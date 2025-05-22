<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Events\TicketPurchased;
use App\Exports\OrdersExport;
use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Cache;

class PaymentController extends Controller
{
    /**
     * Handle payment charge requests from the frontend
     */
    // disini nanti taro eventnya
    public function charge(Request $request, string $client = "")
    {
        $lock = Cache::lock('seat_lock_user_' . Auth::id(), 10);

        if (! $lock->get()) {
            return response()->json(['message' => 'System is processing your order. Please try again in a moment.'], 429);
        }

        try {
            // Order rate limiting
            $hourlyLimit = 5;
            $dailyLimit = 10;

            $recentOrderCount = Order::where('user_id', Auth::id())
                ->where('order_date', '>=', now()->subHour())
                ->count();

            if ($recentOrderCount >= $hourlyLimit) {
                throw new \Exception('Too many orders in a short time. Please wait for 1 hour.');
            }

            $dailyOrderCount = Order::where('user_id', Auth::id())
                ->where('order_date', '>=', now()->subDay())
                ->count();

            if ($dailyOrderCount >= $dailyLimit) {
                throw new \Exception('Too many orders today. Please try again tomorrow.');
            }

            // Request validation
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'amount' => 'required|numeric|min:0',
                'grouped_items' => 'required|array',
                'tax_amount' => 'numeric|nullable',
                'total_with_tax' => 'numeric|nullable',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            if (! Auth::check()) {
                throw new \Exception('Unauthorized');
            }

            $event = Event::where('slug', $client)->first();
            if (! $event) {
                throw new \Exception('Event not found');
            }

            $existingOrders = User::find(Auth::id())
                ->orders()
                ->where('event_id', $event->id)
                ->where('status', OrderStatus::PENDING)
                ->exists();

            if ($existingOrders) {
                throw new \Exception('There is an existing pending order');
            }

            // Collect seat info
            $groupedItems = $request->grouped_items;
            $seats = collect();

            foreach ($groupedItems as $item) {
                if (! empty($item['seatNumbers'])) {
                    $seats = $seats->merge(
                        Seat::whereIn('seat_number', $item['seatNumbers'])
                            ->where('venue_id', $event->venue_id)
                            ->get()
                    );
                }
            }

            if ($seats->isEmpty()) {
                throw new \Exception('No seats available');
            }

            DB::beginTransaction();

            $seatIds = $seats->pluck('id')->toArray();

            $tickets = Ticket::whereIn('seat_id', $seatIds)
                ->where('event_id', $event->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->get();

            if ($tickets->count() !== $seats->count()) {
                DB::rollBack();
                throw new \Exception('Failed to lock seats');
            }

            $itemDetails = [];
            foreach ($groupedItems as $category => $item) {
                $seatLabel = isset($item['seatNumbers']) ? ' (' . implode(', ', $item['seatNumbers']) . ')' : '';
                $itemDetails[] = [
                    'id' => 'TICKET-' . strtoupper($category),
                    'price' => (int) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'name' => ucfirst($category) . ' Ticket' . $seatLabel,
                ];
            }

            $amount = (int) $request->amount;
            $defaultTaxRate = config('app.default_tax_rate', 1);
            $taxAmount = (int) ($request->tax_amount ?? ($amount * $defaultTaxRate / 100));
            $totalWithTax = $amount + $taxAmount;

            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'TAX-' . $defaultTaxRate . 'PCT',
                    'price' => $taxAmount,
                    'quantity' => 1,
                    'name' => 'Tax (' . $defaultTaxRate . '%)',
                ];
            }

            $team = Team::find($event->team_id);
            if (! $team) {
                DB::rollBack();
                throw new \Exception('Team not found');
            }

            $orderCode = Order::keyGen(OrderType::AUTO, $event);

            $order = Order::create([
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'team_id' => $team->id,
                'order_date' => now(),
                'total_price' => $totalWithTax,
                'status' => OrderStatus::PENDING,
                'expired_at' => now()->addMinutes(10),
            ]);

            if (! $order) {
                DB::rollBack();
                throw new \Exception('Failed to create order');
            }

            $updatedTickets = [];
            foreach ($tickets as $ticket) {
                $ticket->update(['status' => TicketStatus::IN_TRANSACTION]);
                TicketOrder::create([
                    'ticket_id' => $ticket->id,
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'status' => TicketOrderStatus::ENABLED,
                ]);

                $updatedTickets[] = [
                    "id" => $ticket->id,
                    "status" => TicketStatus::IN_TRANSACTION,
                    "seat_id" => $ticket->seat_id,
                ];
            }

            $snapToken = null;
            if ($totalWithTax) {
                $snapToken = $this->midtransCharge(
                    $request,
                    $orderCode,
                    $totalWithTax,
                    $itemDetails,
                    $event
                );

                if (! $snapToken) {
                    DB::rollBack();
                    throw new \Exception('Failed to get Snap token');
                }
            } else {
                // Handle free order, auto complete
                $this->updateStatus($orderCode, OrderStatus::COMPLETED->value, []);
                $snapToken = 'free';
            }

            $order->update(['snap_token' => $snapToken]);

            DB::commit();

            return response()->json([
                'snap_token' => $snapToken,
                'transaction_id' => $orderCode,
                'updated_tickets' => $updatedTickets
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            preg_match('/"error_messages":\["(.*?)"/', $e->getMessage(), $matches);
            $firstErrorMessage = $matches[1] ?? null;
            return response()->json(['message' => 'System failed to process payment! ' . $firstErrorMessage . '.'], 500);
        } finally {
            optional($lock)->release();
        }
    }

    public function midtransCharge(
        Request $request,
        string $orderCode,
        float $totalWithTax,
        array $itemDetails,
        Event $event
    ) {
        Config::$serverKey = $event->eventVariables->getKey('server');
        Config::$isProduction = $event->eventVariables->midtrans_is_production;
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);

        $snapToken = Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $orderCode,
                'gross_amount' => $totalWithTax
            ],
            'credit_card' => ['secure' => true],
            'customer_details' => ['email' => $request->email],
            'item_details' => $itemDetails,
        ]);

        if (! $snapToken) {
            DB::rollBack();
            throw new \Exception('Failed to get Snap token');
        }

        return $snapToken;
    }

    /**
     * Handle Midtrans payment callbacks
     */
    public function midtransCallback(Request $request)
    {
        $data = $request->all();
        $identifier = $data['order_id'] ?? null;

        if (!isset($identifier, $data['gross_amount'], $data['transaction_status'])) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        // Check if the identifier is testing
        if (str_contains($identifier, 'payment_notif_test')) {
            return response()->json(['message' => 'Test notification received'], 200);
        }

        DB::beginTransaction();
        try {
            // Process the callback based on transaction status
            switch ($data['transaction_status']) {
                case 'capture':
                case 'settlement':
                    $this->updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case 'pending':
                    $this->updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case 'deny':
                case 'expire':
                case 'cancel':
                    $this->updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
                    break;
            }

            DB::commit();
            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process callback'], 500);
        }
    }

    /**
     * Handle Faspay payment callbacks
     */
    // public function handleFaspayCallback(Request $request)
    // {
    //     $data = $request->all();

    //     $validator = Validator::make($data, [
    //         'trx_id' => 'required|string|max:16',
    //         'merchant_id' => 'required|numeric',
    //         'merchant' => 'required|string|max:32',
    //         'bill_no' => 'required|string|max:32',
    //         'payment_reff' => 'nullable|string|max:32',
    //         'payment_date' => 'required|date_format:Y-m-d H:i:s',
    //         'payment_status_code' => 'required|in:0,1,2,3,4,5,7,8,9',
    //         'payment_status_desc' => 'required|string|max:32',
    //         'bill_total' => 'required|numeric',
    //         'payment_total' => 'required|numeric',
    //         'payment_channel_uid' => 'required|numeric',
    //         'payment_channel' => 'required|string|max:32',
    //         'signature' => 'required|string',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => 'Invalid input', 'details' => $validator->errors()], 400);
    //     }

    //     // Example: Credentials used to compute signature
    //     $userId = config('faspay.user_id');
    //     $password = config('faspay.password');
    //     $expectedSignature = sha1(md5($userId . $password . $data['bill_no'] . $data['payment_status_code']));

    //     if ($data['signature'] !== $expectedSignature) {
    //         return response()->json(['error' => 'Invalid signature'], 403);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Example: Find the corresponding order
    //         $order = Order::where('bill_no', $data['bill_no'])->first();

    //         if (!$order) {
    //             return response()->json(['error' => 'Order not found'], 404);
    //         }

    //         // Map status code to your internal status enum
    //         $statusMap = [
    //             '0' => OrderStatus::UNPROCESSED,
    //             '1' => OrderStatus::PROCESSING,
    //             '2' => OrderStatus::COMPLETED,
    //             '3' => OrderStatus::FAILED,
    //             '4' => OrderStatus::REVERSED,
    //             '5' => OrderStatus::NOT_FOUND,
    //             '7' => OrderStatus::EXPIRED,
    //             '8' => OrderStatus::CANCELLED,
    //             '9' => OrderStatus::UNKNOWN,
    //         ];

    //         $newStatus = $statusMap[$data['payment_status_code']] ?? OrderStatus::UNKNOWN;

    //         // Update order
    //         $order->update([
    //             'status' => $newStatus->value,
    //             'payment_reference' => $data['payment_reff'] ?? null,
    //             'paid_at' => $data['payment_date'],
    //             'payment_channel' => $data['payment_channel'],
    //             'payment_status_desc' => $data['payment_status_desc'],
    //             'payment_total' => $data['payment_total'],
    //         ]);

    //         DB::commit();
    //         return response()->json(['message' => 'Payment callback processed']);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Faspay callback failed: ' . $e->getMessage());
    //         return response()->json(['error' => 'Internal server error'], 500);
    //     }
    // }

    public function faspayCallback(Request $request)
    {
        $data = $request->all();

        return response()->json([
            'response'       => 'Payment Notification',
            'trx_id'         => $data['trx_id'] ?? null,
            'merchant_id'    => $data['merchant_id'] ?? null,
            'merchant'       => $data['merchant'] ?? null,
            'bill_no'        => $data['bill_no'] ?? null,
            'response_code'  => '00',
            'response_desc'  => 'Success',
            'response_date'  => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function faspayReturn(Request $request)
    {
        // Retrieve all request data
        $data = $request->all();

        // Validate required parameters
        $requiredParams = [
            'merchant_id',
            'bill_no',
            'trx_id',
            'bill_reff',
            'payment_date',
            'bank_user_name',
            'status',
            'signature'
        ];

        foreach ($requiredParams as $param) {
            if (!isset($data[$param])) {
                return response()->json([
                    'response'       => 'Error',
                    'response_code'  => '01',
                    'response_desc'  => "Missing parameter: $param",
                    'response_date'  => now()->format('Y-m-d H:i:s'),
                ], 400);
            }
        }

        // Here you can add signature verification logic if needed

        // Abort for now
        abort(403, "
            This is a callback test. Your include such information:
            trx_id: {$data['trx_id']}
            merchant_id: {$data['merchant_id']}
            bill_no: {$data['bill_no']}
            bill_reff: {$data['bill_reff']}
            payment_date: {$data['payment_date']}
            bank_user_name: {$data['bank_user_name']}
            status: {$data['status']}
            signature: {$data['signature']}
        ");

        // Redirect to thank you page or any other page
        return redirect()->route('thankYouPage', [
            'trx_id'         => $data['trx_id'],
            'merchant_id'    => $data['merchant_id'],
            'bill_no'        => $data['bill_no'],
            'bill_reff'      => $data['bill_reff'],
            'payment_date'   => $data['payment_date'],
            'bank_user_name' => $data['bank_user_name'],
            'status'         => $data['status'],
            'signature'      => $data['signature'],
        ])->with([
            'response_code'  => '00',
            'response_desc'  => 'Success',
            'response_date'  => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update order status in the database
     */
    private function updateStatus($orderCode, $status, $transactionData)
    {
        try {
            DB::beginTransaction();
            // Find order first
            $order = Order::where('order_code', $orderCode)->first();
            if (!$order) {
                throw new \Exception('Order not found: ' . $orderCode);
            }

            $currentStatus = $order->status;
            if ($currentStatus === $status || $currentStatus === OrderStatus::CANCELLED || $currentStatus === OrderStatus::COMPLETED) {
                // No need to update if status is the same and ignore completed/cancelled orders
                DB::commit();
                return;
            }

            // Update order status
            $order->status = $status;
            $order->save();

            $updatedTickets = [];

            // Update ticket statuses
            $ticketOrders = TicketOrder::where('order_id', $order->id)->get();
            foreach ($ticketOrders as $ticketOrder) {
                $ticket = Ticket::find($ticketOrder->ticket_id);
                if ($ticket) { // Ensure the ticket exists before updating
                    $ticket->status = $status === OrderStatus::COMPLETED->value ? TicketStatus::BOOKED->value : TicketStatus::AVAILABLE->value;
                    $ticket->save();

                    $updatedTickets[] = [
                        "id" => $ticket->id,
                        "status" => $status,
                        "seat_id" => $ticket->seat_id,
                        "ticket_category_id" => $ticket->ticket_category_id,
                        "ticket_type" => $ticket->ticket_type,
                    ];
                }
            }
            DB::commit();
            $this->publishMqtt(data: [
                'event' => "update_ticket_status",
                'data' => $updatedTickets
            ]);
        } catch (\Exception $e) {
            $this->publishMqtt(data: [
                'message' => $e,
            ]);
            DB::rollBack();
            throw $e;
        }
    }

    public function getPendingTransactions(string $client = '')
    {
        try {
            $userId = Auth::id();

            // Get the client's event
            if (!$client) {
                return response()->json(['success' => false, 'error' => 'Client identifier not found'], 400);
            }

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                return response()->json(['error' => 'Event not found'], 404);
            }

            // PERBAIKAN: Gunakan query builder dengan kondisi yang benar
            $pendingOrders = Order::where('user_id', $userId)  // Perhatikan: kolom 'id' merujuk ke user_id
                ->where('event_id', $event->id)
                ->where('status', OrderStatus::PENDING)
                ->get();

            $pendingTransactions = [];

            foreach ($pendingOrders as $order) {
                // Get tickets for this order
                $ticketOrders = TicketOrder::where('order_id', $order->id)->get();
                $ticketIds = $ticketOrders->pluck('ticket_id');

                $tickets = Ticket::whereIn('id', $ticketIds)->get();
                $seatIds = $tickets->pluck('seat_id');

                // Get seats
                $seats = Seat::whereIn('id', $seatIds)->get();

                $seatsData = $seats->map(function ($seat) use ($tickets) {
                    $ticket = $tickets->where('seat_id', $seat->id)->first();

                    // PERBAIKAN: Tambahkan pengecekan agar tidak error jika ticket null
                    if (!$ticket) {
                        return null;
                    }

                    return [
                        'seat_id' => $seat->id,
                        'seat_number' => $seat->seat_number,
                        'ticket_type' => $ticket->ticket_type,
                        'category' => $ticket->ticketCategory,
                        'status' => $ticket->status,
                        'price' => $ticket->price,
                    ];
                })->filter()->values(); // PERBAIKAN: Filter null values dan reset index array

                $pendingTransactions[] = [
                    'snap_token' => $order->snap_token,
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'total_price' => $order->total_price,
                    'seats' => $seatsData,
                ];
            }

            return response()->json([
                'success' => true,
                'pendingTransactions' => $pendingTransactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch pending transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelPendingTransactions(Request $request, string $client = '')
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'order_ids' => 'required|array',
            ]);

            if ($validator->fails()) {
                throw new \Exception('Validation error: ' . $validator->errors()->first());
            }

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                throw new \Exception('Event not found');
            }

            $orderIds = $request->order_ids;
            $orders = Order::whereIn('id', $orderIds)
                ->where('user_id', Auth::id())
                ->where('event_id', $event->id)
                ->where('status', OrderStatus::PENDING)
                ->get();

            if ($orders->isEmpty()) {
                throw new \Exception('No pending orders found');
            }

            $updatedTickets = [];

            foreach ($orders as $order) {
                // Update order status
                $order->status = OrderStatus::CANCELLED;
                $order->save();

                // Update ticket statuses
                $ticketOrders = TicketOrder::where('order_id', $order->id)->get();
                foreach ($ticketOrders as $ticketOrder) {
                    $ticket = Ticket::find($ticketOrder->ticket_id);
                    if ($ticket) {
                        $ticket->status = TicketStatus::AVAILABLE;
                        $ticket->save();

                        $updatedTickets[] = [
                            "id" => $ticket->id,
                            "status" => $ticket->status,
                            "seat_id" => $ticket->seat_id,
                            "ticket_category_id" => $ticket->ticket_category_id,
                            "ticket_type" => $ticket->ticket_type,
                        ];
                    }

                    // Set current status to cancelled
                    $ticketOrder->status = TicketOrderStatus::DEACTIVATED;
                    $ticketOrder->save();
                }
            }

            // $this->publishMqtt(data: [
            //     'event' => "update_ticket_status",
            //     'data' => $updatedTickets
            // ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Orders cancelled successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel orders: ' . $e->getMessage()
            ], 500);
        }
    }

    public function fetchMidtransClientKey(string $client = '')
    {
        try {
            $event = Event::where('slug', $client)->first();
            if (!$event) {
                return response()->json(['error' => 'Event not found'], 404);
            }

            $clientKey = $event->eventVariables->getKey();
            $isProduction = $event->eventVariables->midtrans_is_production;

            return response()->json(['client_key' => $clientKey, 'is_production' => $isProduction]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch client key'], 500);
        }
    }

    /**
     * Download orders as an Excel file
     */
    public function ordersDownload($id = null)
    {
        try {
            $user = Auth::user();

            $orderExport = new OrdersExport($id, $user);

            return Excel::download(
                $orderExport,
                $orderExport->title() . '.csv',
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download orders: ' . $e->getMessage()], 500);
        }
    }

    public function publishMqtt(array $data, string $mqtt_code = "defaultcode", string $client_name = "defaultclient")
    {
        $server = 'broker.emqx.io';
        $port = 1883;
        $clientId = 'novatix_midtrans' . rand(100, 999);
        $username = 'emqx';
        $password = 'public';
        $mqtt_version = MqttClient::MQTT_3_1_1;

        // $topic = 'novatix/midtrans/' . $client_name . '/' . $mqtt_code . '/ticketpurchased';
        // Atau fallback static jika belum support param client_name
        $topic = 'novatix/midtrans/defaultcode';

        $conn_settings = (new ConnectionSettings)
            ->setUsername($username)
            ->setPassword($password)
            ->setLastWillMessage('client disconnected')
            ->setLastWillTopic('emqx/last-will')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, $mqtt_version);

        try {
            $mqtt->connect($conn_settings, true);

            // Pastikan koneksi sukses sebelum publish
            if ($mqtt->isConnected()) {
                $mqtt->publish(
                    $topic,
                    json_encode($data),
                    0 // QoS
                );
                Log::info('MQTT Publish success to topic: ' . $topic, $data);
            } else {
                Log::warning('MQTT not connected. Skipped publishing to topic: ' . $topic);
            }

            $mqtt->disconnect();
        } catch (\Throwable $th) {
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}

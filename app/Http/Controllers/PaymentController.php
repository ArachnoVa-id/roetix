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
                return response()->json(['message' => 'Too many orders in a short time. Please wait for 1 hour.'], 429);
            }

            $dailyOrderCount = Order::where('user_id', Auth::id())
                ->where('order_date', '>=', now()->subDay())
                ->count();

            if ($dailyOrderCount >= $dailyLimit) {
                return response()->json(['message' => 'Too many orders today. Please try again tomorrow.'], 429);
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
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $event = Event::where('slug', $client)->first();
            if (! $event) {
                return response()->json(['message' => 'Event not found'], 404);
            }

            $existingOrders = User::find(Auth::id())
                ->orders()
                ->where('event_id', $event->id)
                ->where('status', OrderStatus::PENDING)
                ->exists();

            if ($existingOrders) {
                return response()->json(['message' => 'There is an existing pending order'], 400);
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
                return response()->json(['message' => 'No seats available'], 400);
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
                return response()->json(['message' => 'Failed to lock seats'], 500);
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
                return response()->json(['message' => 'Team not found'], 404);
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
                return response()->json(['message' => 'Failed to create order'], 500);
            }

            foreach ($tickets as $ticket) {
                $ticket->update(['status' => TicketStatus::IN_TRANSACTION]);
                TicketOrder::create([
                    'ticket_id' => $ticket->id,
                    'order_id' => $order->id,
                    'event_id' => $event->id,
                    'status' => TicketOrderStatus::ENABLED,
                ]);
                // $this->publishMqtt(data: [
                //     'id' => $ticket->id,
                //     'status' => TicketStatus::IN_TRANSACTION,
                //     'seat_id' => $ticket->seat_id,
                //     'ticket_category_id' => $ticket->ticket_category_id,
                //     'ticket_type' => $ticket->ticket_type,
                // ]);
            }

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
                return response()->json(['message' => 'Failed to get Snap token'], 500);
            }

            $order->update(['snap_token' => $snapToken]);
            DB::commit();

            return response()->json(['snap_token' => $snapToken, 'transaction_id' => $orderCode]);
        } catch (\Exception $e) {
            DB::rollBack();
            preg_match('/"error_messages":\["(.*?)"/', $e->getMessage(), $matches);
            $firstErrorMessage = $matches[1] ?? null;
            return response()->json(['message' => 'System failed to process payment! ' . $firstErrorMessage . '.'], 500);
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Handle Midtrans payment callbacks
     */
    public function midtransCallback(Request $request)
    {
        $data = $request->all();
        $identifier = $data['order_id'] ?? null;

        // $this->publishMqtt(data: [
        //     'id' => $identifier,
        //     'status' => 'hallo callback',
        // ]);

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
                // No need to update if status is the same
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
                        "status" => $ticket->status,
                        "seat_id" => $ticket->seat_id,
                        "ticket_category_id" => $ticket->ticket_category_id,
                        "ticket_type" => $ticket->ticket_type,
                    ];
                }
            }
            DB::commit();
            $this->publishMqtt(data: [
                'message' => "kontol",
                'data' => $updatedTickets
            ]);
            $this->publishMqtt(data: $updatedTickets);
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

            foreach ($orders as $order) {
                // Update order status
                $order->status = OrderStatus::CANCELLED;
                $order->save();

                // Update ticket statuses
                $ticketOrders = TicketOrder::where('order_id', $order->id)->get();
                foreach ($ticketOrders as $ticketOrder) {
                    $ticket = Ticket::find($ticketOrder->ticket_id);
                    if ($ticket) { // Ensure the ticket exists before updating
                        $ticket->status = TicketStatus::AVAILABLE;
                        $ticket->save();
                    }

                    // Set current status to cancelled
                    $ticketOrder->status = TicketOrderStatus::DEACTIVATED;
                    $ticketOrder->save();
                }
            }

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
        $usrname = 'emqx';
        $password = 'public';
        $mqtt_version = MqttClient::MQTT_3_1_1;
        // $topic = 'novatix/midtrans/' . $client_name . '/' . $mqtt_code . '/ticketpurchased';
        $topic = 'novatix/midtrans/defaultcode';

        $conn_settings = (new ConnectionSettings)
            ->setUsername($usrname)
            ->setPassword($password)
            ->setLastWillMessage('client disconnected')
            ->setLastWillTopic('emqx/last-will')
            ->setLastWillQualityOfService(1);

        $mqtt = new MqttClient($server, $port, $clientId, $mqtt_version);

        try {
            $mqtt->connect($conn_settings, true);
            $mqtt->publish(
                $topic,
                json_encode($data),
                0
            );
            $mqtt->disconnect();
        } catch (\Throwable $th) {
            // biarin lewat aja biar ga bikin masalah di payment controller flow nya
            Log::error('MQTT Publish Failed: ' . $th->getMessage());
        }
    }
}

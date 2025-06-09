<?php

namespace App\Http\Controllers;

use Midtrans\Snap;
use App\Models\Seat;
use App\Models\Team;
use App\Models\User;
use Midtrans\Config;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Enums\OrderType;
use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Models\TicketOrder;
use Illuminate\Support\Str;
use App\Models\DevNoSQLData;
use Illuminate\Http\Request;
use App\Enums\PaymentGateway;
use App\Exports\OrdersExport;
use PhpMqtt\Client\MqttClient;
use App\Enums\TicketOrderStatus;
use App\Models\UserContact;
use App\Services\ResendMailer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Handle payment charge requests from the frontend
     */
    // disini nanti taro eventnya
    public function charge(Request $request, string $client = "")
    {
        // Validate
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'grouped_items' => 'required|array',
            'tax_amount' => 'numeric|nullable',
            'total_with_tax' => 'numeric|nullable',
        ]);

        $lock = Cache::lock('seat_lock_user_' . Auth::id(), 10);

        if (! $lock->get()) {
            return response()->json(['message' => 'System is processing your order. Please try again in a moment.'], 429);
        }

        try {
            // Order rate limiting
            $hourlyLimit = 50;
            $dailyLimit = 100;

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
                'payment_gateway' => $event->eventVariables->payment_gateway,
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

            $accessor = null;
            // Initiate pgs if totalWithTax is not null
            if ($totalWithTax > 0) {
                switch ($event->eventVariables->payment_gateway) {
                    case PaymentGateway::MIDTRANS->value:
                        try {
                            $accessor = $this->midtransCharge(
                                $request,
                                $orderCode,
                                $totalWithTax,
                                $itemDetails,
                                $event
                            );

                            if (! $accessor) {
                                throw new \Exception('Failed to get Snap token');
                            }
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw new \Exception('Failed to process Midtrans charge: ' . $e->getMessage());
                        }
                        break;

                    case PaymentGateway::FASPAY->value:
                        try {
                            $accessor = $this->faspayCharge(
                                $request,
                                $orderCode,
                                $totalWithTax,
                                $itemDetails,
                                $event
                            );

                            if (! $accessor) {
                                throw new \Exception('Failed to get Faspay token');
                            }
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw new \Exception('Failed to process Faspay charge: ' . $e->getMessage());
                        }
                        break;

                    case PaymentGateway::TRIPAY->value:
                        try {
                            $accessor = $this->tripayCharge(
                                $request,
                                $orderCode,
                                $totalWithTax,
                                $itemDetails,
                                $event
                            );

                            if (! $accessor) {
                                throw new \Exception('Failed to get Tripay token');
                            }
                        } catch (\Exception $e) {
                            DB::rollBack();
                            throw new \Exception('Failed to process Tripay charge: ' . $e->getMessage());
                        }
                        break;

                    default:
                        DB::rollBack();
                        throw new \Exception('Invalid payment gateway');
                }
            } else {
                // Handle free order, auto complete
                $this->updateStatus($orderCode, OrderStatus::COMPLETED->value, []);
                $accessor = 'free';
            }

            $order->update(['accessor' => $accessor]);

            DB::commit();

            return response()->json([
                'accessor' => $accessor,
                'transaction_id' => $orderCode,
                'updated_tickets' => $updatedTickets
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'System failed to process payment! ' . $e->getMessage() . '.'], 500);
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
        if ($event->eventVariables->midtrans_use_novatix) {
            if ($event->eventVariables->midtrans_is_production) {
                Config::$serverKey = config('midtrans.server_key');
            } else {
                Config::$serverKey = config('midtrans.server_key_sb');
            }
        } else {
            Config::$serverKey = $event->eventVariables->getKey('server');
        }
        Config::$isProduction = $event->eventVariables->midtrans_is_production;
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);

        $accessor = Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $orderCode,
                'gross_amount' => $totalWithTax
            ],
            'credit_card' => ['secure' => true],
            'customer_details' => ['email' => $request->email],
            'item_details' => $itemDetails,
        ]);

        if (! $accessor) {
            DB::rollBack();
            throw new \Exception('Failed to get Snap token');
        }

        DevNoSQLData::create([
            'collection' => 'midtrans_orders',
            'data' => [
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'team_id' => $event->team_id,
                'total_price' => $totalWithTax,
                'status' => OrderStatus::PENDING,
                'expired_at' => now()->addMinutes(10),
                'payment_gateway' => $event->eventVariables->payment_gateway,
                'accessor' => $accessor,
            ],
        ]);

        return $accessor;
    }

    public function faspayCharge(
        Request $request,
        string $orderCode,
        float $totalWithTax,
        array $itemDetails,
        Event $event
    ) {
        $variables = $event->eventVariables;

        $faspay_baseUrl = $variables->faspay_is_production ? 'https://debit.faspay.co.id' : 'https://debit-sandbox.faspay.co.id';
        $endpoint = $faspay_baseUrl . '/cvr/300011/10';

        if ($variables->faspay_use_novatix) {
            $merchantId = config('faspay.merchant_id');
            $merchantName = config('faspay.merchant_name');
            $userId = config('faspay.user_id');
            $password = $variables->faspay_is_production ? config('faspay.password_prod') : config('faspay.password');
        } else {
            $merchantId = Crypt::decryptString($variables->faspay_merchant_id);
            $merchantName = Crypt::decryptString($variables->faspay_merchant_name);
            $userId = Crypt::decryptString($variables->faspay_user_id);
            $password = $variables->faspay_is_production ? Crypt::decryptString($variables->faspay_password_prod) : Crypt::decryptString($variables->faspay_password);
        }

        $signature = sha1(md5($userId . $password . $orderCode));

        $now = now();
        $billDate = $now->format('Y-m-d H:i:s');
        $billExpired = $now->addMinutes(10)->format('Y-m-d H:i:s');

        $payload = [
            "request" => "Post Data Transaction",
            "merchant_id" => $merchantId,
            "merchant" => $merchantName,
            "bill_no" => $orderCode,
            "bill_reff" => $orderCode,
            "bill_date" => $billDate,
            "bill_expired" => $billExpired,
            "bill_desc" => "Payment #$orderCode",
            "bill_currency" => "IDR",
            "bill_gross" => 0,
            "bill_miscfee" => 0,
            "bill_total" => (int) $totalWithTax * 100,
            "cust_no" => $request->user()->id ?? '0',
            "cust_name" => $request->user()->name ?? 'Guest',
            "payment_channel" => "836", // Paydia QRIS
            "pay_type" => "1",
            "bank_userid" => "",
            "msisdn" => $request->phone ?? '',
            "email" => $request->email ?? '',
            "terminal" => "10",

            "billing_name" => '',
            "billing_lastname" => '',
            "billing_address" => '',
            "billing_address_city" => "",
            "billing_address_region" => "",
            "billing_address_state" => "",
            "billing_address_poscode" => "",
            "billing_msisdn" => "",
            "billing_address_country_code" => "ID",

            "receiver_name_for_shipping" => '',
            "shipping_lastname" => "",
            "shipping_address" => '',
            "shipping_address_city" => "",
            "shipping_address_region" => "",
            "shipping_address_state" => "",
            "shipping_address_poscode" => "",
            "shipping_msisdn" => "",
            "shipping_address_country_code" => "ID",

            "item" => collect($itemDetails)->map(function ($item) {
                return [
                    "product" => $item['name'],
                    "qty" => (string) $item['quantity'],
                    "amount" => (string) $item['price'] . '00',
                ];
            })->values()->toArray(),

            "reserve1" => "",
            "reserve2" => "",
            "signature" => $signature,
        ];

        $response = Http::post($endpoint, $payload);

        if (! $response->ok()) {
            throw new \Exception("Faspay charge failed: " . $response->json());
        }

        $returnData = [
            'order_code' => $orderCode,
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'team_id' => $event->team_id,
            'total_price' => $totalWithTax,
            'status' => OrderStatus::PENDING,
            'expired_at' => now()->addMinutes(10),
            'payment_gateway' => $variables->payment_gateway,
        ];

        $responseData = $response->json();

        $returnData = array_merge($returnData, $responseData, ['signature' => $signature]);

        DevNoSQLData::create([
            'collection' => 'faspay_orders',
            'data' => $returnData,
        ]);


        return $responseData['redirect_url'] ?? null;
    }

    public function tripayCharge(
        Request $request,
        string $orderCode,
        float $totalWithTax,
        array $itemDetails,
        Event $event
    ) {
        $variables = $event->eventVariables;

        // Select endpoint
        $tripay_baseUrl = $variables->tripay_is_production ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
        $endpoint = $tripay_baseUrl . '/transaction/create';

        // Select keys
        if ($variables->tripay_use_novatix) {
            if ($variables->tripay_is_production) {
                $apiKey = config('tripay.api_key');
                $privateKey = config('tripay.private_key');
                $merchantCode = config('tripay.merchant_code');
            } else {
                $apiKey = config('tripay.api_key_sb');
                $privateKey = config('tripay.private_key_sb');
                $merchantCode = config('tripay.merchant_code_sb');
            }
        } else {
            if ($variables->tripay_is_production) {
                $apiKey = Crypt::decryptString($variables->tripay_api_key_prod);
                $privateKey = Crypt::decryptString($variables->tripay_private_key_prod);
                $merchantCode = Crypt::decryptString($variables->tripay_merchant_code_prod);
            } else {
                $apiKey = Crypt::decryptString($variables->tripay_api_key_dev);
                $privateKey = Crypt::decryptString($variables->tripay_private_key_dev);
                $merchantCode = Crypt::decryptString($variables->tripay_merchant_code_dev);
            }
        }

        // Generate signature
        $customer = $request->user();
        $userQueue = Event::getUser($event, $customer); // Replace YourQueueClass with actual class name

        // Calculate timeout based on user's expected_kick time
        if ($userQueue && isset($userQueue['expected_kick'])) {
            $expectedKick = Carbon::parse($userQueue['expected_kick']);
            $now = Carbon::now();

            // If user's session has already expired, use minimum timeout (1 minute)
            if ($expectedKick->isPast()) {
                $timeout = $now->addMinutes(1)->timestamp;
            } else {
                // Use the user's remaining time, but ensure minimum 1 minute
                $remainingMinutes = max(1, $now->diffInMinutes($expectedKick));
                $timeout = $now->addMinutes($remainingMinutes)->timestamp;
            }
        } else {
            // Fallback to 10 minutes if user is not in queue (admin or error case)
            $timeout = now()->addMinutes(10)->timestamp;
        }

        $signature = hash_hmac('sha256', $merchantCode . $orderCode . $totalWithTax, $privateKey);

        // Construct payload
        $payload = [
            "method" => "QRIS", // payment method
            "merchant_ref" => $orderCode,
            "amount" => (int) $totalWithTax,
            "customer_name" => $customer->name ?? 'Guest',
            "customer_email" => $customer->email ?? '',
            "customer_phone" => $request->phone ?? '',
            "order_items" => collect($itemDetails)->map(function ($item) {
                return [
                    "sku" => $item['sku'] ?? Str::slug($item['name']),
                    "name" => $item['name'],
                    "price" => (int) $item['price'],
                    "quantity" => (int) $item['quantity'],
                    "product_url" => $item['product_url'] ?? null,
                    "image_url" => $item['image_url'] ?? null,
                ];
            })->values()->toArray(),
            "return_url" => route('payment.tripayReturn'),
            "expired_time" => $timeout,
            "signature" => $signature,
        ];

        // Send request
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
        ])->post($endpoint, $payload);

        if (! $response->ok()) {
            throw new \Exception("Tripay charge failed: " . $response->json()['message']);
        }

        $responseData = $response->json()['data'];
        // Store transaction
        DevNoSQLData::create([
            'collection' => 'tripay_orders',
            'data' => array_merge([
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => Auth::id(),
                'team_id' => $event->team_id,
                'total_price' => $totalWithTax,
                'status' => OrderStatus::PENDING,
                'expired_at' => now()->addMinutes(10),
                'payment_gateway' => $variables->payment_gateway,
            ], $responseData),
        ]);

        return $responseData['checkout_url'] ?? null;
    }

    /**
     * Handle Midtrans payment callbacks
     */
    public function midtransCallback(Request $request)
    {
        $data = $request->all();
        $identifier = $data['order_id'] ?? null;

        // Append the origin ip (host) of the request to data
        $data['origin_ip'] = $request->ip();
        $data['host'] = $request->getHost();

        // Log the callback data
        DevNoSQLData::create([
            'collection' => 'midtrans_callbacks',
            'data' => $data,
        ]);

        // // Only 172.68.164.43 (cloudflare midtrans)
        // if ($request->ip() != '172.68.164.43') {
        //     return response()->json(['error' => 'Forbidden request origin'], 403);
        // }

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

    public function faspayCallback(Request $request)
    {
        $data = $request->all();
        $identifier = $data['bill_no'] ?? null;

        // Append the origin ip (host) of the request to data
        $data['origin_ip'] = $request->ip();
        $data['host'] = $request->getHost();

        // Log the callback data
        DevNoSQLData::create([
            'collection' => 'faspay_callbacks',
            'data' => $data,
        ]);

        if (!isset($identifier, $data['payment_status_code'])) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        DB::beginTransaction();
        try {
            // Process the callback based on transaction status
            switch ($data['payment_status_code']) {
                case '2':
                    $this->updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case '1':
                    $this->updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case '3':
                case '4':
                case '5':
                case '7':
                case '8':
                    $this->updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
                    break;
                default:
                    throw new \Exception('Invalid status');
            }

            DB::commit();
            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process callback'], 500);
        }
    }

    public function tripayCallback(Request $request)
    {
        $data = $request->all();
        $identifier = $data['merchant_ref'] ?? null;

        // Append the origin ip (host) of the request to data
        $data['origin_ip'] = $request->ip();
        $data['host'] = $request->getHost();

        // Log the callback data
        DevNoSQLData::create([
            'collection' => 'tripay_callbacks',
            'data' => $data,
        ]);

        // Only accept from 162.158.189.25 (cloudflare tripay)
        // if ($request->ip() != '162.158.189.25') {
        //     return response()->json(['error' => 'Forbidden request origin'], 403);
        // }

        if (!isset($identifier, $data['status'])) {
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        DB::beginTransaction();
        try {
            // Process the callback based on transaction status
            switch ($data['status']) {
                case 'PAID':
                    $this->updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case 'UNPAID':
                    $this->updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case 'EXPIRED':
                case 'REFUND':
                case 'FAILED':
                    $this->updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
                    break;
                default:
                    throw new \Exception('Invalid status');
            }

            DB::commit();
            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to process callback'], 500);
        }
    }

    public function midtransReturn(Request $request)
    {
        // Retrieve all request data
        $data = $request->all();

        // Validate required parameters
        $validator = Validator::make($data, [
            'order_id' => 'required|string|max:16',
            'status_code' => 'required|string|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Callback return is not valid'], 404);
        }

        // Get orders from order_code => order_code and read the client slug
        $order = Order::where('order_code', $data['order_id'])->first();
        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        // Get event
        $event = Event::find($order->event_id);
        if (!$event) {
            return response()->json(['success' => false, 'error' => 'Event not found'], 404);
        }

        // Redirect to my_tickets
        return redirect()->route(
            'client.my_tickets',
            ['client' => $event->slug,]
        );
    }

    public function faspayReturn(Request $request)
    {
        // Retrieve all request data
        $data = $request->all();

        // Validate required parameters
        $validator = Validator::make($data, [
            'trx_id'         => 'required|string|max:16',
            'merchant_id'    => 'required|numeric',
            'bill_no'        => 'required|string|max:32',
            'bill_reff'      => 'nullable|string|max:32',
            'payment_date'   => 'required|date_format:Y-m-d H:i:s',
            'bank_user_name' => 'required|string|max:32',
            'status'         => 'required|string|max:32',
            'signature'      => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Callback return is not valid'], 404);
        }

        // Get orders from bill_no => order_code and read the client slug
        $order = Order::where('order_code', $data['bill_no'])->first();
        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        // Get event
        $event = Event::find($order->event_id);
        if (!$event) {
            return response()->json(['success' => false, 'error' => 'Event not found'], 404);
        }

        // Redirect to my_tickets
        return redirect()->route(
            'client.my_tickets',
            ['client' => $event->slug,]
        );
    }

    public function tripayReturn(Request $request)
    {
        // Retrieve all request data
        $data = $request->all();

        // Validate required parameters
        $validator = Validator::make($data, [
            'tripay_merchant_ref' => 'required|string|max:32',
            'tripay_reference' => 'required|string|max:32',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => 'Callback return is not valid'], 404);
        }

        // Get orders from bill_no => order_code and read the client slug
        $order = Order::where('order_code', $data['tripay_merchant_ref'])->first();
        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        // Get event
        $event = Event::find($order->event_id);
        if (!$event) {
            return response()->json(['success' => false, 'error' => 'Event not found'], 404);
        }

        // Method 1: Using view with JavaScript to replace history
        $redirectUrl = route('client.my_tickets', ['client' => $event->slug]);

        return response()->view('redirect-replace', compact('redirectUrl'));
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

            // Get user
            $user = User::find($order->user_id);
            if (!$user) {
                throw new \Exception('User not found for order: ' . $orderCode);
            }

            // Get user contact
            $userContact = UserContact::find($user->contact_info);
            if (!$userContact) {
                throw new \Exception('User contact not found for user: ' . $user->id);
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
                        "seat_number" => $ticket->seat->seat_number,
                        "ticket_category_id" => $ticket->ticket_category_id,
                        "ticket_type" => $ticket->ticket_type,
                    ];
                }
            }

            // Commit the transaction before attempting to send email
            DB::commit();

            // Send email outside of transaction to prevent rollback on email failure
            if ($status === OrderStatus::COMPLETED->value) {
                try {
                    // Get event details
                    $event = $order->getSingleEvent();

                    // Render the email template
                    $emailHtml = view('emails.order-confirmation', [
                        'order' => $order,
                        'event' => $event,
                        'tickets' => $updatedTickets,
                        'user' => $user,
                        'userContact' => $userContact
                    ])->render();

                    $resendMailer = new ResendMailer();
                    $resendMailer->send(
                        to: $userContact->email,
                        subject: "🎫 Your Tickets are Confirmed - Order #{$order->order_code}",
                        html: $emailHtml
                    );

                    // Log successful email send
                    Log::info('Order confirmation email sent successfully', [
                        'order_code' => $order->order_code,
                        'user_id' => $user->id,
                        'email' => $userContact->email
                    ]);
                } catch (\Exception $e) {
                    // Log the email failure but don't break the system
                    Log::error('Failed to send order completion email ' . $e->getMessage(), [
                        'order_code' => $order->order_code,
                        'user_id' => $user->id,
                        'email' => $userContact->email,
                    ]);
                }
            }

            // Publish MQTT message about successful ticket update
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
                return response()->json(['success' => false, 'error' => 'Event not found'], 404);
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
                    'accessor' => $order->accessor,
                    'order_id' => $order->id,
                    'order_code' => $order->order_code,
                    'total_price' => $order->total_price,
                    'seats' => $seatsData,
                    'payment_gateway' => $order->payment_gateway,
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

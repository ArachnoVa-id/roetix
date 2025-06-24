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
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Handle payment charge requests from the frontend
     */
    // disini nanti taro eventnya
    public function charge(Request $request, string $client = "")
    {
        Log::info('PaymentController@charge called', [
            'client' => $client,
            'user_id' => Auth::id(),
            'request_data' => $request->all(),
        ]);
        // Reject other than role user
        if (! Auth::check() || ! User::find(Auth::id())->isUser()) {
            Log::warning('Unauthorized payment attempt', [
                'user_id' => Auth::id(),
                'client' => $client,
            ]);
            return response()->json(['message' => 'Only users can buy tickets!'], 401);
        }

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
            Log::warning('Payment charge request is being processed', [
                'user_id' => Auth::id(),
                'client' => $client,
            ]);
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
                Log::warning('Too many orders in a short time', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'recent_order_count' => $recentOrderCount,
                ]);
                throw new \Exception('Too many orders in a short time. Please wait for 1 hour.');
            }

            $dailyOrderCount = Order::where('user_id', Auth::id())
                ->where('order_date', '>=', now()->subDay())
                ->count();

            if ($dailyOrderCount >= $dailyLimit) {
                Log::warning('Too many orders today', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'daily_order_count' => $dailyOrderCount,
                ]);
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
                Log::error('Validation error in payment charge', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'errors' => $validator->errors()->toArray(),
                ]);
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Fetch the event early to use its ID in the DevNoSQLData query
            $event = Event::where('slug', $client)->first();
            if (! $event) {
                Log::error('Event not found', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                ]);
                throw new \Exception('Event not found');
            }

            // Check if user_id_no exists with a completed or pending order FOR THE CURRENT EVENT
            $hasActiveOrderWithSameId = DevNoSQLData::where('collection', 'roetixUserData')
                ->whereJsonContains('data->user_id_no', $request->extra_data['user_id_no'] ?? '')
                ->whereRaw('JSON_EXTRACT(data, "$.accessor") IS NOT NULL')
                ->whereExists(function ($query) use ($event) { // Perhatikan `use ($event)` di sini
                    $query->select(DB::raw(1))
                        ->from('orders')
                        ->whereRaw('orders.accessor = JSON_UNQUOTE(JSON_EXTRACT(dev_nosql_data.data, "$.accessor"))')
                        ->where('orders.event_id', $event->id) // Baris baru ini yang memfilter berdasarkan event
                        ->whereIn('orders.status', [
                            OrderStatus::COMPLETED->value,
                            OrderStatus::PENDING->value
                        ]);
                })
                ->exists();

            if ($hasActiveOrderWithSameId) {
                Log::warning('ID Number already used in an active order', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'user_id_no' => $request->extra_data['user_id_no'] ?? 'N/A',
                ]);
                throw new \Exception('Your ID Number is already used in an active order. Refresh the page if you\'re sure that it was your payment.');
            }

            // If the user_id_no is less than 10 characters, return error
            if (isset($request->extra_data['user_id_no']) && strlen($request->extra_data['user_id_no']) < 10) {
                Log::error('ID Number too short', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'user_id_no' => $request->extra_data['user_id_no'] ?? 'N/A',
                ]);
                throw new \Exception('Your ID Number must be at least 10 characters long');
            }

            $noSQLRecord = DevNoSQLData::create([
                'collection' => 'roetixUserData',
                'data' => $request->extra_data,
            ]);

            if (! Auth::check()) {
                Log::error('Unauthorized access attempt', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                ]);
                throw new \Exception('Unauthorized');
            }

            $event = Event::where('slug', $client)->first();
            if (! $event) {
                Log::error('Event not found', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                ]);
                throw new \Exception('Event not found');
            }

            $existingOrders = User::find(Auth::id())
                ->orders()
                ->where('event_id', $event->id)
                ->where('status', OrderStatus::PENDING)
                ->exists();

            if ($existingOrders) {
                Log::warning('Pending order exists for user', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'event_id' => $event->id,
                ]);
                throw new \Exception('There is an existing pending order, please refresh the page to respond');
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
                Log::error('No seats available for selected items', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'grouped_items' => $groupedItems,
                ]);
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
                Log::error('Some seats are already taken', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'requested_seats' => $seatIds,
                    'available_tickets' => $tickets->pluck('seat_id')->toArray(),
                ]);
                throw new \Exception('These seats are already taken, please choose another seat');
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
                Log::error('Event team not found', [
                    'event_id' => $event->id,
                    'team_id' => $event->team_id,
                ]);
                throw new \Exception('Event team not found');
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
                Log::error('Failed to create order', [
                    'user_id' => Auth::id(),
                    'client' => $client,
                    'order_code' => $orderCode,
                ]);
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
                    "seat_number" => $ticket->seat->seat_number,
                    "ticket_category_id" => $ticket->ticket_category_id,
                    "ticket_type" => $ticket->ticket_type,
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
                PaymentController::updateStatus($orderCode, OrderStatus::COMPLETED->value, []);
                $accessor = 'free';
            }

            $currentData = $noSQLRecord->data;
            $currentData['accessor'] = $accessor;

            $noSQLRecord->update([
                'data' => $currentData
            ]);

            $order->update(['accessor' => $accessor]);

            DB::commit();

            // Publish MQTT message about successful ticket update
            Event::publishMqtt(data: [
                'event' => "update_ticket_status",
                'data' => $updatedTickets,
            ]);

            Log::info('Payment charge successful', [
                'user_id' => Auth::id(),
                'client' => $client,
                'order_code' => $orderCode,
                'accessor' => $accessor,
            ]);

            return response()->json([
                'accessor' => $accessor,
                'transaction_id' => $orderCode,
                'updated_tickets' => $updatedTickets
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment charge failed', [
                'user_id' => Auth::id(),
                'client' => $client,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'System failed: ' . $e->getMessage()], 500);
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
            'expired_at' => now()->addHour(1),
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
        // Capture user ID early before any potential logout
        $userId = Auth::id();
        
        Log::info('Tripay charge initiated', [
            'order_code' => $orderCode,
            'total_with_tax' => $totalWithTax,
            'event_id' => $event->id,
            'user_id' => $userId,
        ]);
        
        $variables = $event->eventVariables;

        $order = Order::where('order_code', $orderCode)->first();
        if (! $order) {
            Log::error('Order not found for Tripay charge', [
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => $userId,
            ]);
            throw new \Exception('Order not found');
        }

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
        // COMMENTED OUT: Queue system is currently disabled
        // $userQueue = Event::getUser($event, $customer);

        // Calculate timeout based on user's expected_kick time
        $timeout = null;
        // COMMENTED OUT: Queue system timeout calculation - using default timeout instead
        /*
        if ($userQueue && isset($userQueue['expected_kick'])) {
            $expectedKick = Carbon::parse($userQueue['expected_kick']);

            if ($expectedKick->isPast()) {
                // If user's session has already expired, reject transaction and logout
                Event::logoutUser($event, $customer);
                Log::error('User session expired during Tripay charge', [
                    'order_code' => $orderCode,
                    'event_id' => $event->id,
                    'user_id' => $userId, // Use captured ID
                    'expected_kick' => $expectedKick->toDateTimeString(),
                ]);
                throw new \Exception('Your session has expired. Please refresh the page and try again.');
            } else {
                $timeout = $expectedKick->timestamp;
                $timeout = (int) $timeout;
            }
        } else {
            // Invalid user - log error with captured user ID before logout
            Log::error('Invalid user for Tripay charge', [
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => $userId, // Use captured ID
                'user_queue_data' => $userQueue,
                'customer_exists' => $customer ? true : false,
            ]);
            
            // Logout user after logging
            Event::logoutUser($event, $customer);
            throw new \Exception('Invalid user session. Please refresh the page and try again.');
        }
        */
        
        // ADDED: Default timeout since queue system is disabled
        $timeout = Carbon::now()->addMinutes(15)->timestamp;

        $signature = hash_hmac('sha256', $merchantCode . $orderCode . $totalWithTax, $privateKey);

        // Check if extra_data has user full name
        if (isset($request->extra_data['user_full_name'])) {
            $customerName = $request->extra_data['user_full_name'];
        } else {
            $customerName = $customer->name ?? 'Guest';
        }

        // Construct payload
        $payload = [
            "method" => "QRIS", // payment method
            "merchant_ref" => $orderCode,
            "amount" => (int) $totalWithTax,
            "customer_name" => $customerName,
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
            Log::error('Tripay charge failed', [
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => $userId, // Use captured ID
                'response' => $response->json(),
            ]);
            throw new \Exception("Tripay charge failed: " . $response->json()['message']);
        }

        // Update order expires at to follow timeout
        $timeout = Carbon::createFromTimestamp($timeout)->toDateTimeString();

        // Min timeout 12 min, max 30 min
        if (Carbon::now()->diffInMinutes($timeout) < 12) {
            $timeout = Carbon::now()->addMinutes(12)->toDateTimeString();
        } elseif (Carbon::now()->diffInMinutes($timeout) > 30) {
            $timeout = Carbon::now()->addMinutes(30)->toDateTimeString();
        }

        // Update order with timeout
        $order->update([
            'expired_at' => $timeout,
        ]);

        $responseData = $response->json()['data'];
        // Store transaction
        DevNoSQLData::create([
            'collection' => 'tripay_orders',
            'data' => array_merge([
                'order_code' => $orderCode,
                'event_id' => $event->id,
                'user_id' => $userId, // Use captured ID
                'team_id' => $event->team_id,
                'total_price' => $totalWithTax,
                'status' => OrderStatus::PENDING,
                'expired_at' => $timeout,
                'payment_gateway' => $variables->payment_gateway,
            ], $responseData),
        ]);

        Log::info('Tripay charge successful', [
            'order_code' => $orderCode,
            'event_id' => $event->id,
            'user_id' => $userId, // Use captured ID
            'redirect_url' => $responseData['checkout_url'] ?? null,
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
                    PaymentController::updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case 'pending':
                    PaymentController::updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case 'deny':
                case 'expire':
                case 'cancel':
                    PaymentController::updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
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
                    PaymentController::updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case '1':
                    PaymentController::updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case '3':
                case '4':
                case '5':
                case '7':
                case '8':
                    PaymentController::updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
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

        Log::info('Tripay callback received', [
            'identifier' => $identifier,
            'data' => $data,
        ]);

        if (!isset($identifier, $data['status'])) {
            Log::error('Invalid Tripay callback data', [
                'identifier' => $identifier,
                'data' => $data,
            ]);
            return response()->json(['error' => 'Invalid callback data'], 400);
        }

        DB::beginTransaction();
        try {
            // Process the callback based on transaction status
            switch ($data['status']) {
                case 'PAID':
                    PaymentController::updateStatus($identifier, OrderStatus::COMPLETED->value, $data);
                    break;

                case 'UNPAID':
                    PaymentController::updateStatus($identifier, OrderStatus::PENDING->value, $data);
                    break;

                case 'EXPIRED':
                case 'REFUND':
                case 'FAILED':
                    PaymentController::updateStatus($identifier, OrderStatus::CANCELLED->value, $data);
                    break;
                default:
                    throw new \Exception('Invalid status');
            }

            DB::commit();

            Log::info('Tripay callback processed successfully', [
                'identifier' => $identifier,
                'data' => $data,
            ]);
            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process Tripay callback', [
                'identifier' => $identifier,
                'error' => $e->getMessage(),
            ]);
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

        Log::info('Tripay return received', [
            'data' => $data,
        ]);

        // Validate required parameters
        $validator = Validator::make($data, [
            'tripay_merchant_ref' => 'required|string|max:32',
            'tripay_reference' => 'required|string|max:32',
        ]);

        if ($validator->fails()) {
            Log::error('Tripay return validation failed', [
                'errors' => $validator->errors(),
                'data' => $data,
            ]);
            return response()->json(['success' => false, 'error' => 'Callback return is not valid'], 404);
        }

        // Get orders from bill_no => order_code and read the client slug
        $order = Order::where('order_code', $data['tripay_merchant_ref'])->first();
        if (!$order) {
            Log::error('Tripay return order not found', [
                'tripay_merchant_ref' => $data['tripay_merchant_ref'],
                'data' => $data,
            ]);
            return response()->json(['success' => false, 'error' => 'Order not found'], 404);
        }

        // Get event
        $event = Event::find($order->event_id);
        if (!$event) {
            Log::error('Tripay return event not found', [
                'order_id' => $order->id,
                'event_id' => $order->event_id,
                'data' => $data,
            ]);
            return response()->json(['success' => false, 'error' => 'Event not found'], 404);
        }

        // Generate redirect URL
        $redirectUrl = route('client.my_tickets', ['client' => $event->slug]);

        Log::info('Tripay return redirecting', [
            'redirect_url' => $redirectUrl,
            'order_id' => $order->id,
            'event_id' => $event->id,
        ]);

        // Return redirect response with cache prevention headers
        return redirect($redirectUrl)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');
    }

    /**
     * Update order status in the database
     */
    public static function updateStatus($orderCode, $status, $transactionData)
    {
        Log::info('Updating order status', [
            'order_code' => $orderCode,
            'status' => $status,
            'transaction_data' => $transactionData,
        ]);

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
                Log::info('No status update needed', [
                    'order_code' => $orderCode,
                    'current_status' => $currentStatus,
                    'new_status' => $status,
                ]);
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
                    // determine ticketStatus by all possible OrderStatus input
                    $ticketStatus = match ($status) {
                        OrderStatus::COMPLETED->value => TicketStatus::BOOKED->value,
                        OrderStatus::PENDING->value => TicketStatus::IN_TRANSACTION->value,
                        OrderStatus::CANCELLED->value => TicketStatus::AVAILABLE->value,
                        default => $ticket->status,
                    };

                    $ticket->status = $ticketStatus;
                    $ticket->save();
                    $updatedTickets[] = [
                        "id" => $ticket->id,
                        "status" => $ticketStatus,
                        "seat_id" => $ticket->seat_id,
                        "seat_number" => $ticket->seat->seat_number,
                        "ticket_category_id" => $ticket->ticket_category_id,
                        "ticket_type" => $ticket->ticket_type,
                    ];
                }
            }

            Log::info('Order status updated', [
                'order_code' => $orderCode,
                'new_status' => $status,
                'updated_tickets' => $updatedTickets,
            ]);

            // Commit the transaction before attempting to send email
            DB::commit();

            // Send email outside of transaction to prevent rollback on email failure
            if ($status === OrderStatus::COMPLETED->value) {
                try {
                    // Get event details
                    $event = $order->getSingleEvent();

                    // Get from DB
                    $updatedTicketsObj = [];
                    foreach ($updatedTickets as $ticket) {
                        $ticketObj = Ticket::find($ticket['id']);
                        if ($ticketObj) {
                            $updatedTicketsObj[] = $ticketObj;
                        }
                    }

                    // Render the email template
                    $emailHtml = view('emails.order-confirmation', [
                        'order' => $order,
                        'event' => $event,
                        'tickets' => $updatedTicketsObj,
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
            Event::publishMqtt(data: [
                'event' => "update_ticket_status",
                'data' => $updatedTickets
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status', [
                'order_code' => $orderCode,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function getPendingTransactions(string $client = '')
    {
        try {
            Log::info('Fetching pending transactions', [
                'client' => $client,
                'user_id' => Auth::id(),
            ]);

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

            Log::info('Pending transactions fetched successfully', [
                'client' => $client,
                'user_id' => $userId,
                'count' => count($pendingTransactions),
            ]);

            return response()->json([
                'success' => true,
                'pendingTransactions' => $pendingTransactions
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch pending transactions', [
                'client' => $client,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

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
                // Call updateStatus to cancel the order
                PaymentController::updateStatus($order->order_code, OrderStatus::CANCELLED->value, []);
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
}

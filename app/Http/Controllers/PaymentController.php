<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Models\Event;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketOrder;
use Illuminate\Support\Facades\DB;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set up Midtrans configuration from a single place
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production', false);
        Config::$isSanitized = config('midtrans.is_sanitized', true);
        Config::$is3ds = config('midtrans.is_3ds', true);
    }

    /**
     * Handle payment charge requests from the frontend
     */
    public function charge(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'amount' => 'required|numeric|min:0',
                'grouped_items' => 'required',
                'tax_amount' => 'numeric',
                'total_with_tax' => 'numeric',
            ]);

            if ($validator->fails()) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Ensure user is authenticated
            if (!Auth::check()) {
                DB::rollBack();
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            // Validate host
            $hostParts = explode('.', $request->getHost());
            if (count($hostParts) < 2) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid host'], 400);
            }

            $client = $hostParts[0];
            $event = Event::where('slug', $client)->first();
            if (!$event) {
                DB::rollBack();
                return response()->json(['message' => 'Event not found'], 404);
            }

            // Check Array
            $groupedItems = $request->grouped_items;
            if (!is_array($groupedItems)) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid grouped_items format'], 422);
            }

            // Get seats
            $seats = collect();
            foreach ($groupedItems as $category => $item) {
                if (!empty($item['seatNumbers'])) {
                    $seats = $seats->merge(
                        Seat::whereIn('seat_number', $item['seatNumbers'])
                            ->where('venue_id', $event->venue->venue_id)
                            ->get()
                    );
                }
            }
            if ($seats->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'No seats available'], 400);
            }

            $seatIds = $seats->pluck('seat_id')->toArray();
            $tickets = Ticket::whereIn('seat_id', $seatIds)
                ->where('event_id', $event->event_id)
                ->where('status', 'available')
                ->distinct()
                ->lockForUpdate()
                ->get();

            // Generate order ID
            $orderCode = Order::keyGen(OrderType::AUTO);

            // Prepare transaction parameters
            $itemDetails = [];
            foreach ($groupedItems as $category => $item) {
                $seatLabel = isset($item['seatNumbers']) ? ' (' . implode(', ', $item['seatNumbers']) . ')' : '';
                $itemDetails[] = [
                    'id' => 'TICKET-' . strtoupper($category),
                    'price' => (int)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'name' => ucfirst($category) . ' Ticket' . $seatLabel,
                ];
            }

            // Calculate total price with tax
            $amount = (int)$request->amount;
            $defaultTaxRate = config('app.default_tax_rate', 1);
            $taxAmount = (int)($request->tax_amount ?? ($amount * $defaultTaxRate / 100));
            $totalWithTax = $amount + $taxAmount;

            // Add tax item
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'TAX-' . $defaultTaxRate . 'PCT',
                    'price' => $taxAmount,
                    'quantity' => 1,
                    'name' => 'Tax (' . $defaultTaxRate . '%)',
                ];
            }

            $team = Team::where('team_id', $event->team_id)->first();
            if (!$team) {
                DB::rollBack();
                return response()->json(['message' => 'Team not found'], 404);
            }

            // Validate ticket availability
            if ($tickets->count() !== $seats->count()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to lock seats'], 500);
            }

            // Lock tickets
            $tickets->each(fn($ticket) => $ticket->update(['status' => TicketStatus::IN_TRANSACTION]));

            // Create order
            $order = Order::create([
                'order_code' => $orderCode,
                'event_id' => $event->event_id,
                'id' => Auth::id(),
                'team_id' => $team->team_id,
                'order_date' => now(),
                'total_price' => $totalWithTax,
                'status' => OrderStatus::PENDING,
            ]);

            if (!$order) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to create order'], 500);
            }

            // Create ticket orders
            $ticketOrders = [];
            foreach ($tickets as $ticket) {
                $ticketOrders[] = TicketOrder::create([
                    'ticket_id' => $ticket->ticket_id,
                    'order_id' => $order->order_id,
                    'event_id' => $event->event_id,
                    'status' => TicketOrderStatus::ENABLED,
                ]);
            }

            if (count($ticketOrders) !== $tickets->count()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to create ticket orders'], 500);
            }

            // Get Midtrans Snap Token
            $snapToken = Snap::getSnapToken([
                'transaction_details' => ['order_id' => $orderCode, 'gross_amount' => $totalWithTax],
                'credit_card' => ['secure' => true],
                'customer_details' => ['email' => $request->email],
                'item_details' => $itemDetails,
            ]);

            if (!$snapToken) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to get Snap token'], 500);
            }

            DB::commit();
            return response()->json(['snap_token' => $snapToken, 'transaction_id' => $orderCode]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error processing payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Midtrans payment callbacks
     */
    public function midtransCallback(Request $request)
    {
        $data = $request->all();

        DB::beginTransaction();
        try {
            $identifier = $data['order_id'] ?? null;
            if (!isset($identifier, $data['gross_amount'], $data['transaction_status'])) {
                return response()->json(['error' => 'Invalid callback data'], 400);
            }

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
            Log::error('Failed to process callback', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            return response()->json(['error' => 'Failed to process callback'], 500);
        }
    }

    /**
     * Update order status in the database
     */
    private function updateStatus($orderCode, $status, $transactionData)
    {
        try {
            // Find order first
            $order = Order::where('order_code', $orderCode)->first();

            if (!$order) {
                Log::warning('Order not found', ['order_code' => $orderCode]);
                throw new \Exception('Order not found: ' . $orderCode);
            }

            // Update order status
            $order->status = $status;
            $order->save();

            // Update ticket statuses
            $ticketOrders = TicketOrder::where('order_id', $order->order_id)->get();
            foreach ($ticketOrders as $ticketOrder) {
                $ticket = Ticket::find($ticketOrder->ticket_id);
                if ($ticket) { // Ensure the ticket exists before updating
                    $ticket->status = $status === OrderStatus::COMPLETED->value ? TicketStatus::BOOKED->value : TicketStatus::AVAILABLE->value;
                    $ticket->save();
                } else {
                    Log::warning('Ticket not found', ['ticket_id' => $ticketOrder->ticket_id]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to update order status', [
                'order_code' => $orderCode,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw the exception to ensure the outer transaction rolls back
        }
    }

    public function getPendingTransactions(Request $request)
    {
        try {
            $userId = Auth::id();

            // Tambahkan logging untuk debugging
            Log::info('Fetching pending transactions', [
                'user_id' => $userId,
                'host' => $request->getHost(),
                'client' => $request->route('client')
            ]);

            // Get the client's event
            $client = $request->route('client');
            if (!$client) {
                // Try to extract from host as fallback
                $hostParts = explode('.', $request->getHost());
                $client = $hostParts[0] !== 'api' ? $hostParts[0] : null;

                if (!$client) {
                    Log::error('Client identifier not found', [
                        'host' => $request->getHost()
                    ]);
                    return response()->json(['success' => false, 'error' => 'Client identifier not found'], 400);
                }
            }

            $event = Event::where('slug', $client)->first();
            if (!$event) {
                Log::error('Event not found for client', ['client' => $client]);
                return response()->json(['error' => 'Event not found'], 404);
            }

            // Get pending orders for this user and event
            // PERBAIKAN: Logging sebelum query utama
            Log::info('Querying pending orders', [
                'user_id' => $userId,
                'event_id' => $event->event_id
            ]);

            // PERBAIKAN: Gunakan query builder dengan kondisi yang benar
            $pendingOrders = Order::where('user_id', $userId)  // Perhatikan: kolom 'id' merujuk ke user_id
                ->where('event_id', $event->event_id)
                ->where('status', OrderStatus::PENDING)
                ->get();

            // PERBAIKAN: Logging hasil query
            Log::info('Pending orders found', [
                'count' => $pendingOrders->count(),
                'orders' => $pendingOrders->pluck('order_code')->toArray()
            ]);

            $pendingTransactions = [];

            foreach ($pendingOrders as $order) {
                // Get tickets for this order
                $ticketOrders = TicketOrder::where('order_id', $order->order_id)->get();
                $ticketIds = $ticketOrders->pluck('ticket_id');

                $tickets = Ticket::whereIn('ticket_id', $ticketIds)->get();
                $seatIds = $tickets->pluck('seat_id');

                // Get seats
                $seats = Seat::whereIn('seat_id', $seatIds)->get();

                $seatsData = $seats->map(function ($seat) use ($tickets) {
                    $ticket = $tickets->where('seat_id', $seat->seat_id)->first();

                    // PERBAIKAN: Tambahkan pengecekan agar tidak error jika ticket null
                    if (!$ticket) {
                        Log::warning('Ticket not found for seat', ['seat_id' => $seat->seat_id]);
                        return null;
                    }

                    return [
                        'seat_id' => $seat->seat_id,
                        'seat_number' => $seat->seat_number,
                        'row' => $seat->row,
                        'column' => $seat->column,
                        'ticket_type' => $ticket->ticket_type,
                        'status' => $ticket->status,
                        'price' => $ticket->price,
                        'type' => 'seat',
                    ];
                })->filter()->values(); // PERBAIKAN: Filter null values dan reset index array

                $pendingTransactions[] = [
                    'order_id' => $order->order_id,
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
            Log::error('Failed to fetch pending transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch pending transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resumePayment(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $orderCode = $request->transaction_id;

            // PERBAIKAN: Tambahkan logging untuk debugging
            Log::info('Attempting to resume payment', [
                'order_code' => $orderCode,
                'user_id' => Auth::id()
            ]);

            // PERBAIKAN: Cek apakah order ada di database dengan kondisi yang tepat
            $order = Order::where('order_code', $orderCode)
                ->where('user_id', Auth::id())  // Pastikan order milik user yang sedang login
                ->where('status', OrderStatus::PENDING)
                ->first();

            if (!$order) {
                // PERBAIKAN: Logging lebih detail jika order tidak ditemukan
                $existingOrder = Order::where('order_code', $orderCode)->first();
                if ($existingOrder) {
                    Log::warning('Order found but not eligible for resume', [
                        'order_code' => $orderCode,
                        'user_id' => Auth::id(),
                        'actual_user_id' => $existingOrder->id,
                        'status' => $existingOrder->status
                    ]);
                } else {
                    Log::warning('Order not found in database', [
                        'order_code' => $orderCode
                    ]);
                }

                return response()->json(['message' => 'Transaction not found or already completed'], 404);
            }

            // PERBAIKAN PENTING: Jika transaksi ditemukan di localStorage tapi tidak di database
            // Periksa apakah localStorage masih memiliki transaksi yang valid
            // Ini bisa ditambahkan di frontend dengan mengirim timestamp transaksi

            // Get ticket orders for this order
            $ticketOrders = TicketOrder::where('order_id', $order->order_id)->get();

            // PERBAIKAN: Periksa apakah ada ticket orders
            if ($ticketOrders->isEmpty()) {
                Log::warning('No ticket orders found for this order', [
                    'order_id' => $order->order_id
                ]);
                return response()->json(['message' => 'Invalid order: no tickets found'], 400);
            }

            $ticketIds = $ticketOrders->pluck('ticket_id');
            $tickets = Ticket::whereIn('ticket_id', $ticketIds)->get();

            // Prepare item details for Midtrans
            $itemDetails = [];
            $ticketsByType = $tickets->groupBy('ticket_type');

            foreach ($ticketsByType as $type => $typeTickets) {
                $seatNumbers = $typeTickets->map(function ($ticket) {
                    $seat = Seat::where('seat_id', $ticket->seat_id)->first();
                    return $seat ? $seat->seat_number : '';
                })->filter()->toArray();

                $seatLabel = !empty($seatNumbers) ? ' (' . implode(', ', $seatNumbers) . ')' : '';

                $itemDetails[] = [
                    'id' => 'TICKET-' . strtoupper($type),
                    'price' => (int)$typeTickets->first()->price,
                    'quantity' => $typeTickets->count(),
                    'name' => ucfirst($type) . ' Ticket' . $seatLabel,
                ];
            }

            // Add tax item if applicable
            $taxAmount = $order->total_price * 0.01; // Assuming 1% tax
            if ($taxAmount > 0) {
                $itemDetails[] = [
                    'id' => 'TAX-1PCT',
                    'price' => (int)$taxAmount,
                    'quantity' => 1,
                    'name' => 'Tax (1%)',
                ];
            }

            // Get user email
            $userEmail = Auth::user()->email ?? 'customer@example.com';

            // Use original order_code - PERBAIKAN UTAMA
            Log::info('Generating Snap token for order', [
                'order_code' => $orderCode,
                'amount' => (int)$order->total_price
            ]);

            $snapToken = Snap::getSnapToken([
                'transaction_details' => [
                    'order_id' => $orderCode, // Gunakan order_code asli
                    'gross_amount' => (int)$order->total_price
                ],
                'credit_card' => ['secure' => true],
                'customer_details' => ['email' => $userEmail],
                'item_details' => $itemDetails,
            ]);

            if (!$snapToken) {
                Log::error('Failed to get Snap token from Midtrans');
                return response()->json(['message' => 'Failed to get Snap token'], 500);
            }

            Log::info('Successfully generated Snap token', [
                'order_code' => $orderCode
            ]);

            return response()->json([
                'snap_token' => $snapToken,
                'transaction_id' => $orderCode,
            ]);
        } catch (\Exception $e) {
            Log::error('Error resuming payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error resuming payment: ' . $e->getMessage()
            ], 500);
        }
    }
}

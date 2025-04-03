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

class PaymentController extends Controller
{
    // public function __construct()
    // {
    // Set up Midtrans configuration from a single place
    // Config::$serverKey = config('midtrans.server_key');
    // Config::$isProduction = config('midtrans.is_production', false);
    // Config::$isSanitized = config('midtrans.is_sanitized', true);
    // Config::$is3ds = config('midtrans.is_3ds', true);
    // }

    /**
     * Handle payment charge requests from the frontend
     */
    // disini nanti taro eventnya
    public function charge(Request $request, string $client = "")
    {
        // Check if there's too much orders frequently, reject
        $timeSpan = now()->subHour();
        $orderCount = Order::where('user_id', Auth::id())
            ->where('order_date', '>=', $timeSpan)
            ->count();
        $limitPerTimeSpan = 5;

        if ($orderCount >= $limitPerTimeSpan) {
            return response()->json(['message' => 'Too many orders in a short time. Please wait for 1 hour to do the next transaction.'], 429);
        }

        $secondTimeSpan = now()->subDay();
        $orderCountSecond = Order::where('user_id', Auth::id())
            ->where('order_date', '>=', $secondTimeSpan)
            ->count();
        $limitPerTimeSpanSecond = 10;

        if ($orderCountSecond >= $limitPerTimeSpanSecond) {
            return response()->json(['message' => 'Too many orders in a short time. Please wait for 1 day to do the next transaction.'], 429);
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'amount' => 'required|numeric|min:0',
            'grouped_items' => 'required',
            'tax_amount' => 'numeric',
            'total_with_tax' => 'numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Ensure user is authenticated
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $event = Event::where('slug', $client)->first();
        if (!$event) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        // Check if there's still existing order
        $existingOrders = User::find(Auth::id())
            ->orders()
            ->where('event_id', $event->event_id)
            ->where('status', OrderStatus::PENDING)
            ->get();

        if ($existingOrders->isNotEmpty()) {
            return response()->json(['message' => 'There is an existing pending order'], 400);
        }

        // Check Array
        $groupedItems = $request->grouped_items;
        if (!is_array($groupedItems)) {
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
            return response()->json(['message' => 'No seats available'], 400);
        }

        DB::beginTransaction();
        try {
            $seatIds = $seats->pluck('seat_id')->toArray();
            $tickets = Ticket::whereIn('seat_id', $seatIds)
                ->where('event_id', $event->event_id)
                ->where('status', 'available')
                ->distinct()
                ->lockForUpdate()
                ->get();

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

            // Generate order ID
            $orderCode = Order::keyGen(OrderType::AUTO, $event);

            // Create order
            $order = Order::create([
                'order_code' => $orderCode,
                'event_id' => $event->event_id,
                'user_id' => Auth::id(),
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
            Config::$serverKey = $event->eventVariables->getKey('server');
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

            // Update snap_token to order
            $order->snap_token = $snapToken;
            $order->save();

            DB::commit();
            return response()->json(['snap_token' => $snapToken, 'transaction_id' => $orderCode]);
        } catch (\Exception $e) {
            DB::rollBack();
            $responseString = $e->getMessage();
            preg_match('/"error_messages":\["(.*?)"/', $responseString, $matches);
            $firstErrorMessage = $matches[1] ?? null;
            return response()->json(['message' => 'System failed to process payment! ' . $firstErrorMessage . '.'], 500);
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

            // Update ticket statuses
            $ticketOrders = TicketOrder::where('order_id', $order->order_id)->get();
            foreach ($ticketOrders as $ticketOrder) {
                $ticket = Ticket::find($ticketOrder->ticket_id);
                if ($ticket) { // Ensure the ticket exists before updating
                    $ticket->status = $status === OrderStatus::COMPLETED->value ? TicketStatus::BOOKED->value : TicketStatus::AVAILABLE->value;
                    $ticket->save();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Re-throw the exception to ensure the outer transaction rolls back
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
                ->where('event_id', $event->event_id)
                ->where('status', OrderStatus::PENDING)
                ->get();

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
                        return null;
                    }

                    return [
                        'seat_id' => $seat->seat_id,
                        'seat_number' => $seat->seat_number,
                        'ticket_type' => $ticket->ticket_type,
                        'category' => $ticket->ticketCategory,
                        'status' => $ticket->status,
                        'price' => $ticket->price,
                    ];
                })->filter()->values(); // PERBAIKAN: Filter null values dan reset index array

                $pendingTransactions[] = [
                    'snap_token' => $order->snap_token,
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
            $orders = Order::whereIn('order_id', $orderIds)
                ->where('user_id', Auth::id())
                ->where('event_id', $event->event_id)
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
                $ticketOrders = TicketOrder::where('order_id', $order->order_id)->get();
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

            return response()->json(['client_key' => $clientKey]);
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

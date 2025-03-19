<?php

namespace App\Http\Controllers;

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

            // Check Array
            $groupedItems = $request->grouped_items;
            if (!is_array($groupedItems)) {
                DB::rollBack();
                return response()->json(['message' => 'Invalid grouped_items format'], 422);
            }

            // Generate order ID
            $orderId = 'ORDER-' . time() . '-' . rand(1000, 9999);

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

            $team = Team::where('team_id', $event->team_id)->first();
            if (!$team) {
                DB::rollBack();
                return response()->json(['message' => 'Team not found'], 404);
            }

            // Lock seats
            $seats = collect();
            foreach ($groupedItems as $category => $item) {
                if (!empty($item['seatNumbers'])) {
                    $seats = $seats->merge(
                        Seat::whereIn('seat_number', $item['seatNumbers'])
                            ->where('venue_id', $event->venue->venue_id)
                            ->lockForUpdate()
                            ->get()
                    );
                }
            }
            if ($seats->isEmpty()) {
                DB::rollBack();
                return response()->json(['message' => 'No seats available'], 400);
            }

            // Validate ticket availability
            $seatIds = $seats->pluck('seat_id')->toArray();
            $tickets = Ticket::whereIn('seat_id', $seatIds)
                ->where('event_id', $event->event_id)
                ->where('status', 'available')
                ->distinct()
                ->get();

            if ($tickets->count() !== $seats->count()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to lock seats'], 500);
            }

            // Lock tickets
            $tickets->each(fn($ticket) => $ticket->update(['status' => 'in_transaction']));

            // Create order
            $order = Order::create([
                'order_id' => $orderId,
                'user_id' => Auth::id(),
                'team_id' => $team->team_id,
                'order_date' => now(),
                'total_price' => $totalWithTax,
                'status' => 'pending',
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
                    'order_id' => $orderId,
                    'event_id' => $event->event_id,
                ]);
            }

            if (count($ticketOrders) !== $tickets->count()) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to create ticket orders'], 500);
            }

            // Get Midtrans Snap Token
            $snapToken = Snap::getSnapToken([
                'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $totalWithTax],
                'credit_card' => ['secure' => true],
                'customer_details' => ['email' => $request->email],
                'item_details' => $itemDetails,
            ]);

            if (!$snapToken) {
                DB::rollBack();
                return response()->json(['message' => 'Failed to get Snap token'], 500);
            }

            DB::commit();
            return response()->json(['snap_token' => $snapToken, 'transaction_id' => $orderId]);
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
            if (!isset($data['order_id'], $data['gross_amount'], $data['transaction_status'])) {
                return response()->json(['error' => 'Invalid callback data'], 400);
            }

            // Process the callback based on transaction status
            switch ($data['transaction_status']) {
                case 'capture':
                case 'settlement':
                    // Payment success - update order status
                    $this->updateStatus($data['order_id'], 'paid', $data);
                    break;

                case 'pending':
                    // Payment pending
                    $this->updateStatus($data['order_id'], 'pending', $data);
                    break;

                case 'deny':
                case 'expire':
                case 'cancel':
                    // Payment failed or canceled
                    $this->updateStatus($data['order_id'], 'failed', $data);
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
    private function updateStatus($orderId, $status, $transactionData)
    {
        // Implement your order update logic here
        DB::beginTransaction();
        try {
            Order::where('order_id', $orderId)->update([
                'status' => $status
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update order status', [
                'order_id' => $orderId,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function callback(Request $request)
    {
        // Log::info('Midtrans Callback Received', $request->all());
        return response()->json(['message' => 'Callback received']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Team;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function __construct()
    {
        // Set up Midtrans configuration from a single place
        // Use the same config key in both __construct and charge method
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = config('services.midtrans.is_production', false);
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Log Midtrans configuration for debugging
        Log::info('Midtrans Configuration Loaded', [
            'server_key_exists' => !empty(Config::$serverKey),
            'is_production' => Config::$isProduction,
        ]);
    }

    /**
     * Handle payment charge requests from the frontend
     */
    public function charge(Request $request)
    {
        try {
            // Log full request for debugging
            Log::info('Payment request received', [
                'method' => $request->method(),
                'all' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Validate the request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'amount' => 'required|numeric|min:0',
                'grouped_items' => 'required',
            ]);

            if ($validator->fails()) {
                Log::warning('Payment validation failed', ['errors' => $validator->errors()]);
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Parse grouped_items if it's a string (from form submission)
            $groupedItems = $request->grouped_items;
            if (is_string($groupedItems)) {
                $groupedItems = json_decode($groupedItems, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Failed to decode grouped_items JSON', ['error' => json_last_error_msg()]);
                    return response()->json([
                        'message' => 'Invalid grouped_items format',
                        'error' => json_last_error_msg()
                    ], 422);
                }
            }

            // Generate a unique order ID
            $orderId = 'ORDER-' . time() . '-' . rand(1000, 9999);

            // Prepare transaction parameters
            $itemDetails = [];
            foreach ($groupedItems as $category => $item) {
                // Format seat numbers if available
                $seatLabel = '';
                if (isset($item['seatNumbers']) && !empty($item['seatNumbers'])) {
                    $seatLabel = ' (' . implode(', ', $item['seatNumbers']) . ')';
                }

                $itemDetails[] = [
                    'id' => 'TICKET-' . strtoupper($category),
                    'price' => (int)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'name' => ucfirst($category) . ' Ticket' . $seatLabel,
                ];
            }

            // Make sure to convert amount to integer (Midtrans requirement)
            $amount = (int)$request->amount;

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'credit_card' => [
                    'secure' => true,
                ],
                'customer_details' => [
                    'email' => $request->email,
                ],
                'item_details' => $itemDetails,
            ];

            // Log the request to Midtrans
            Log::info('Sending request to Midtrans', [
                'params' => $params,
                'server_key_exists' => !empty(Config::$serverKey),
            ]);

            // Get Snap Token from Midtrans
            $snapToken = Snap::getSnapToken($params);
            Log::info('Midtrans response received', ['snap_token' => $snapToken ? 'received' : 'null']);

            // Return the response
            return response()->json([
                'snap_token' => $snapToken,
                'transaction_id' => $orderId,
                'client_key' => config('services.midtrans.client_key'),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in payment charge: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Payment processing failed: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    /**
     * Handle Midtrans payment callbacks
     */
    public function midtransCallback(Request $request)
    {
        $data = $request->all();
        
        Log::info('Midtrans callback received', ['data' => $data]);

        try {
            if (!isset($data['order_id'], $data['gross_amount'], $data['transaction_status'])) {
                return response()->json(['error' => 'Invalid callback data'], 400);
            }

            // Process the callback based on transaction status
            switch ($data['transaction_status']) {
                case 'capture':
                case 'settlement':
                    // Payment success - update order status
                    $this->updateOrderStatus($data['order_id'], 'paid', $data);
                    break;
                    
                case 'pending':
                    // Payment pending
                    $this->updateOrderStatus($data['order_id'], 'pending', $data);
                    break;
                    
                case 'deny':
                case 'expire':
                case 'cancel':
                    // Payment failed or canceled
                    $this->updateOrderStatus($data['order_id'], 'failed', $data);
                    break;
            }

            return response()->json(['message' => 'Callback processed successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error processing payment callback: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            
            return response()->json(['error' => 'Failed to process callback'], 500);
        }
    }

    /**
     * Update order status in the database
     */
    private function updateOrderStatus($orderId, $status, $transactionData)
    {
        Log::info('Updating order status', [
            'order_id' => $orderId,
            'status' => $status,
        ]);

        // Implement your order update logic here
        try {
            // Add code to update your order in the database
            // Example:
            // Order::where('transaction_id', $orderId)
            //     ->update(['status' => $status]);
        } catch (\Exception $e) {
            Log::error('Failed to update order: ' . $e->getMessage());
        }
    }
}
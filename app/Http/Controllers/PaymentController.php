<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Team;
use Exception;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = config('midtrans.is_sanitized');
        Config::$is3ds = config('midtrans.is_3ds');
    }

    public function createCharge(Request $request)
    {
        $itemDetails = collect($request->grouped_items)->map(function ($details, $category) {
            return [
                'id' => $category,
                'price' => $details['price'],
                'quantity' => $details['quantity'],
                'name' => $category . ' (' . implode(', ', $details['seatNumbers']) . ')',
            ];
        })->values()->toArray();

        $params = [
            'transaction_details' => [
                'order_id' => 'order_' . rand(),
                'gross_amount' => $request->amount,
            ],
            'credit_card' => [
                'secure' => true,
            ],
            'customer_details' => [
                'email' => $request->email,
            ],
            'item_details' => $itemDetails,
            // 'callbacks' => [
            //     'finish' => (config('app.env') !== 'development' ? config('app.fe_url') : '') . 'myticket',
            // ],
        ];

        // Mengambil Snap Token
        try {
            $snapToken = Snap::getSnapToken($params);
            return response()->json(['snap_token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function midtransCallback(Request $request)
    {
        $data = $request->all();
    
        try {
            if (!isset($data['order_id'], $data['gross_amount'], $data['transaction_status'])) {
                return response()->json(['error' => 'Invalid request data'], 400);
            }
    
            $team = Team::first();
            $coupon = Coupon::first();
    
            if (!$team || !$coupon) {
                return response()->json(['error' => 'Team or Coupon not found'], 404);
            }
    
            // Buat order baru
            Order::create([
                'user_id' => auth()->user_id ?? null,
                'team_id' => $team->id,
                'coupon_id' => $coupon->id,
                'order_date' => $data[''],
                'total_price' => $data['gross_amount'],
                'status' => $data['transaction_status']
            ]);
    
            return response()->json(['message' => 'Order successfully created'], 201);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
}

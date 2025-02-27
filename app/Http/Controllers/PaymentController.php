<?php

namespace App\Http\Controllers;

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
}

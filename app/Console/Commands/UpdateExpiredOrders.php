<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Http\Controllers\PaymentController;
use Illuminate\Console\Command;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateExpiredOrders extends Command
{
    protected $signature = 'orders:update-expired';
    protected $description = 'Update the status of orders that have exceeded their deadline';

    public function handle()
    {
        try {
            DB::beginTransaction();
            $expiredOrders = Order::where('status', OrderStatus::PENDING->value)
                ->where('expired_at', '<', Carbon::now())->get();

            $count = $expiredOrders->count();

            foreach ($expiredOrders as $order) {
                PaymentController::updateStatus(
                    $order->id,
                    OrderStatus::CANCELLED->value,
                    []
                );
            }

            if ($count > 0) {
                $this->info("Updated $count expired orders to 'Expired' status.");
            } else {
                $this->info("No expired orders found.");
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred while updating expired orders: " . $e->getMessage());
        }
    }
}

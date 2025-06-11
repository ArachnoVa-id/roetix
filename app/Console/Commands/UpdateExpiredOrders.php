<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdateExpiredOrdersJob;
use App\Enums\OrderStatus;
use App\Http\Controllers\PaymentController;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class UpdateExpiredOrders extends Command
{
    protected $signature = 'orders:update-expired {--continuous : Run continuously via jobs}';
    protected $description = 'Update the status of orders that have exceeded their deadline';

    public function handle()
    {
        if ($this->option('continuous')) {
            $this->info('Starting continuous expired orders update via queue jobs...');
            // Dispatch the first job, which will schedule subsequent ones
            UpdateExpiredOrdersJob::dispatch();
            $this->info('Job dispatched. Use "php artisan queue:work" to process jobs.');
        } else {
            // Original synchronous behavior for manual testing
            $this->info('Running one-time expired orders update...');
            try {
                if (cache()->lock('update_expired_orders_lock', 10)->get()) {
                    try {
                        $this->processExpiredOrders();
                        $this->info("Successfully updated expired orders");
                    } finally {
                        cache()->forget('update_expired_orders_lock');
                    }
                } else {
                    $this->warn("UpdateExpiredOrders skipped: lock is currently held by another process.");
                }
            } catch (\Throwable $e) {
                $this->error("Failed to update expired orders: " . $e->getMessage());
            }
        }

        return 0;
    }

    private function processExpiredOrders(): void
    {
        try {
            DB::beginTransaction();

            $expiredOrders = Order::where('status', OrderStatus::PENDING->value)
                ->where('expired_at', '<', Carbon::now())
                ->get();

            $count = $expiredOrders->count();

            foreach ($expiredOrders as $order) {
                $this->processExpiredOrder($order);
            }

            $this->displayResults($count);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processExpiredOrder(Order $order): void
    {
        $event = $order->getSingleEvent();

        if (!$event || !$event->eventVariables) {
            $this->cancelOrder($order);
            return;
        }

        if ($order->payment_gateway === 'tripay' && $this->isTripayOrderPaid($order, $event->eventVariables)) {
            $this->info("Order {$order->id} is already paid, skipping update.");
            PaymentController::updateStatus($order->order_code, OrderStatus::COMPLETED->value, []);
            return;
        }

        $this->cancelOrder($order);
    }

    private function isTripayOrderPaid(Order $order, $variables): bool
    {
        try {
            $tripayApiKey = $this->getTripayApiKey($variables);
            $baseUrl = $variables->tripay_is_production ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
            $endpoint = $baseUrl . '/transaction/detail';
            $orderRef = str_replace('https://tripay.co.id/checkout/', '', $order->accessor);

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $tripayApiKey])
                ->get($endpoint . '?reference=' . $orderRef);

            return $response['data']['status'] === 'PAID';
        } catch (\Exception $e) {
            $this->error("Error checking Tripay payment status for order {$order->id}: " . $e->getMessage());
            return false;
        }
    }

    private function getTripayApiKey($variables): string
    {
        if ($variables->tripay_use_novatix) {
            return $variables->tripay_is_production
                ? config('tripay.api_key')
                : config('tripay.api_key_sb');
        }

        return $variables->tripay_is_production
            ? Crypt::decryptString($variables->tripay_api_key_prod)
            : Crypt::decryptString($variables->tripay_api_key_dev);
    }

    private function cancelOrder(Order $order): void
    {
        PaymentController::updateStatus($order->order_code, OrderStatus::CANCELLED->value, []);
    }

    private function displayResults(int $count): void
    {
        if ($count > 0) {
            $this->info("Updated $count expired orders.");
        } else {
            $this->info("No expired orders found.");
        }
    }
}

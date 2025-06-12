<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Http\Controllers\PaymentController;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateExpiredOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 1;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle()
    {
        Log::info('UpdateExpiredOrdersJob started');

        try {
            // Use a global lock to prevent concurrent execution
            $lockKey = 'update_expired_orders_lock';

            if (cache()->lock($lockKey, 60)->get()) {
                try {
                    $this->updateExpiredOrders();
                } finally {
                    cache()->forget($lockKey);
                }
            } else {
                Log::warning("UpdateExpiredOrdersJob skipped: lock held by another process");
            }
        } catch (\Throwable $e) {
            Log::error("UpdateExpiredOrdersJob failed: " . $e->getMessage());
            throw $e;
        }

        // Schedule the next job to run in 1 minute (like AdjustUsersJob pattern)
        self::dispatch()->delay(now()->addMinutes(1));

        Log::info('UpdateExpiredOrdersJob finished');
    }

    private function updateExpiredOrders(): void
    {
        try {
            DB::beginTransaction();

            $expiredOrders = Order::where('status', OrderStatus::PENDING->value)
                ->where('expired_at', '<', Carbon::now())
                ->get();

            $count = $expiredOrders->count();

            Log::info("Found {$count} expired orders to process");

            foreach ($expiredOrders as $order) {
                $this->processExpiredOrder($order);
            }

            $this->logResults($count);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error updating expired orders: " . $e->getMessage());
            throw $e;
        }
    }

    private function processExpiredOrder(Order $order): void
    {
        try {
            $event = $order->getSingleEvent();

            if (!$event || !$event->eventVariables) {
                $this->cancelOrder($order);
                return;
            }

            if ($order->payment_gateway === 'tripay' && $this->isTripayOrderPaid($order, $event->eventVariables)) {
                Log::info("Order {$order->id} is already paid, updating to completed.");
                PaymentController::updateStatus($order->order_code, OrderStatus::COMPLETED->value, []);
                return;
            }

            $this->cancelOrder($order);
        } catch (\Exception $e) {
            Log::error("Error processing expired order {$order->id}: " . $e->getMessage());
            // Continue processing other orders even if one fails
        }
    }

    private function isTripayOrderPaid(Order $order, $variables): bool
    {
        try {
            $tripayApiKey = $this->getTripayApiKey($variables);
            $baseUrl = $variables->tripay_is_production ? 'https://tripay.co.id/api' : 'https://tripay.co.id/api-sandbox';
            $endpoint = $baseUrl . '/transaction/detail';
            $orderRef = str_replace('https://tripay.co.id/checkout/', '', $order->accessor);

            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $tripayApiKey])
                ->timeout(30)
                ->get($endpoint . '?reference=' . $orderRef);

            if ($response->successful()) {
                return $response['data']['status'] === 'PAID';
            }

            Log::warning("Tripay API request failed for order {$order->id}: " . $response->status());
            return false;
        } catch (\Exception $e) {
            Log::error("Error checking Tripay payment status for order {$order->id}: " . $e->getMessage());
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
        try {
            PaymentController::updateStatus($order->order_code, OrderStatus::CANCELLED->value, []);
            Log::info("Order {$order->id} cancelled successfully");
        } catch (\Exception $e) {
            Log::error("Error cancelling order {$order->id}: " . $e->getMessage());
            throw $e;
        }
    }

    private function logResults(int $count): void
    {
        if ($count > 0) {
            Log::info("Updated {$count} expired orders.");
        } else {
            Log::info("No expired orders found.");
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('UpdateExpiredOrdersJob failed permanently: ' . $exception->getMessage());

        // Restart the cycle after a delay
        self::dispatch()->delay(now()->addMinutes(5));
    }
}

<?php

namespace App\Jobs;

use App\Models\SeatTransaction;
use App\Models\Seat;
use App\Events\TransactionUpdated;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ClearExpiredTransactions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        try {
            DB::beginTransaction();

            $expiredTransactions = SeatTransaction::where('status', 'pending')
                ->where('expiry_time', '<', now())
                ->get();

            foreach ($expiredTransactions as $transaction) {
                $transaction->update(['status' => 'expired']);

                $seat = Seat::find($transaction->seat_id);
                if ($seat) {
                    $seat->update(['status' => 'available']);
                    broadcast(new TransactionUpdated($transaction, $seat))->toOthers();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to clear expired transactions: ' . $e->getMessage());
        }
    }
}
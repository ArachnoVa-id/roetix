<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SeatTransaction;
use App\Models\Seat;
use App\Events\TransactionUpdated;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitorTransactions extends Command
{
    protected $signature = 'transactions:monitor';
    protected $description = 'Monitor and cleanup expired transactions';

    public function handle()
    {
        $this->info('Starting transaction monitoring...');

        while (true) {
            try {
                DB::beginTransaction();

                // Find expired transactions
                $expiredTransactions = SeatTransaction::where('status', 'pending')
                    ->where('expiry_time', '<', Carbon::now())
                    ->get();

                foreach ($expiredTransactions as $transaction) {
                    $this->info("Processing expired transaction: {$transaction->transaction_id}");

                    // Update transaction status
                    $transaction->update(['status' => 'expired']);

                    // Release the seat
                    $seat = Seat::find($transaction->seat_id);
                    if ($seat) {
                        $seat->update(['status' => 'available']);

                        // Broadcast update
                        broadcast(new TransactionUpdated($transaction, $seat))->toOthers();
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error processing transactions: {$e->getMessage()}");
            }

            // Wait for 5 seconds before next check
            sleep(5);
        }
    }
}
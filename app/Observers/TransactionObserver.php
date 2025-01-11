<?php

namespace App\Observers;

use App\Models\SeatTransaction;
use App\Models\TransactionLog;

class TransactionObserver
{
    /**
     * Handle the SeatTransaction "created" event.
     */
    public function created(SeatTransaction $transaction)
    {
        TransactionLog::create([
            'transaction_id' => $transaction->transaction_id,
            'seat_id' => $transaction->seat_id,
            'user_id' => $transaction->user_id,
            'action' => 'created',
            'previous_status' => null,
            'new_status' => $transaction->status,
            'metadata' => [
                'expiry_time' => $transaction->expiry_time
            ]
        ]);
    }

    public function updated(SeatTransaction $transaction)
    {
        if ($transaction->isDirty('status')) {
            TransactionLog::create([
                'transaction_id' => $transaction->transaction_id,
                'seat_id' => $transaction->seat_id,
                'user_id' => $transaction->user_id,
                'action' => 'status_changed',
                'previous_status' => $transaction->getOriginal('status'),
                'new_status' => $transaction->status,
                'metadata' => [
                    'expiry_time' => $transaction->expiry_time,
                    'updated_at' => now()
                ]
            ]);
        }
    }

    /**
     * Handle the SeatTransaction "deleted" event.
     */
    public function deleted(SeatTransaction $seatTransaction): void
    {
        //
    }

    /**
     * Handle the SeatTransaction "restored" event.
     */
    public function restored(SeatTransaction $seatTransaction): void
    {
        //
    }

    /**
     * Handle the SeatTransaction "force deleted" event.
     */
    public function forceDeleted(SeatTransaction $seatTransaction): void
    {
        //
    }
}

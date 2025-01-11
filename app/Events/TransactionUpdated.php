<?php

namespace App\Events;

use App\Models\Seat;
use App\Models\SeatTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public $seat;

    public function __construct(SeatTransaction $transaction, Seat $seat)
    {
        $this->transaction = $transaction;
        $this->seat = $seat;
    }

    public function broadcastOn()
    {
        return new Channel('transactions.' . $this->seat->venue_id);
    }
}
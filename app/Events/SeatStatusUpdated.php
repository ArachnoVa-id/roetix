<?php

namespace App\Events;

use App\Models\Seat;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $seat;

    public function __construct(Seat $seat)
    {
        $this->seat = $seat;
    }

    public function broadcastOn()
    {
        return new Channel('seats.' . $this->seat->venue_id);
    }
}
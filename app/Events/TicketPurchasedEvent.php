<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PublicChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Ticket;
use Illuminate\Broadcasting\PrivateChannel;

class TicketPurchasedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tickets;

    /**
     * Create a new event instance.
     */
    public function __construct(Ticket $tickets)
    {
        $this->tickets = $tickets;
    }

    public function broadcastAs()
    {
        return 'ticket-purchased';
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('tickets'),
        ];
    }

    /**
     * Data yang akan dikirim ke frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'tickets' => [
                'ticket_id' => $this->tickets->ticket_id,
                'status' => $this->tickets->status,
                'seat_id' => $this->tickets->seat_id,
                'event_id' => $this->tickets->event_id,
            ],
        ];
    }
}

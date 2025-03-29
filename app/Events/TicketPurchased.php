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

class TicketPurchased implements ShouldBroadcastNow
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
        return 'ticket.purchased';
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tickets'),
        ];

    }

    /**
     * Data yang akan dikirim ke frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'tickets' => $this->tickets->map(fn($ticket) => [
                'ticket_id' => $ticket->ticket_id,
                'status' => $ticket->status,
                'seat_id' => $ticket->seat_id,
                'event_id' => $ticket->event_id,
            ]),
        ];
    }
}

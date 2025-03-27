<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketOrder;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Get random user
        $user = User::inRandomOrder()->first();
        if (!$user) return [];

        // Get a random available ticket
        $ticket = Ticket::where('status', TicketStatus::AVAILABLE)->inRandomOrder()->first();

        // If no available tickets, return an empty array (prevent creating empty orders)
        if (!$ticket) {
            return [];
        }

        $event_id = $ticket->event_id;
        $event = $ticket->event;
        $orderCode = Order::keyGen(OrderType::UNKNOWN);

        return [
            'order_code' => $orderCode,
            'event_id' => $event_id,
            'user_id' => $user->id,
            'team_id' => $event->team_id,
            'order_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total_price' => $this->faker->randomFloat(2, 100000, 1000000),
            'status' => $this->faker->randomElement(OrderStatus::values()),
        ];
    }

    // Attach tickets to the order AFTER it is created
    public function configure()
    {
        return $this->afterCreating(function (Order $order) {
            // Find available tickets for the event
            $available_tickets = Ticket::where('event_id', $order->event_id)
                ->where('status', TicketStatus::AVAILABLE)
                ->get();

            // If no available tickets, do nothing
            if ($available_tickets->isEmpty()) {
                return;
            }

            // Random number of tickets (1 to remaining tickets, max 5)
            $num_tickets = rand(1, min($available_tickets->count(), 5));

            // Select random tickets
            $selected_tickets = $available_tickets->random($num_tickets);

            foreach ($selected_tickets as $ticket) {
                // Check what is the current order status
                switch ($order->status) {
                    case OrderStatus::COMPLETED:
                        // If order is paid, mark ticket as booked
                        $ticket->status = TicketStatus::BOOKED;
                        break;
                    case OrderStatus::PENDING:
                        // If order is pending, mark ticket as reserved
                        $ticket->status = TicketStatus::IN_TRANSACTION;
                        break;
                    default:
                        // If order is anything else, mark ticket as available
                        $ticket->status = TicketStatus::AVAILABLE;
                        break;
                }

                $ticket->save();

                // Create TicketOrder with correct order_id
                TicketOrder::create([
                    'order_id' => $order->order_id,
                    'ticket_id' => $ticket->ticket_id,
                    'event_id' => $order->event_id,
                    'status' => TicketOrderStatus::ENABLED,
                ]);
            }
        });
    }
}

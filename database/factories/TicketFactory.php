<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\Seat;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {


        return [
            'ticket_id' => (string) Str::uuid(),
            'event_id' => Event::all()->random()->event_id,
            'seat_id' => Seat::all()->random()->seat_id,
            'ticket_type' => $this->faker->randomElement(['standard', 'VIP']),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

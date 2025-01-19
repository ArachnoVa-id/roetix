<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Ticket;
use App\Models\Event;

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
            'event_id' => Event::factory(),
            'ticket_type' => $this->faker->randomElement(['standard', 'VIP']),
            'price' => $this->faker->randomFloat(2, 10, 500), // Random price between 10 and 500
            'status' => $this->faker->randomElement(['available', 'sold', 'reserved']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

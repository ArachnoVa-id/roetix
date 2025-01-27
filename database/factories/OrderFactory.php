<?php

namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\User;
use App\Models\Ticket;

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
        return [
            'order_id' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'ticket_id' => Ticket::factory(),
            'order_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'total_price' => $this->faker->randomFloat(2, 100000, 1000000),
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']),
        ];
    }
}

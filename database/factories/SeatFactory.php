<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Venue;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seat>
 */
class SeatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'seat_id' => (string) Str::uuid(),
            'venue_id' => Venue::factory(),
            'seat_number' => $this->faker->unique()->numberBetween(1, 100),
            'position' => $this->faker->word(),
            'status' => $this->faker->randomElement(['available', 'booked', 'reserved', 'in_transaction']),
        ];
    }
}

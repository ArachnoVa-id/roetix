<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\UserContact;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Venue>
 */
class VenueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'venue_id' => (string) Str::uuid(),
            'name' => $this->faker->sentence(2),
            'location' => $this->faker->address,
            'capacity' => $this->faker->numberBetween(100, 10000),
            'contact_info' => UserContact::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'under_maintenance']),
        ];
    }
}

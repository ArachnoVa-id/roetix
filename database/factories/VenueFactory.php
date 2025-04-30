<?php

namespace Database\Factories;

use App\Enums\VenueStatus;
use App\Models\Team;
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
            'name' => $this->faker->sentence(2),
            'location' => $this->faker->address,
            'team_id' => Team::inRandomOrder()->first()?->id,
            'contact_info' => UserContact::factory(),
            'status' => $this->faker->randomElement(VenueStatus::values()),
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\EventVariables;
use App\Models\Team;
use App\Models\Venue;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Event::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->sentence(3);
        $slug = Str::slug($name);

        return [
            'event_id' => (string) Str::uuid(),
            'team_id' => Team::inRandomOrder()->first()?->team_id,
            'venue_id' => Venue::inRandomOrder()->first()?->venue_id,
            'name' => $name,
            'slug' => $slug,
            'category' => $this->faker->randomElement(['concert', 'sports', 'workshop', 'etc']),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+2 months'),
            'location' => $this->faker->address(),
            'status' => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
        ];
    }
}

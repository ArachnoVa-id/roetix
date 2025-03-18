<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Event;
use App\Models\EventVariables;
use App\Models\Team;
use App\Models\Venue;
use Carbon\Carbon;

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
        
        // Create end_date 1-2 months in the future
        $endDate = $this->faker->dateTimeBetween('+1 month', '+2 months');
        
        // Create event_date 1-7 days after end_date
        $eventDateCarbon = Carbon::instance($endDate)->addDays($this->faker->numberBetween(1, 7));
        
        return [
            'event_id' => (string) Str::uuid(),
            'team_id' => Team::inRandomOrder()->first()?->team_id,
            'venue_id' => Venue::inRandomOrder()->first()?->venue_id,
            'name' => $name,
            'slug' => $slug,
            'category' => $this->faker->randomElement(['concert', 'sports', 'workshop', 'etc']),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $endDate,
            'event_date' => $eventDateCarbon->toDateTime(), // 1-7 days after end_date
            'location' => $this->faker->address(),
            'status' => $this->faker->randomElement(['planned', 'active', 'completed', 'cancelled']),
        ];
    }
}
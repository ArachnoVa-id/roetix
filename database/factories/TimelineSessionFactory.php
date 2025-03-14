<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\TimelineSession;
use App\Models\Event;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimelineSession>
 */
class TimelineSessionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimelineSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // CATATAN: Sebenarnya ini tidak akan digunakan secara langsung
        // Kita akan menimpa nilai-nilai ini di TimelineSessionSeeder
        return [
            'timeline_id' => (string) Str::uuid(),
            'event_id' => null,
            'name' => $this->faker->word,
            'start_date' => now(),
            'end_date' => now()->addDays(1),
        ];
    }
}
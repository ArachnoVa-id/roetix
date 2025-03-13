<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\EventVariables;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EventVariables>
 */
class EventVariablesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = EventVariables::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_variables_id' => (string) Str::uuid(),

            'is_locked' => $this->faker->boolean,
            'locked_password' => $this->faker->word,

            'is_maintenance' => $this->faker->boolean,
            'maintenance_expected_finish' => now(),
            'maintenance_title' => $this->faker->sentence,
            'maintenance_message' => $this->faker->sentence,

            'logo' => '/images/novatix-logo/favicon-32x32.png',
            'logo_alt' => 'Novatix Logo',
            'favicon' => '/images/novatix-logo/favicon.ico',
            'primary_color' => $this->randomColor(),
            'secondary_color' => $this->randomColor(),
            'text_primary_color' => $this->randomColor(),
            'text_secondary_color' => $this->randomColor(),

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Random color picker in hex
    public function randomColor(): string
    {
        return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }
}

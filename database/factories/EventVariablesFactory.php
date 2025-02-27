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
            'is_maintenance' => $this->faker->boolean,
            'var_title' => $this->faker->word,
            'expected_finish' => now(),
            'password' => $this->faker->word,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

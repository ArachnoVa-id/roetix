<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Seat;
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
        // Define row labels (A-Z) and max columns per row
        $rows = range('A', 'Z');
        $maxColumns = 15; // Adjust based on your seating arrangement

        // Generate a random row and column
        $row = $this->faker->randomElement($rows);
        $column = $this->faker->numberBetween(1, $maxColumns);

        return [
            'seat_number' => $row . $column, // Example: A1, B3, G10
            'position' => $row . $column, // Matches the pattern from the image
            'row' => $row,
            'column' => $column,
        ];
    }
}

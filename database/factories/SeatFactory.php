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

    /**
     * Ensure seat number is unique within the given venue.
     *
     * @param string $venueId
     * @return static
     */
    public function forVenue(string $venueId): static
    {
        // Loop until we find a unique seat_number for the given venue
        $existing = true;
        $row = '';
        $column = 0;
        $seatNumber = '';

        while ($existing) {
            $row = $this->faker->randomElement(range('A', 'Z'));
            $column = $this->faker->numberBetween(1, 15);
            $seatNumber = $row . $column;

            // Check if the seat_number already exists for the given venue
            $existing = Seat::where('venue_id', $venueId)
                ->where('seat_number', $seatNumber)
                ->exists();
        }

        return $this->state(fn() => [
            'seat_number' => $seatNumber,
            'position' => $seatNumber,
            'row' => $row,
            'column' => $column,
            'venue_id' => $venueId, // Add venue_id
        ]);
    }
}

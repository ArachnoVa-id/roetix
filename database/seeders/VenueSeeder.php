<?php

namespace Database\Seeders;

use App\Models\Seat;
use Illuminate\Database\Seeder;
use App\Models\Venue;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 venues
        Venue::factory()->count(10)->create()->each(function ($venue) {
            $usedSeats = [];

            while (count($usedSeats) < 100) {
                $row = fake()->randomElement(range('A', 'Z'));
                $col = fake()->numberBetween(1, 15);
                $seatNumber = $row . $col;

                if (!in_array($seatNumber, $usedSeats)) {
                    $usedSeats[] = $seatNumber;

                    Seat::factory()
                        ->state([
                            'seat_number' => $seatNumber,
                            'position' => $seatNumber,
                            'row' => $row,
                            'column' => $col,
                            'venue_id' => $venue->id,
                        ])
                        ->make()
                        ->save();
                }
            }
        });
    }
}

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
            // Generate 100 unique seats for each venue
            Seat::factory()->count(100)->forVenue($venue->id)->create();
        });
    }
}

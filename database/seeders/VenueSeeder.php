<?php

namespace Database\Seeders;

use App\Models\Seat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Venue;

class VenueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Venue::factory()->count(10)->create()->each(function ($venue) {
            Seat::factory()->count(100)->create([
                'venue_id' => $venue->id,
            ]);
        });
    }
}

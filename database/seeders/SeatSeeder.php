<?php
// database/seeders/SeatSeeder.php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Seat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        // Create a sample section
        $section = Section::create([
            'id' => Str::uuid(),
            'name' => 'Main Section'
        ]);

        // Create sample seats
        $rows = ['A', 'B', 'C', 'D', 'E'];
        $seatsPerRow = 10;

        foreach ($rows as $row) {
            for ($col = 1; $col <= $seatsPerRow; $col++) {
                Seat::create([
                    'seat_id' => Str::uuid(),
                    'section_id' => $section->id,
                    'seat_number' => $row . $col,
                    'position' => $row . '-' . $col,
                    'status' => 'available',
                    'category' => 'silver',
                    'row' => $row,
                    'column' => $col,
                ]);
            }
        }
    }
}
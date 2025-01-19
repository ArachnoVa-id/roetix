<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Rock Fest 2025',
                'category' => 'concert',
                'start_date' => now()->addDays(10),
                'end_date' => now()->addDays(11),
                'location' => 'Stadium A',
                'status' => 'planned',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Marathon Challenge',
                'category' => 'sports',
                'start_date' => now()->addDays(20),
                'end_date' => now()->addDays(20)->addHours(6),
                'location' => 'City Park',
                'status' => 'active',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Photography Workshop',
                'category' => 'workshop',
                'start_date' => now()->addDays(15),
                'end_date' => now()->addDays(15)->addHours(4),
                'location' => 'Art Center',
                'status' => 'planned',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Music Gala Night',
                'category' => 'concert',
                'start_date' => now()->addDays(30),
                'end_date' => now()->addDays(31),
                'location' => 'Concert Hall',
                'status' => 'planned',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Tech Innovation Summit',
                'category' => 'workshop',
                'start_date' => now()->addDays(40),
                'end_date' => now()->addDays(41),
                'location' => 'Convention Center',
                'status' => 'planned',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Charity Soccer Match',
                'category' => 'sports',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7)->addHours(2),
                'location' => 'Sports Complex',
                'status' => 'active',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Jazz Evening',
                'category' => 'concert',
                'start_date' => now()->addDays(18),
                'end_date' => now()->addDays(18)->addHours(5),
                'location' => 'Jazz Club',
                'status' => 'planned',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Yoga Retreat',
                'category' => 'workshop',
                'start_date' => now()->addDays(25),
                'end_date' => now()->addDays(26),
                'location' => 'Wellness Center',
                'status' => 'completed',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Local Art Expo',
                'category' => 'etc',
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(3)->addHours(8),
                'location' => 'Exhibition Hall',
                'status' => 'cancelled',
            ],
            [
                'event_id' => (string) Str::uuid(),
                'name' => 'Annual Coding Hackathon',
                'category' => 'workshop',
                'start_date' => now()->addDays(50),
                'end_date' => now()->addDays(51),
                'location' => 'Tech Park',
                'status' => 'planned',
            ],
        ];

        foreach ($events as $event) {
            Event::create($event);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\TrafficNumbersSlug;

class TrafficNumbersSlugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = Event::all();

        foreach ($events as $event) {
            TrafficNumbersSlug::updateOrCreate(
                ['event_id' => $event->id],
                ['active_sessions' => 0]
            );
        }
    }
}

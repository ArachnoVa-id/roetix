<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Seat;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fetch all event IDs and seat IDs to optimize performance
        $eventIds = Event::pluck('event_id')->toArray();
        $seatIds = Seat::pluck('seat_id')->toArray();

        // Ensure there are events and seats before seeding
        if (empty($eventIds) || empty($seatIds)) {
            return;
        }

        // Create a random number of tickets for each event
        foreach ($eventIds as $eventId) {
            Ticket::factory()
                ->count(rand(1, 3)) // Generate 1 to 3 tickets per event
                ->create([
                    'event_id' => $eventId,
                    'seat_id' => $seatIds[array_rand($seatIds)], // Pick a random seat
                ]);
        }
    }
}

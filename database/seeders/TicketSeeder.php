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

        foreach ($eventIds as $eventId) {
            Ticket::factory()
                ->count(rand(1, 5))
                ->create([
                    'event_id' => $eventId,
                    'seat_id' => $seatIds[array_rand($seatIds)],
                ]);
        }
    }
}

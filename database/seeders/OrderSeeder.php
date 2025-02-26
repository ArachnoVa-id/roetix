<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::factory()->count(10)->create();

        $orders = Order::all();
        $tickets = Ticket::all();

        for ($i = 0; $i < 100; $i++) {
            $rd_ticket = $tickets->random();

            DB::table('ticket_order')->insert([
                'order_id' => $orders->random()->order_id,
                'ticket_id' => $rd_ticket->ticket_id,
                'event_id' => $rd_ticket->event_id,
            ]);
        }
    }
}

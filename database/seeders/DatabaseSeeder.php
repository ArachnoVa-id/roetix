<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\User;
use App\Models\EventVariables;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\UserContact;
use App\Models\Venue;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Order::truncate();
        Coupon::truncate();
        TicketCategory::truncate();
        Ticket::truncate();
        Seat::truncate();
        Venue::truncate();
        UserContact::truncate();
        EventVariables::truncate();
        Event::truncate();
        User::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $this->call([
            UserSeeder::class,
            EventSeeder::class,
            EventVariablesSeeder::class,
            UserContactSeeder::class,
            VenueSeeder::class,
            SeatSeeder::class,
            TicketSeeder::class,
            TicketCategorySeeder::class,
            CouponSeeder::class,
            OrderSeeder::class,
        ]);
    }
}

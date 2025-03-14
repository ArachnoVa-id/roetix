<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Team::truncate();
        // Order::truncate();
        // Coupon::truncate();
        // TicketCategory::truncate();
        // Ticket::truncate();
        // Seat::truncate();
        // Venue::truncate();
        // UserContact::truncate();
        // EventVariables::truncate();
        // Event::truncate();
        // User::truncate();

        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            UserSeeder::class,
            TeamSeeder::class,
            VenueSeeder::class,
            EventVariablesSeeder::class,
            EventSeeder::class,
            UserContactSeeder::class,
            TicketSeeder::class,
            TicketCategorySeeder::class,
            CouponSeeder::class,
            TimelineSessionSeeder::class,
            EventCategoryTimeboundPriceSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}

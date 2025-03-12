<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Event;
use App\Models\EventVariables;
use App\Models\Order;
use App\Models\Seat;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Models\UserContact;
use App\Models\Venue;
use App\Models\EventCategoryTimeboundPrice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
            RoleSeeder::class,
            VenueSeeder::class,
            EventSeeder::class,
            EventVariablesSeeder::class,
            UserContactSeeder::class,
            TicketSeeder::class,
            TicketCategorySeeder::class,
            CouponSeeder::class,
            EventCategoryTimeboundPriceSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}

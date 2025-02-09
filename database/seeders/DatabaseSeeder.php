<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\EventVariables;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);
        $this->call(EventSeeder::class);
        $this->call(EventVariablesSeeder::class);
        $this->call(UserContactSeeder::class);
        $this->call(VenueSeeder::class);
        $this->call(SeatSeeder::class);
        $this->call(TicketSeeder::class);
        $this->call(TicketCategorySeeder::class);
        $this->call(CouponSeeder::class);
        $this->call(OrderSeeder::class);
    }
}

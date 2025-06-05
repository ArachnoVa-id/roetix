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
        $this->call([
            UserSeeder::class, // Initial users
            TeamSeeder::class, // Pick random user assignment
            VenueSeeder::class, // With seat generation
            EventSeeder::class, // With all event timelineing and ticket generation
            OrderSeeder::class, // Pick random ticket assignment
            TrafficNumbersSlugSeeder::class,
        ]);
    }
}

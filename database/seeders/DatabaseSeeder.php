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
        $this->call(TicketSeeder::class);
        $this->call(EventVariablesSeeder::class);
    }
}

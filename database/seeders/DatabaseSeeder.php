<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'user',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'admin',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'email' => 'vendor@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'vendor',
            'role' => 'vendor',
        ]);
    }
}

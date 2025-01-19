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

        EventVariables::factory()->create([
            'is_locked' => true,
            'is_maintenance' => true,
            'var_c' => 'var test',
            'var_b' => 'var test',
            'var_c' => 'var test',
        ]);


        EventVariables::factory()->create([
            'is_locked' => false,
            'is_maintenance' => true,
            'var_c' => 'var test',
            'var_b' => 'var vendor',
            'var_c' => 'var vendor',
        ]);
    }
}

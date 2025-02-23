<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'user',
            'role' => 'user',
        ]);

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'admin',
            'role' => 'admin',
        ]);

        // vendor 1
        User::factory()->create([
            'email' => 'vendor1@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'vendor1',
            'role' => 'vendor',
        ]);
        // vendor 2
        User::factory()->create([
            'email' => 'vendor2@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'vendor2',
            'role' => 'vendor',
        ]);
        // vendor 3
        User::factory()->create([
            'email' => 'vendor3@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'vendor3',
            'role' => 'vendor',
        ]);
        // vendor 4
        User::factory()->create([
            'email' => 'vendor4@example.com',
            'password' => 'test123',
            'first_name' => 'test',
            'last_name' => 'vendor4',
            'role' => 'vendor',
        ]);
    }
}

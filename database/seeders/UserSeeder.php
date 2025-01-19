<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('test123'), // Encrypt the password
            'first_name' => 'test',
            'last_name' => 'user',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('test123'), // Encrypt the password
            'first_name' => 'test',
            'last_name' => 'admin',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'email' => 'vendor@example.com',
            'password' => bcrypt('test123'), // Encrypt the password
            'first_name' => 'test',
            'last_name' => 'vendor',
            'role' => 'vendor',
        ]);
    }
}

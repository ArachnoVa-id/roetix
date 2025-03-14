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

        $data = [
            // user
            [
                'email' => 'user@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'user',
                'role' => 'user',
            ],

            // admin novatix
            [
                'email' => 'admin@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'admin',
                'role' => 'admin',
            ],

            // vendor 1
            [
                'email' => 'vendor1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor1',
                'role' => 'vendor',
            ],

            // vendor 2
            [
                'email' => 'vendor2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor2',
                'role' => 'vendor',
            ],

            // eo 1
            [
                'email' => 'eo1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo1',
                'role' => 'event-organizer',
            ],

            // eo2
            [
                'email' => 'eo2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo2',
                'role' => 'event-organizer',
            ],
        ];

        foreach ($data as $user) {
            $created_user = User::create([
                'email' => $user['email'],
                'password' => $user['password'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
            ]);

            // assign rolenya disini lex
            // $created_user->syncRoles([$user['role']]);
        }
    }
}

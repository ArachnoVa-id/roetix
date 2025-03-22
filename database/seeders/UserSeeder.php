<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private array $defaultUsers;

    public function __construct()
    {
        $this->defaultUsers = [
            // user
            [
                'email' => 'user@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'user',
                'role' => 'user',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],

            // admin novatix
            [
                'email' => 'admin@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'admin',
                'role' => 'admin',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],

            // vendor 1
            [
                'email' => 'vendor1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor1',
                'role' => 'vendor',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],

            // vendor 2
            [
                'email' => 'vendor2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor2',
                'role' => 'vendor',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],

            // eo 1
            [
                'email' => 'eo1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo1',
                'role' => 'event-organizer',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],

            // eo2
            [
                'email' => 'eo2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo2',
                'role' => 'event-organizer',
                'contact_info' => UserContact::factory()->create()->contact_id,
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->defaultUsers as $user) {
            User::create($user);
        }
    }
}

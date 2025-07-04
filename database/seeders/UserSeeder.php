<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
                'role' => UserRole::USER->value,
                'contact_info' => UserContact::factory()->create()->id,
            ],

            // admin novatix
            [
                'email' => 'admin@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'admin',
                'role' => UserRole::ADMIN->value,
                'contact_info' => UserContact::factory()->create()->id,
            ],

            // vendor 1
            [
                'email' => 'vendor1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor1',
                'role' => UserRole::VENDOR->value,
                'contact_info' => UserContact::factory()->create()->id,
            ],

            // vendor 2
            [
                'email' => 'vendor2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'vendor2',
                'role' => UserRole::VENDOR->value,
                'contact_info' => UserContact::factory()->create()->id,
            ],

            // eo 1
            [
                'email' => 'eo1@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo1',
                'role' => UserRole::EVENT_ORGANIZER->value,
                'contact_info' => UserContact::factory()->create()->id,
            ],

            // eo2
            [
                'email' => 'eo2@example.com',
                'password' => Hash::make('test123'),
                'first_name' => 'test',
                'last_name' => 'eo2',
                'role' => UserRole::EVENT_ORGANIZER->value,
                'contact_info' => UserContact::factory()->create()->id,
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

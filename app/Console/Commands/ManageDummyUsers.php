<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserContact;
use Illuminate\Support\Facades\Hash;

class ManageDummyUsers extends Command
{
    protected $signature = 'dummy-users {action : launch or destroy} {--count=100} {--verbose=false}';
    protected $description = 'Create or remove dummy users';

    public function handle()
    {
        $action = $this->argument('action');
        $verbose = (bool) $this->argument('verbose');
        $count = (int) $this->option('count');

        if ($action === 'launch') {
            $this->info("Creating {$count} dummy users...");

            for ($i = 1; $i <= $count; $i++) {
                $email = "testuser{$i}@example.com";
                $password = 'password123';

                $contact = UserContact::factory()->create();

                $user = User::create([
                    'email' => $email,
                    'password' => Hash::make($password),
                    'first_name' => 'Test',
                    'last_name' => "User{$i}",
                    'role' => UserRole::USER->value,
                    'contact_info' => $contact->id,
                ]);

                if ($verbose) $this->info("Created user: {$user->email}");
            }

            $this->info("Dummy users created: {$count}");
        } elseif ($action === 'destroy') {
            $users = User::where('email', 'LIKE', 'testuser%@example.com')->get();

            foreach ($users as $user) {
                if ($user->contact_info) {
                    $user->contactInfo()->delete();
                }
                $user->delete();
                if ($verbose) $this->info("Deleted user: {$user->email}");
            }

            $this->info('Dummy users deleted.');
        } else {
            $this->error('Invalid action. Use "launch" or "destroy".');
        }
    }
}

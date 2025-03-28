<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company;
        // code based on name with random number
        $code = strtoupper($name);
        $code = str_replace(' ', '-', $code);
        $code = 'TEAM-' . $code;
        $code = $code . '-' . strtoupper(Str::random(6));

        return [
            'name' => $name,
            'code' => $code,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Team $team) {
            $allUsers = User::all();

            // Make sure the role of the user is not admin and user
            $usingUsers = $allUsers->filter(function (User $user) {
                return !in_array($user->role, [UserRole::ADMIN->value, UserRole::USER->value]);
            });

            // Count how many users existing
            $userCount = $usingUsers->count();

            // min is 3, max is 5
            $numUsers = $userCount > 5 ? rand(3, 5) : $userCount;

            // Pick the users
            $users = $usingUsers->random($numUsers);

            // Add in many to many
            $team->users()->attach($users);
        });
    }
}

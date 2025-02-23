<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach($users as $user)
        {
            $randValue = rand(1, 4);

            for($i = 0; $i < $randValue; $i++)
            {
                $tms = Team::create([
                    'name' => $user->name . "'s Team",
                    'code' => 'TEAM' . strtoupper(Str::random(6)),
                ]);

                DB::table('user_team')->insert([
                    'user_id' => $user->user_id,
                    'team_id' => $tms->team_id,
                ]);
            }
        }
    }
}

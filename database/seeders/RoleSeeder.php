<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teamdata = Team::all();
        
        // define permissions
        Permission::create(['name' => 'register vendor']);
        Permission::create(['name' => 'create event']);
        Permission::create(['name' => 'edit event']);
        Permission::create(['name' => 'delete event']);
        Permission::create(['name' => 'create ticket']);

        Permission::create(['name' => 'create vendor']);
        Permission::create(['name' => 'edit vendor']);
        Permission::create(['name' => 'delete vendor']);

        foreach ($teamdata as $team) {
            // define roles
            $admin = Role::create(['name' => 'admin', 'team_id' => $team->team_id]);
            $vendor = Role::create(['name' => 'vendor', 'team_id' => $team->team_id]);
            $eo = Role::create(['name' => 'event-orginizer', 'team_id' => $team->team_id]);
            $user = Role::create(['name' => 'user', 'team_id' => $team->team_id]);

            // assign perission
            $admin->givePermissionTo([
                'register vendor',
                'create event',
                'edit event',
                'delete event',
                'create ticket',
                'create vendor',
                'edit vendor',
                'delete vendor',
            ]);

            $vendor->givePermissionTo([
                'create vendor',
                'edit vendor',
                'delete vendor',
            ]);

            $eo->givePermissionTo([
                'register vendor',
                'create event',
                'edit event',
                'delete event',
                'create ticket',
            ]);
        }
    }
}

<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public function afterCreate()
    {
        $data = $this->data;

        $user = $this->record;

        // dd($user);

        $userdata = $data ?? [];

        if (!empty($userdata)) {
            $team = Team::find($userdata['team_id']);
            if ($team) {
                $user->teams()->attach($team->team_id);
            }
        }
    }
}

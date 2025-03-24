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

        $userdata = $data ?? [];

        if (!empty($userdata)) {
            $teams = $userdata['teams'] ?? [];
            foreach ($teams as $team) {
                $team = Team::find($team);
                $user->teams()->attach($team);
            }
        }
    }
}

<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTeam extends CreateRecord
{
    protected static string $resource = TeamResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()->label('Create Team');
    }
}

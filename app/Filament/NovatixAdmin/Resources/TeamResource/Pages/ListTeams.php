<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTeams extends ListRecords
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Team')
                ->icon('heroicon-o-plus'),
        ];
    }
}

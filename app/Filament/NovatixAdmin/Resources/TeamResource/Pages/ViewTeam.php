<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\Components\BackButtonAction;
use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeam extends ViewRecord
{
    protected static string $resource = TeamResource::class;

    public function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

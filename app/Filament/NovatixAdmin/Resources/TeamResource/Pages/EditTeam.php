<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => url()->previous())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Update Team');
    }
}

<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\Components\BackButtonAction;
use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    protected static string $resource = TeamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            TeamResource::AddMemberButton(
                Actions\Action::make('addMember')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Update Team')
            ->icon('heroicon-o-check-circle');
    }
}

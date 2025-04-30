<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\Pages;

use App\Filament\Components\BackButtonAction;
use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

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
                ->icon('heroicon-m-pencil-square')
                ->color(Color::Orange),
            TeamResource::AddMemberButton(
                Actions\Action::make('addMember')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

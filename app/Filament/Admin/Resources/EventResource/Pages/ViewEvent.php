<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use App\Filament\Components\BackButtonAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    public function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            Actions\EditAction::make('editEvent')
                ->icon('heroicon-m-pencil-square')
                ->color(Color::Orange),
            EventResource::EditSeatsButton(
                Actions\Action::make('editSeats')
            ),
            EventResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

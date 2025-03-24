<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => EventResource::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            EventResource::EditSeatsButton(
                Actions\Action::make('editSeats')
            )->button(),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

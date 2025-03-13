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
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
            Actions\Action::make('editSeats')
                ->label('Edit Seats')
                ->icon('heroicon-m-pencil-square')
                ->button()
                ->color('primary')
                ->url(fn($record) => "/seats/edit?event_id={$record->event_id}")
                ->openUrlInNewTab(),
        ];
    }
}

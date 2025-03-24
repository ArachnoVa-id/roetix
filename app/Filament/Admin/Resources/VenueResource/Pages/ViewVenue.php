<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVenue extends ViewRecord
{
    protected static string $resource = VenueResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => VenueResource::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            VenueResource::EditVenueButton(
                Actions\Action::make('Edit Venue')
            ),
            VenueResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

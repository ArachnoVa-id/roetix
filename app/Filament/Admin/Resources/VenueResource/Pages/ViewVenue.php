<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use App\Filament\Components\BackButtonAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVenue extends ViewRecord
{
    protected static string $resource = VenueResource::class;

    public function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            VenueResource::EditVenueButton(
                Actions\Action::make('Edit Venue')
            ),
            VenueResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            VenueResource::ExportVenueButton(
                Actions\Action::make('exportVenue')
            ),
            VenueResource::ImportVenueButton(
                Actions\Action::make('importVenue')
            )
        ];
    }
}

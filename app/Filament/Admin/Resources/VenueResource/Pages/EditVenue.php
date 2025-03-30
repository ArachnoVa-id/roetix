<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use App\Filament\Components\BackButtonAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            VenueResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            VenueResource::ExportVenueButton(
                Actions\Action::make('exportVenue')
            ),
            VenueResource::ImportVenueButton(
                Actions\Action::make('importVenue')
            ),
            Actions\DeleteAction::make('Delete Venue')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()->label('Update Venue');
    }
}

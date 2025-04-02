<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use App\Filament\Components\BackButtonAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

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
                ->icon('heroicon-m-pencil-square')
                ->color(Color::Orange),
            VenueResource::EditVenueButton(
                Actions\Action::make('Edit Venue')
            ),
            VenueResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),

            Actions\ActionGroup::make([
                VenueResource::ExportVenueButton(
                    Actions\Action::make('exportVenue')
                ),
                VenueResource::ImportVenueButton(
                    Actions\Action::make('importVenue')
                ),
                Actions\DeleteAction::make('Delete Venue')
                    ->icon('heroicon-o-trash'),
            ]),
        ];
    }
}

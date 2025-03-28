<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVenue extends EditRecord
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => url()->previous())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            VenueResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
        ];
    }
}

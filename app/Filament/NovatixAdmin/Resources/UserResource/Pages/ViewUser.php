<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Filament\NovatixAdmin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => UserResource::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            Actions\EditAction::make('Edit Event')
                ->icon('heroicon-o-pencil'),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }
}

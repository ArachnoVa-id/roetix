<?php

namespace App\Filament\NovatixAdmin\Resources;

use Filament\Tables;
use App\Filament\Admin\Resources\VenueResource as AdminVenueResource;
use App\Filament\NovatixAdmin\Resources\VenueResource\Pages;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class VenueResource extends AdminVenueResource
{
    protected static ?int $navigationSort = 4;

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return AdminVenueResource::getNavigationIcon();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
            'view' => Pages\ViewVenue::route('/{record}'),
        ];
    }
}

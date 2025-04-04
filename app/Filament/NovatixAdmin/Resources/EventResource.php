<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Filament\Admin\Resources\EventResource as AdminEventResource;
use App\Filament\NovatixAdmin\Resources\EventResource\Pages;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class EventResource extends AdminEventResource
{
    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return AdminEventResource::getNavigationIcon();
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'view' => Pages\ViewEvent::route('/{record}'),
        ];
    }
}

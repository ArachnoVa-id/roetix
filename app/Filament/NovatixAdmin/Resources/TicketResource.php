<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Filament\Admin\Resources\TicketResource as AdminTicketResource;
use App\Filament\NovatixAdmin\Resources\TicketResource\Pages;
use Filament\Resources\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class TicketResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 5;

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return AdminTicketResource::getNavigationIcon();
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
            'index' => Pages\ListTickets::route('/'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}

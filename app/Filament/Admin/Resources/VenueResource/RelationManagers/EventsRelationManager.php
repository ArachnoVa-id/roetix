<?php

namespace App\Filament\Admin\Resources\VenueResource\RelationManagers;

use App\Filament\Admin\Resources\EventResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function infolist(Infolist $infolist): Infolist
    {
        return EventResource::infolist($infolist, showOrders: false, showTickets: false);
    }

    public function table(Table $table): Table
    {
        return EventResource::table($table, filterStatus: true)
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes())
            ->heading('');
    }
}

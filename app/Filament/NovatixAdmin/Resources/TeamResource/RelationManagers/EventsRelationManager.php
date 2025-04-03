<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\Admin\Resources\EventResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

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
            ->heading('');
    }
}

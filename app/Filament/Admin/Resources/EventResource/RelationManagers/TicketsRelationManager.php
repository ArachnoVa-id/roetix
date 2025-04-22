<?php

namespace App\Filament\Admin\Resources\EventResource\RelationManagers;

use App\Filament\Admin\Resources\TicketResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function getTableQuery(): Builder
    {
        return $this->ownerRecord
            ->tickets()
            ->getQuery();
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return TicketResource::infolist($infolist, showBuyer: false, showOrders: false);
    }

    public function table(Table $table): Table
    {
        return TicketResource::table($table, showEvent: false, showTraceButton: true, filterStatus: true, filterEvent: false, filterTeam: false)
            ->heading('');
    }
}

<?php

namespace App\Filament\Admin\Resources\OrderResource\RelationManagers;

use App\Filament\Admin\Resources\TicketResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function infolist(Infolist $infolist): Infolist
    {
        return TicketResource::infolist($infolist, showBuyer: false, showOrders: false);
    }

    public function table(Table $table): Table
    {
        return TicketResource::table($table, showEvent: false, showTraceButton: true, filterStatus: true, filterTeam: false)
            ->heading('');
    }
}

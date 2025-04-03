<?php

namespace App\Filament\Admin\Resources\EventResource\RelationManagers;

use App\Filament\Admin\Resources\TicketResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function infolist(Infolist $infolist): Infolist
    {
        $event = $this->ownerRecord;
        dd($event->tickets);

        return TicketResource::infolist($infolist, event: $event, showBuyer: false, showOrders: false);
    }

    public function table(Table $table): Table
    {
        return TicketResource::table($table, showEvent: false, showTraceButton: true, filterStatus: true, filterEvent: false)
            ->heading('');
    }
}

<?php

namespace App\Filament\Admin\Resources\EventResource\RelationManagers;

use App\Filament\Admin\Resources\OrderResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function getTableRecords(): Collection
    {
        $orders = $this->ownerRecord->orders;

        return new Collection($orders);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return OrderResource::infolist($infolist, showTickets: false);
    }

    public function table(Table $table): Table
    {
        return OrderResource::table($table, filterStatus: true, filterEvent: false)
            ->heading('');
    }
}

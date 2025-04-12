<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\Admin\Resources\EventResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';


    public function getTableRecords(): Collection
    {
        $events = $this->ownerRecord->events;

        return new Collection($events);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $eventId = $infolist->record->id;

        $record = $this->ownerRecord->events()
            ->where('id', $eventId)
            ->with([
                'team',
                'ticketCategories',
                'ticketCategories.eventCategoryTimeboundPrices',
                'ticketCategories.eventCategoryTimeboundPrices.timelineSession',
            ])
            ->first();

        return EventResource::infolist($infolist, record: $record, showOrders: false, showTickets: false);
    }

    public function table(Table $table): Table
    {
        return EventResource::table($table, filterStatus: true)
            ->heading('');
    }
}

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
        $ownerRecordArray = $this->ownerRecord->toArray();
        // Loop through the ownerRecordArray and add the new property `timelineSession_name`
        foreach ($ownerRecordArray['events'] as &$event) {
            foreach ($event['ticket_categories'] as &$ticketCategory) {
                foreach ($ticketCategory['event_category_timebound_prices'] as &$timeboundPrice) {
                    // Check if 'timeline_session' exists and has a 'name' field
                    if (isset($timeboundPrice['timeline_session']['name'])) {
                        // Add a new property 'timelineSession_name' based on 'timeline_session.name'
                        $timeboundPrice['timelineSession_name'] = $timeboundPrice['timeline_session']['name'];
                    }
                }
            }
        }

        return EventResource::infolist($infolist, dataSource: $ownerRecordArray, showOrders: false, showTickets: false);
    }

    public function table(Table $table): Table
    {
        return EventResource::table($table, showTeamName: false, filterStatus: true)
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes())
            ->heading('');
    }
}

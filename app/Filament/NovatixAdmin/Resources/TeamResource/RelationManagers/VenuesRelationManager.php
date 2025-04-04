<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class VenuesRelationManager extends RelationManager
{
    protected static string $relationship = 'venues';

    public function getTableRecords(): Collection
    {
        $venues = $this->ownerRecord->venues;

        return new Collection($venues);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $venueId = $infolist->record->venue_id;

        $record = $this->ownerRecord->venues()
            ->where('venue_id', $venueId)
            ->with([
                'seats',
            ])
            ->first();

        return VenueResource::infolist($infolist, record: $record, showEvents: false);
    }

    public function table(Table $table): Table
    {
        return VenueResource::table($table, filterStatus: true, filterCapacity: false)
            ->heading('');
    }
}

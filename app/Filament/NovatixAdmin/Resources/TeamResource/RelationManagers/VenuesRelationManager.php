<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class VenuesRelationManager extends RelationManager
{
    protected static string $relationship = 'venues';

    public function infolist(Infolist $infolist): Infolist
    {
        return VenueResource::infolist($infolist, showEvents: false);
    }

    public function table(Table $table): Table
    {
        return VenueResource::table($table, filterStatus: true)
            ->heading('');
    }
}

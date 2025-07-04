<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use App\Filament\NovatixAdmin\Resources\UserResource;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    public function infolist(Infolist $infolist): Infolist
    {
        return TeamResource::infolist($infolist, showMembers: false, showEvents: false, showVenues: false);
    }

    public function table(Table $table): Table
    {
        return TeamResource::table($table, showAddMemberAction: false, additionActions: [UserResource::KickMemberButton()])
            ->heading('');
    }
}

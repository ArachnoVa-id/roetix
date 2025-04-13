<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\User;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function infolist(Infolist $infolist): Infolist
    {
        return UserResource::infolist($infolist, showTeams: false);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return UserResource::table($table, filterRole: true, additionActions: [UserResource::KickMemberButton()])
            ->heading('');
    }
}

<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

    public function infolist(Infolist $infolist): Infolist
    {
        return TeamResource::infolist($infolist);
    }

    public function form(Form $form): Form
    {
        return TeamResource::form($form);
    }

    public function table(Table $table): Table
    {
        return TeamResource::table($table)
            ->heading('');
    }
}

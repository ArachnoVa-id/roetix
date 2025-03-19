<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\TeamResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TeamsRelationManager extends RelationManager
{
    protected static string $relationship = 'teams';

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

<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\UserResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return UserResource::form($form);
    }

    public function table(Table $table): Table
    {
        return UserResource::table($table)
            ->heading('');
    }
}

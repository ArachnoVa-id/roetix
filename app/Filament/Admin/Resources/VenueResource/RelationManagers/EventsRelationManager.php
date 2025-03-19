<?php

namespace App\Filament\Admin\Resources\VenueResource\RelationManagers;

use App\Filament\Admin\Resources\EventResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';

    public function form(Form $form): Form
    {
        return EventResource::form($form);
    }

    public function table(Table $table): Table
    {
        return EventResource::table($table)
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes())
            ->heading('');
    }
}

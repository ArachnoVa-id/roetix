<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\Admin\Resources\VenueResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class VenuesRelationManager extends RelationManager
{
    protected static string $relationship = 'venues';

    public function form(Form $form): Form
    {
        return VenueResource::form($form);
    }

    public function table(Table $table): Table
    {
        return VenueResource::table($table)
            ->heading('');
    }
}

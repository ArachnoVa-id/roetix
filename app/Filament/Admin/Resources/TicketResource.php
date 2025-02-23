<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ticket_id')
                    ->label('Ticket ID')
                    ->disabled(),
                    // ->required(),
                Forms\Components\Select::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'name')
                    ->required(),
                Forms\Components\Select::make('seat_id')
                    ->label('Seat')
                    ->relationship('seat', 'seat_number')
                    ->required(),
                Forms\Components\Select::make('ticket_type')
                    ->label('Ticket Type')
                    ->options([
                        'standard' => 'Standard',
                        'VIP' => 'VIP',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Available',
                        'sold' => 'Sold',
                        'reserved' => 'Reserved',
                    ])
                    ->required(),
                Forms\Components\Select::make('team_id')
                    ->label('Team')
                    ->relationship('team', 'name')
                    ->required(),
                    // ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id'),
                Tables\Columns\TextColumn::make('event.name'),
                // Tables\Columns\TextColumn::make('event_id'),
                // Tables\Columns\TextColumn::make('seat_id'),
                Tables\Columns\TextColumn::make('ticket_type'),
                Tables\Columns\TextColumn::make('price'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('event_id')
                    ->label('Filter by Event')
                    ->relationship('event', 'name')
                    ->searchable()
                    ->default(request()->query('tableFilters')['event_id']['value'] ?? null),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}

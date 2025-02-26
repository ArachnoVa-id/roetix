<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\EventResource\Pages;
use App\Filament\Admin\Resources\EventResource\Pages\Settings;
use App\Filament\Admin\Resources\EventResource\Pages\TicketScan;
use App\Filament\Admin\Resources\EventResource\RelationManagers;
use Filament\Tables\Actions\Action;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant()->team_id;
        $tenant_name = Filament::getTenant()->code;

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->options([
                        'concert' => 'concert',
                        'sports' => 'sports',
                        'workshop' => 'workshop',
                        'etc' => 'etc'
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'planned' => 'planned',
                        'active' => 'active',
                        'completed' => 'completed',
                        'cancelled' => 'cancelled'
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\TextInput::make('location')
                    ->required(),
                Forms\Components\TextInput::make('team_code')
                    ->label('Team')
                    ->default($tenant_name)
                    ->disabled()
                    ->required(),
                Forms\Components\Hidden::make('team_id')
                    ->default($tenant)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event Name')
                    ->sortable()
                    ->searchable()
                    ->url(fn ($record) => TicketResource::getUrl('index', ['tableFilters[event_id][value]' => $record->event_id])),
                // Tables\Columns\TextColumn::make('event_id'),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('start_date'),
                Tables\Columns\TextColumn::make('end_date'),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                Action::make('scanTicket')
                    ->label('Scan Ticket')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->url(fn ($record) => TicketScan::getUrl(['record' => $record])),
                    // ->url(fn ($record) => Settings::getUrl(['record' => $record])),
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
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
            'ticket-scan' => Pages\TicketScan::route('/{record}/ticket-scan'),
            'settings' => Pages\Settings::route('/{record}/settings'),
        ];
    }
}

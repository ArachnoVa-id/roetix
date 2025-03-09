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
use Filament\Infolists;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\Livewire;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class EventResource extends Resource implements HasShieldPermissions
{

    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view events',
            'create event',
            'edit event',
            'delete event',
            'scan ticket',
        ];
    }

    public static function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->schema([
                Infolists\Components\TextEntry::make('name')
                    ->label('NAME')
                    ->icon('heroicon-m-film'),
                Infolists\Components\TextEntry::make('slug')
                    ->label('SLUG')
                    ->icon('heroicon-m-magnifying-glass-plus'),
                Infolists\Components\TextEntry::make('category')
                    ->label('CATEGORY')
                    ->icon('heroicon-m-tag'),
                Infolists\Components\TextEntry::make('status')
                    ->label('STATUS')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('primary'),
                Infolists\Components\TextEntry::make('start_date')
                    ->label('START')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('end_date')
                    ->label('END')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('location')
                    ->label('LOCATION')
                    ->icon('heroicon-m-map-pin'),
            ])->columns(2),
            Infolists\Components\Tabs::make('Tabs')
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('Settings')
                        ->schema([
                            Livewire::make('event-settings'),
                        ]),
                    Infolists\Components\Tabs\Tab::make('Scan Tickets')
                        ->schema([
                            Livewire::make('event-scan-ticket', ['eventId' => $infolist->record->event_id])
                        ]),
                ])
                ->columnSpan('full'),
        ]);
    }

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
                    ->url(fn($record) => TicketResource::getUrl('index', ['tableFilters[event_id][value]' => $record->event_id])),
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
                    ->url(fn($record) => TicketScan::getUrl(['record' => $record])),
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
            'view' => Pages\ViewEvent::route('/{record}'),
            'ticket-scan' => Pages\TicketScan::route('/{record}/ticket-scan'),
            'settings' => Pages\Settings::route('/{record}/settings'),
        ];
    }
}

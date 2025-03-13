<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Event;
use Filament\Infolists;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Livewire;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\EventResource\Pages;
use App\Filament\Admin\Resources\EventResource\Pages\TicketScan;
use App\Filament\Admin\Resources\EventResource\RelationManagers;
use Filament\Infolists\Components\Actions\Action as InfolistAction;


class EventResource extends Resource
{

    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin', 'event-organizer']);
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
                Infolists\Components\Actions::make([
                    InfolistAction::make('editSeats')
                        ->label('Edit Seats')
                        ->icon('heroicon-m-pencil-square')
                        ->button()
                        ->color('primary')
                        ->url(fn($record) => "/seats/edit?event_id={$record->event_id}")
                        ->openUrlInNewTab(),
                ]),
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
                    ->maxLength(255)
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $foundEvent = Event::where('name', $get('name'))->first();
                        if ($foundEvent) {
                            $set('name', '');
                        }
                        $set('slug', Str::slug($get('name')));
                    })
                    ->debounce(1000),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->readOnly(),
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
                Forms\Components\Select::make('venue_id')
                    ->options(
                        \App\Models\Venue::all()->pluck('name', 'venue_id')
                    )
                    ->required()
                    ->label('Venue')
                    ->placeholder('Select Venue'),
                Forms\Components\TextInput::make('team_code')
                    ->label('Team')
                    ->default($tenant_name)
                    ->hidden()
                    ->readOnly()
                    ->required(),
                Forms\Components\Hidden::make('team_id')
                    ->default($tenant)
                    ->required(),

                // Adding Repeater for ticket categories
                Forms\Components\Fieldset::make('Ticket Categories & Price Bounds')
                    ->schema([
                        Forms\Components\Repeater::make('ticket_categories')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Category Name')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->rgb()
                                    ->label('Category Color')
                                    ->required(),

                                // Adding Repeater for ticket time bound price
                                Forms\Components\Repeater::make('event_category_timebound_prices')
                                    ->schema([
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Start Date')
                                            ->required(),

                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('End Date')
                                            ->required(),

                                        Forms\Components\TextInput::make('price')
                                            ->label('Price')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required(),
                                    ])
                                    ->label('Ticket Time Bound Price')
                            ])

                            ->label('Ticket Categories')
                            ->columnSpan(2)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event Name')
                    ->sortable()
                    ->searchable(),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Action::make('scanTicket')
                    //     ->label('Scan Ticket')
                    //     ->icon('heroicon-o-qr-code')
                    //     ->color('success')
                    //     ->url(fn($record) => TicketScan::getUrl(['record' => $record])),
                ]),
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
        ];
    }
}

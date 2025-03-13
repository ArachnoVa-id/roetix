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
                    ->label('Name')
                    ->icon('heroicon-m-film'),
                Infolists\Components\TextEntry::make('slug')
                    ->label('Slug')
                    ->icon('heroicon-m-magnifying-glass-plus'),
                Infolists\Components\TextEntry::make('category')
                    ->label('Category')
                    ->icon('heroicon-m-tag'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('primary'),
                Infolists\Components\TextEntry::make('start_date')
                    ->label('Start')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('end_date')
                    ->label('End')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('location')
                    ->label('Location')
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
                        ])
                ])
                ->columnSpan('full'),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Event Variables')
                    ->columnSpan('full')
                    ->schema([
                        Forms\Components\Tabs\Tab::make('General')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // reject if name already used
                                        $event_id = $get('event_id');
                                        $foundEvent = Event::where('name', $get('name'))->first();
                                        if ($foundEvent && $foundEvent->event_id != $event_id) {
                                            $set('name', null);
                                            $set('slug', null);
                                        }

                                        // incrementing slug if exist same slug
                                        $increment = 0;
                                        $base_slug = Str::slug($get('name'));
                                        $slug = $base_slug;

                                        $foundEvent = Event::where('slug', $slug)->first();

                                        while ($foundEvent && $foundEvent->event_id != $event_id) {
                                            if ($increment > 0) {
                                                $slug = $base_slug . '-' . $increment;
                                                $foundEvent = Event::where('slug', $slug)->first();
                                            }
                                            $increment++;
                                        }
                                        $set('slug', $slug);
                                    })
                                    ->debounce(1000),
                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // incrementing slug if exist same slug
                                        $increment = 0;
                                        $base_slug = Str::slug($get('slug'));
                                        $slug = $base_slug;

                                        // current event id
                                        $event_id = $get('event_id');
                                        $foundEvent = Event::where('slug', $slug)->first();

                                        while ($foundEvent && $foundEvent->event_id != $event_id) {
                                            if ($increment > 0) {
                                                $slug = $base_slug . '-' . $increment;
                                                $foundEvent = Event::where('slug', $slug)->first();
                                            }
                                            $increment++;
                                        }
                                        $set('slug', $slug);
                                    })
                                    ->reactive(),
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
                                    ->label('Start Date')
                                    ->minDate(now()->toDateString())
                                    ->required()
                                    ->reactive(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->minDate(fn(Forms\Get $get) => $get('start_date'))
                                    ->required(),
                                Forms\Components\Select::make('venue_id')
                                    ->options(
                                        \App\Models\Venue::all()->pluck('name', 'venue_id')
                                    )
                                    ->required()
                                    ->label('Venue')
                                    ->placeholder('Select Venue')
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $venue = \App\Models\Venue::find($get('venue_id'));
                                        if ($venue) {
                                            $set('location', $venue->location);
                                        }
                                    })
                                    ->reactive(),
                                Forms\Components\TextInput::make('location')
                                    ->required(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Timeline')
                            ->schema([
                                Forms\Components\Repeater::make('ticket_categories')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Category Name')
                                            ->required(),
                                        Forms\Components\ColorPicker::make('color')
                                            ->hex()
                                            ->label('Category Color')
                                            ->required(),
                                        Forms\Components\Section::make('Timeline')
                                            ->schema([
                                                Forms\Components\Repeater::make('event_category_timebound_prices')
                                                    ->columns(2)
                                                    ->grid(2)
                                                    ->schema([
                                                        Forms\Components\DatePicker::make('start_date')
                                                            ->label('Start Date')
                                                            ->minDate(now()->toDateString())
                                                            ->reactive()
                                                            ->columnSpan(1)
                                                            ->required(),

                                                        Forms\Components\DatePicker::make('end_date')
                                                            ->label('End Date')
                                                            ->minDate(fn(Forms\Get $get) => $get('start_date'))
                                                            ->columnSpan(1)
                                                            ->required(),

                                                        Forms\Components\TextInput::make('price')
                                                            ->columnSpan(2)
                                                            ->label('Price')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->required(),
                                                    ])
                                                    ->label('')
                                            ])
                                            ->label('Ticket Categories')
                                    ])
                                    ->label('')
                            ]),
                        Forms\Components\Tabs\Tab::make('Locking')
                            ->schema([
                                Forms\Components\Toggle::make('is_locked')
                                    ->label('Is Locked'),
                                Forms\Components\TextInput::make('locked_password')
                                    ->label('Locked Password'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Maintenance')
                            ->schema([
                                Forms\Components\Toggle::make('is_maintenance')
                                    ->label('Is Maintenance'),
                                Forms\Components\TextInput::make('maintenance_title')
                                    ->label('Maintenance Title'),
                                Forms\Components\TextInput::make('maintenance_message')
                                    ->label('Maintenance Message'),
                                Forms\Components\DatePicker::make('maintenance_expected_finish')
                                    ->label('Maintenance Expected Finish'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Colors')
                            ->columns(4)
                            ->schema([

                                Forms\Components\ColorPicker::make('primary_color')
                                    ->hex()
                                    ->required()
                                    ->label('Primary Color'),
                                Forms\Components\ColorPicker::make('secondary_color')
                                    ->hex()
                                    ->required()
                                    ->label('Secondary Color'),
                                Forms\Components\ColorPicker::make('text_primary_color')
                                    ->hex()
                                    ->required()
                                    ->label('Text Primary Color'),
                                Forms\Components\ColorPicker::make('text_secondary_color')
                                    ->hex()
                                    ->required()
                                    ->label('Text Secondary Color'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Identity')
                            ->schema([
                                Forms\Components\TextInput::make('logo')
                                    ->label('Logo'),
                                Forms\Components\TextInput::make('favicon')
                                    ->label('Favicon'),
                            ])
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

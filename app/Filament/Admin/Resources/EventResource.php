<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Event;
use Filament\Actions;
use Filament\Infolists;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\EventResource\Pages;
use App\Filament\Admin\Resources\EventResource\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\EventResource\RelationManagers\TicketsRelationManager;

class EventResource extends Resource
{

    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin', 'event-organizer']);
    }

    public static function EditSeatsButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Seating')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color('info')
            ->url(fn($record) => "/seats/edit?event_id={$record->event_id}")
            ->openUrlInNewTab();
        // ->modalHeading('Edit Seating')
        // ->modalContent(function ($record) {
        //     $eventId = $record->event_id;
        //     try {
        //         if (!$eventId) {
        //             // return redirect()->back()->withErrors(['error' => 'Event ID is required']);
        //             return;
        //         }

        //         // Get the event and associated venue
        //         $event = Event::findOrFail($eventId);
        //         $venue = Venue::findOrFail($event->venue_id);

        //         // Get all seats for this venue
        //         $seats = Seat::where('venue_id', $venue->venue_id)
        //             ->orderBy('row')
        //             ->orderBy('column')
        //             ->get();

        //         // Get existing tickets for this event
        //         $existingTickets = Ticket::where('event_id', $eventId)
        //             ->get()
        //             ->keyBy('seat_id');

        //         // Format data for the frontend, prioritizing ticket data
        //         $layout = [
        //             'totalRows' => count(array_unique($seats->pluck('row')->toArray())),
        //             'totalColumns' => $seats->max('column'),
        //             'items' => $seats->map(function ($seat) use ($existingTickets) {
        //                 $ticket = $existingTickets->get($seat->seat_id);

        //                 // Base seat data
        //                 $seatData = [
        //                     'type' => 'seat',
        //                     'seat_id' => $seat->seat_id,
        //                     'seat_number' => $seat->seat_number,
        //                     'row' => $seat->row,
        //                     'column' => $seat->column
        //                 ];

        //                 // Add ticket data if it exists
        //                 if ($ticket) {
        //                     $seatData['status'] = $ticket->status;
        //                     $seatData['ticket_type'] = $ticket->ticket_type;
        //                     $seatData['price'] = $ticket->price;
        //                 } else {
        //                     // Default values for seats without tickets
        //                     $seatData['status'] = 'reserved';
        //                     $seatData['ticket_type'] = 'standard';
        //                     $seatData['price'] = 0;
        //                 }

        //                 return $seatData;
        //             })->values()
        //         ];

        //         // Add stage label
        //         $layout['items'][] = [
        //             'type' => 'label',
        //             'row' => $layout['totalRows'],
        //             'column' => floor($layout['totalColumns'] / 2),
        //             'text' => 'STAGE'
        //         ];

        //         // Get available ticket types for dropdown
        //         $ticketTypes = ['standard', 'VIP'];

        //         return view('modals.edit-seats-modal', [
        //             'layout' => $layout,
        //             'event' => $event,
        //             'venue' => $venue,
        //             'ticketTypes' => $ticketTypes
        //         ]);
        //     } catch (\Exception $e) {
        //         // return redirect()->back()->withErrors(['error' => 'Failed to load seat map: ' . $e->getMessage()]);
        //         return;
        //     }
        // })
        // ->modalSubmitAction(false); // Hide default Filament save button

        // ->modalButton('Close')
        // ->modalHeading('Edit Seating Layout')
        // ->modalWidth('7xl')
        // ->form([
        //     Forms\Components\Placeholder::make('blade_component')
        //         ->content('')
        //         ->extraAttributes(fn($record) => [
        //             'x-html' => '<iframe src="' . route('hello') . '" width="100%" height="500px" style="border: none;"></iframe>',
        //         ]),
        // ]);
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
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('primary'),
                Infolists\Components\TextEntry::make('start_date')
                    ->label('Start Serving')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('event_date')
                    ->label('D-Day')
                    ->icon('heroicon-m-calendar-date-range')
                    ->dateTime(),
                Infolists\Components\TextEntry::make('location')
                    ->label('Location')
                    ->icon('heroicon-m-map-pin'),
            ])->columns(2),
            Infolists\Components\Tabs::make('Tabs')
                ->tabs([
                    Infolists\Components\Tabs\Tab::make('Timeline and Categories')
                        ->schema([
                            Infolists\Components\Section::make('Timeline')
                                ->schema([
                                    Infolists\Components\RepeatableEntry::make('timelineSessions')
                                        ->label('')
                                        ->columns(3)
                                        ->grid(2)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('name'),
                                            Infolists\Components\TextEntry::make('start_date'),
                                            Infolists\Components\TextEntry::make('end_date'),
                                        ])
                                ]),
                            Infolists\Components\Section::make('Categories')
                                ->columnSpan(1)
                                ->schema([
                                    Infolists\Components\RepeatableEntry::make('ticketCategories')
                                        ->label('')
                                        ->columns(2)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('name')
                                                ->columnSpan(1),
                                            Infolists\Components\ColorEntry::make('color')
                                                ->columnSpan(1),
                                            Infolists\Components\RepeatableEntry::make('eventCategoryTimeboundPrices')
                                                ->label('Timeline')
                                                ->grid(3)
                                                ->columnSpan(2)
                                                ->columns(3)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('price'),
                                                    Infolists\Components\TextEntry::make('is_active')
                                                        ->label('Status')
                                                        ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive'),
                                                    Infolists\Components\TextEntry::make('timelineSession.name')
                                                        ->label('Timeline')
                                                ])
                                        ])
                                ]),
                        ]),
                    Infolists\Components\Tabs\Tab::make('Event Variables')
                        ->columns(4)
                        ->schema([
                            Infolists\Components\Section::make('Lock')
                                ->relationship('eventVariables')
                                ->columnSpan(1)
                                ->schema([
                                    Infolists\Components\TextEntry::make('is_locked')
                                        ->label('Is Locked')
                                        ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                                    Infolists\Components\TextEntry::make('locked_password')
                                        ->label('Locked Password'),
                                ]),

                            Infolists\Components\Section::make('Maintenance')
                                ->relationship('eventVariables')
                                ->columnSpan(1)
                                ->schema([
                                    Infolists\Components\TextEntry::make('is_maintenance')
                                        ->label('Is Maintenance')
                                        ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                                    Infolists\Components\TextEntry::make('maintenance_title')
                                        ->label('Title'),

                                    Infolists\Components\TextEntry::make('maintenance_message')
                                        ->label('Message'),

                                    Infolists\Components\TextEntry::make('maintenance_expected_finish')
                                        ->label('Expected Finish'),
                                ]),

                            Infolists\Components\Section::make('Logo')
                                ->relationship('eventVariables')
                                ->columnSpan(1)
                                ->schema([
                                    Infolists\Components\TextEntry::make('logo')
                                        ->label('Logo'),

                                    Infolists\Components\TextEntry::make('logo_alt')
                                        ->label('Logo Alt'),

                                    Infolists\Components\TextEntry::make('favicon')
                                        ->label('Favicon'),

                                ]),

                            Infolists\Components\Section::make('Colors')
                                ->relationship('eventVariables')
                                ->columnSpan(1)
                                ->schema([
                                    Infolists\Components\ColorEntry::make('primary_color')
                                        ->label('Primary Color'),

                                    Infolists\Components\ColorEntry::make('secondary_color')
                                        ->label('Secondary Color'),

                                    Infolists\Components\ColorEntry::make('text_primary_color')
                                        ->label('Text Primary Color'),

                                    Infolists\Components\ColorEntry::make('text_secondary_color')
                                        ->label('Text Secondary Color'),
                                ])
                        ]),
                    // Infolists\Components\Tabs\Tab::make('Orders')
                    //     ->schema([
                    //         \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                    //             ->manager(OrdersRelationManager::class)
                    //     ]),
                    Infolists\Components\Tabs\Tab::make('Tickets')
                        ->schema([
                            \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                ->manager(TicketsRelationManager::class)
                        ]),
                    Infolists\Components\Tabs\Tab::make('Scan Tickets')
                        ->schema([
                            Infolists\Components\Livewire::make('event-scan-ticket', ['eventId' => $infolist->record->event_id])
                        ])
                ])
                ->columnSpan('full'),
        ]);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        // get current form values
        $currentModel = $form->model;
        $modelExists = !is_string($currentModel);

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
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'planned' => 'planned',
                                        'active' => 'active',
                                        'completed' => 'completed',
                                        'cancelled' => 'cancelled'
                                    ])
                                    ->required(),
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Start Date')
                                    ->minDate(
                                        fn() => $modelExists
                                            ? min(now(), optional($currentModel->start_date) ?? now())
                                            : now()
                                    )
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $carbonifiedStart = Carbon::parse($get('start_date'));
                                        $carbonifiedEnd = Carbon::parse($get('event_date'));

                                        if ($carbonifiedStart >= $carbonifiedEnd) {
                                            $set('event_date', null);
                                        }

                                        $copyTimeline = $get('event_timeline');

                                        foreach ($copyTimeline as $key => $timeline) {
                                            $carbonifiedTLStart = Carbon::parse($timeline['start_date']);
                                            $carbonifiedTLEnd = Carbon::parse($timeline['end_date']);

                                            // nullify all the start_date and event_date that is outside the constraints
                                            if ($carbonifiedTLStart < $carbonifiedStart || $carbonifiedTLStart > $carbonifiedEnd) {
                                                $copyTimeline[$key]['start_date'] = null;
                                            }
                                            if ($carbonifiedTLEnd < $carbonifiedStart || $carbonifiedTLEnd > $carbonifiedEnd) {
                                                $copyTimeline[$key]['end_date'] = null;
                                            }
                                        }

                                        $set('event_timeline', $copyTimeline);
                                    }),
                                Forms\Components\DateTimePicker::make('event_date')
                                    ->label('Event Date')
                                    ->minDate(
                                        fn(Forms\Get $get) =>
                                        // Carbon::parse($get('start_date'))->addDay()
                                        Carbon::parse($get('start_date'))
                                    )
                                    ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $copyTimeline = $get('event_timeline');

                                        $carbonifiedStart = Carbon::parse($get('start_date'));
                                        $carbonifiedEnd = Carbon::parse($get('event_date'));

                                        foreach ($copyTimeline as $key => $timeline) {
                                            $carbonifiedTLStart = Carbon::parse($timeline['start_date']);
                                            $carbonifiedTLEnd = Carbon::parse($timeline['event_date']);
                                            // nullify all the start_date and event_date that is outside the constraints
                                            if ($carbonifiedTLStart < $carbonifiedStart || $carbonifiedTLStart > $carbonifiedEnd) {
                                                $copyTimeline[$key]['start_date'] = null;
                                            }
                                            if ($carbonifiedTLEnd < $carbonifiedStart || $carbonifiedTLEnd > $carbonifiedEnd) {
                                                $copyTimeline[$key]['event_date'] = null;
                                            }
                                        }

                                        $set('event_timeline', $copyTimeline);
                                    }),
                                Forms\Components\Select::make('venue_id')
                                    ->searchable()
                                    ->optionsLimit(5)
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
                                Forms\Components\Repeater::make('event_timeline')
                                    ->label('')
                                    ->columns(5)
                                    ->minItems(1)
                                    ->live(debounce: 500)
                                    ->reorderable(false)
                                    ->defaultItems(0)
                                    ->relationship('timelineSessions')
                                    ->addable(function (Forms\Get $get) {
                                        if (!$get('event_timeline')) return true;
                                        // if empty, addable true
                                        if (count($get('event_timeline')) == 0) return true;
                                        else {
                                            // if exist, ensure no null in the whole body
                                            $eventTimelines = $get('event_timeline');
                                            $existsNull = false;
                                            foreach ($eventTimelines as $timeline) {
                                                foreach (array_values($timeline) as $value) {
                                                    if ($value == null) {
                                                        $existsNull = true;
                                                        break;
                                                    }
                                                }

                                                if ($existsNull) break;
                                            }

                                            return !$existsNull;
                                        }
                                    })
                                    ->deletable(fn(Forms\Get $get) => $get('event_timeline') && count($get('event_timeline')) > 1)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $eventTimelines = $get('event_timeline') ?? [];

                                        // Normalize the keys (remove "record-" if present to work in edit mode)
                                        $normalizedTimelines = [];
                                        foreach ($eventTimelines as $key => $value) {
                                            $normalizedKey = preg_replace('/^record-/', '', $key);
                                            $value['timeline_id'] = $normalizedKey;
                                            $normalizedTimelines[$normalizedKey] = $value;
                                        }

                                        $ticketCategories = $get('ticket_categories');
                                        $newCategories = [];

                                        foreach ($ticketCategories as $category) {
                                            $existingPrices = $category['event_category_timebound_prices'] ?? [];

                                            $newPrices = [];

                                            // Scan existing prices, update if found, add if missing
                                            foreach ($normalizedTimelines as $timelineId => $timeline) {
                                                $found = false;

                                                foreach ($existingPrices as $price) {
                                                    if (($price['timeline_id'] ?? null) == $timelineId) {
                                                        $price['name'] = $timeline['name'] ?? "";
                                                        $newPrices[] = $price;
                                                        $found = true;
                                                        break;
                                                    }
                                                }

                                                if (!$found) {
                                                    $newPrices[] = [
                                                        'timeline_id' => $timelineId,
                                                        'price' => 0,
                                                        'name' => $timeline['name'] ?? "",
                                                        'is_active' => true
                                                    ];
                                                }
                                            }

                                            // Reorder prices based on the normalized timeline order
                                            $reorderedPrices = [];
                                            foreach ($normalizedTimelines as $timelineId => $timeline) {
                                                foreach ($newPrices as $newPrice) {
                                                    if ($timelineId == $newPrice['timeline_id']) {
                                                        $reorderedPrices[] = $newPrice;
                                                        break;
                                                    }
                                                }
                                            }

                                            // Preserve other category attributes
                                            $newCategories[] = [
                                                'ticket_category_id' => $category['ticket_category_id'] ?? '-',
                                                'name' => $category['name'] ?? '',
                                                'color' => $category['color'] ?? '',
                                                'event_category_timebound_prices' => $reorderedPrices
                                            ];
                                        }

                                        // Save the corrected ticket categories
                                        $set('ticket_categories', $newCategories);
                                    })
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Name')
                                            ->columnSpan(1)
                                            ->default(null)
                                            ->required()
                                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                                // calculate how many names that is the same
                                                $array = $get('../');

                                                // in array, find how many with name exactly as state
                                                $count = collect($array)->filter(function ($item) use ($state) {
                                                    return $item['name'] == $state;
                                                })->count();

                                                // if only one, then it is already unique, else reject state
                                                if ($count > 1) $set('name', null);
                                            }),
                                        Forms\Components\DateTimePicker::make('start_date')
                                            ->label('Start Date')
                                            ->default(null)
                                            ->reactive()
                                            ->columnSpan(2)
                                            ->required()
                                            ->minDate(function (Forms\Get $get) {
                                                $array = $get('../'); // Get all sibling entries
                                                $current_body = [
                                                    'name' => $get('name'),
                                                    'start_date' => $get('start_date'),
                                                    'end_date' => $get('end_date')
                                                ];

                                                // Clean indexedArray objects to only keep relevant fields
                                                $indexedArray = array_map(fn($item) => [
                                                    'name' => $item['name'],
                                                    'start_date' => $item['start_date'],
                                                    'end_date' => $item['end_date']
                                                ], array_values($array));

                                                // Custom search for the index of the current entry
                                                $index = null;
                                                foreach ($indexedArray as $key => $item) {
                                                    if (
                                                        $item['name'] === $current_body['name'] &&
                                                        $item['start_date'] === $current_body['start_date'] &&
                                                        $item['end_date'] === $current_body['end_date']
                                                    ) {
                                                        $index = $key;
                                                        break;
                                                    }
                                                }

                                                // If not found, return now (fallback)
                                                if ($index === null) return now();

                                                // First index → compare parent start_date with now
                                                if ($index == 0) {
                                                    $minDate = $get('../../start_date');
                                                    $now = now();

                                                    if ($minDate) {
                                                        $minDate = Carbon::parse($minDate);
                                                        return $minDate->greaterThan($now) ? $minDate : $now;
                                                    }

                                                    return $now;
                                                }

                                                // For other indexes, use previous entry's end_date but allow same day with different time
                                                $prevEndDate = $indexedArray[$index - 1]['end_date'] ?? null;

                                                if ($prevEndDate) {
                                                    $prevEnd = Carbon::parse($prevEndDate);
                                                    // return $prevEnd->isSameDay(now()) ? $prevEnd->addMinute() : $prevEnd->addDay();
                                                    return $prevEnd;
                                                }

                                                return now();
                                            })
                                            ->maxDate(
                                                fn(Forms\Get $get) =>
                                                $get('../../event_date') ? Carbon::parse($get('../../event_date')) : null
                                            )
                                            ->afterStateUpdated(
                                                function (Forms\Set $set, Forms\Get $get) {
                                                    // Restart end_date if date clashes
                                                    $startDate = Carbon::parse($get('start_date'));
                                                    $endDate = Carbon::parse($get('end_date'));

                                                    if ($startDate && $endDate && $startDate->greaterThanOrEqualTo($endDate)) {
                                                        $set('end_date', null); // Reset end_date if it's before start_date
                                                    }
                                                }
                                            )
                                            ->disabled(
                                                function (Forms\Get $get, Forms\Set $set) {
                                                    // check on previous end date
                                                    $array = $get('../');
                                                    $current_body = [
                                                        'name' => $get('name'),
                                                        'start_date' => $get('start_date'),
                                                        'end_date' => $get('end_date')
                                                    ];
                                                    $indexedArray = array_values($array);
                                                    // clean indexedArray objects to only have name, start_date, and end_date
                                                    $indexedArray = array_map(function ($item) {
                                                        return [
                                                            'name' => $item['name'],
                                                            'start_date' => $item['start_date'],
                                                            'end_date' => $item['end_date']
                                                        ];
                                                    }, $indexedArray);
                                                    $index = array_search($current_body, $indexedArray);

                                                    // First index now
                                                    if ($index > 0) return $indexedArray[$index - 1]['end_date'] == null;
                                                    // Second index prev end
                                                    else return false;
                                                }
                                            ),
                                        Forms\Components\DateTimePicker::make('end_date')
                                            ->label('End Date')
                                            ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                            ->minDate(
                                                fn(Forms\Get $get) =>
                                                // Carbon::parse($get('start_date'))->addDay()
                                                Carbon::parse($get('start_date'))
                                            )
                                            ->default(null)
                                            ->columnSpan(2)
                                            ->required()
                                            ->reactive()
                                            ->maxDate(
                                                fn(Forms\Get $get) => $get('../../event_date')
                                                    ? Carbon::parse($get('../../event_date'))->max(now())
                                                    : now()
                                            )

                                            ->afterStateUpdated(
                                                function (Forms\Get $get, Forms\Set $set) {
                                                    // remove next if date overlaps
                                                    $array = $get('../');

                                                    // if end date overlaps start date, reset
                                                    if ($get('start_date') && $get('end_date')) {
                                                        $carbonifiedStart = Carbon::parse($get('start_date'));
                                                        $carbonifiedEnd = Carbon::parse($get('end_date'));

                                                        if ($carbonifiedStart >= $carbonifiedEnd) {
                                                            $set('end_date', null);
                                                        }
                                                    }

                                                    $current_body = [
                                                        'name' => $get('name'),
                                                        'start_date' => $get('start_date'),
                                                        'end_date' => $get('end_date')
                                                    ];
                                                    $indexedArray = array_values($array);
                                                    // clean indexedArray objects to only have name, start_date, and end_date
                                                    $indexedArray = array_map(function ($item) {
                                                        return [
                                                            'name' => $item['name'],
                                                            'start_date' => $item['start_date'],
                                                            'end_date' => $item['end_date']
                                                        ];
                                                    }, $indexedArray);
                                                    $index = array_search($current_body, $indexedArray);

                                                    // First index now
                                                    $lastIdx = count($indexedArray) - 1;
                                                    if ($index != $lastIdx) {
                                                        $keysArray = array_keys($array);
                                                        $next = $indexedArray[$index + 1];

                                                        $carbonifiedNext = Carbon::parse($next['start_date']);
                                                        $carbonifiedEnd = Carbon::parse($get('end_date'));

                                                        if ($carbonifiedNext <= $carbonifiedEnd) {
                                                            $nextUUID = $keysArray[$index + 1];
                                                            $array[$nextUUID]['start_date'] = null;
                                                            $set('../', $array);
                                                        }
                                                    }
                                                }
                                            ),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Ticket Prices')
                            ->schema([
                                // add a text info
                                Forms\Components\Placeholder::make('empty_message')
                                    ->label('')
                                    ->content('No timeline has set. Please set the timeline first!')
                                    ->hidden(
                                        fn(Forms\Get $get) =>
                                        $get('event_timeline') && count($get('event_timeline')) > 0
                                    ),
                                Forms\Components\Repeater::make('ticket_categories')
                                    ->relationship('ticketCategories')
                                    ->minItems(1)
                                    ->columns(5)
                                    ->defaultItems(0)
                                    ->reorderable(false)
                                    ->addable(function (Forms\Get $get) {
                                        $timelineExists = $get('event_timeline') && count($get('event_timeline')) > 0;

                                        if (!$timelineExists) return false;

                                        $ticketCategories = $get('ticket_categories');
                                        // if empty, addable true
                                        if (!$ticketCategories) return true;
                                        else {
                                            // if exist, ensure no null in the whole body
                                            $existsNull = false;
                                            foreach ($ticketCategories as $timeline) {
                                                foreach (array_values($timeline) as $value) {
                                                    if ($value == null) {
                                                        $existsNull = true;
                                                        break;
                                                    }
                                                }

                                                if ($existsNull) break;
                                            }

                                            return !$existsNull;
                                        }
                                    })
                                    ->deletable(fn(Forms\Get $get) => count($get('ticket_categories')) > 1)
                                    ->live(debounce: 1000)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $currentCategories = $get('ticket_categories') ?? [];
                                        // retrieve cache
                                        $timeline = $get('event_timeline');
                                        // normalize the timeline
                                        $usingTimeline = [];
                                        foreach ($timeline as $key => $value) {
                                            $normalizedKey = preg_replace('/^record-/', '', $key);
                                            $value['timeline_id'] = $normalizedKey;
                                            $usingTimeline[$normalizedKey] = $value;
                                        }

                                        // ensure all categories have the same timeline prices
                                        $newCategories = [];

                                        foreach ($currentCategories as $category) {
                                            // delete all that doesnt share the same timeline and save all that share the same timeline
                                            $newPrices = [];

                                            // assume all keys non-existing
                                            $nonExistingKeys = [];
                                            foreach ($usingTimeline as $timeline) {
                                                $nonExistingKeys[] = $timeline['timeline_id'];
                                            }

                                            $timeboundPrices = $category['event_category_timebound_prices'] ?? [];

                                            foreach ($timeboundPrices as $price) {
                                                if (!is_array($price)) {
                                                    // ignore
                                                    continue;
                                                }
                                                if (!isset($price['timeline_id'])) {
                                                    // ignore
                                                    continue;
                                                }
                                                foreach ($usingTimeline as $timeline) {
                                                    if ($price['timeline_id'] == $timeline['timeline_id']) {
                                                        // set price values
                                                        $selectedTimeline = collect($usingTimeline)->firstWhere('timeline_id', $timeline['timeline_id']);
                                                        $price['name'] = $selectedTimeline['name'];
                                                        // insert to new prices
                                                        $newPrices[] = $price;
                                                        // remove id from nonExistingKeys
                                                        $nonExistingKeys = array_filter($nonExistingKeys, function ($key) use ($timeline) {
                                                            return $key !== $timeline['timeline_id'];
                                                        });
                                                        // break id seeking
                                                        break;
                                                    }
                                                }
                                            }

                                            // fill the nonExistingKeys to the category
                                            foreach ($nonExistingKeys as $key) {
                                                $selectedTimeline = collect($usingTimeline)->firstWhere('timeline_id', $key);
                                                $newPrices[] = [
                                                    'timeline_id' => $key,
                                                    'price' => 0,
                                                    'name' => $selectedTimeline['name'] ?? "",
                                                    'is_active' => true
                                                ];
                                            }

                                            // reorder the prices according to the timeline
                                            $reorderedPrices = [];
                                            foreach ($usingTimeline as $timeline) {
                                                foreach ($newPrices as $newPrice) {
                                                    if ($timeline['timeline_id'] == $newPrice['timeline_id']) {
                                                        $reorderedPrices[] = $newPrice;
                                                        break;
                                                    }
                                                }
                                            }

                                            // update
                                            $newCategories[] = [
                                                'ticket_category_id' => $category['ticket_category_id'] ?? '-',
                                                'name' => $category['name'] ?? '',
                                                'color' => $category['color'] ?? '',
                                                'event_category_timebound_prices' => $reorderedPrices
                                            ];
                                        }
                                        // update
                                        $set('ticket_categories', $newCategories);
                                    })
                                    ->schema([
                                        Forms\Components\Section::make('Category')
                                            ->columnSpan(1)
                                            ->schema([
                                                Forms\Components\Hidden::make('ticket_category_id')
                                                    ->default('-'),
                                                Forms\Components\TextInput::make('name')
                                                    ->default('')
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                                        // calculate how many names that is the same
                                                        $array = $get('../');

                                                        // in array, find how many with name exactly as state
                                                        $count = collect($array)->filter(function ($item) use ($state) {
                                                            return $item['name'] == $state;
                                                        })->count();

                                                        // if only one, then it is already unique, else reject state
                                                        if ($count > 1) $set('name', null);
                                                    })
                                                    ->required(),
                                                Forms\Components\ColorPicker::make('color')
                                                    ->default('')
                                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                                        // calculate how many names that is the same
                                                        $array = $get('../');

                                                        // in array, find how many with name exactly as state
                                                        $count = collect($array)->filter(function ($item) use ($state) {
                                                            return $item['color'] == $state;
                                                        })->count();

                                                        // if only one, then it is already unique, else reject state
                                                        if ($count > 1) $set('color', null);
                                                    })
                                                    ->hex()
                                                    ->required(),
                                            ]),
                                        Forms\Components\Section::make('Timeline')
                                            ->columnSpan(4)
                                            ->schema([
                                                Forms\Components\Repeater::make('event_category_timebound_prices')
                                                    ->relationship('eventCategoryTimeboundPrices')
                                                    ->defaultItems(0)
                                                    ->grid(3)
                                                    ->label('')
                                                    ->reorderable(false)
                                                    ->deletable(false)
                                                    ->addable(false)
                                                    ->schema([
                                                        Forms\Components\Group::make([
                                                            Forms\Components\Placeholder::make('title')
                                                                ->label(fn(Forms\Get $get) => $get('name')),
                                                            Forms\Components\Toggle::make('is_active')
                                                                ->default(true)
                                                                ->label(fn($state) => $state ? 'On' : 'Off'),
                                                        ])->columns(2),
                                                        Forms\Components\Hidden::make('name')
                                                            ->default('')
                                                            ->formatStateUsing(function (Forms\Get $get) {
                                                                $id = $get('timeline_id');
                                                                $timeline = $get('../../../../event_timeline');

                                                                // timeline has key => obj where one of th obj is timeline_id to matched with $id, find and acquire
                                                                $found = collect($timeline)->firstWhere('timeline_id', $id);

                                                                if ($found) return $found['name'];
                                                                else return '';
                                                            }),

                                                        Forms\Components\TextInput::make('price')
                                                            ->default(0)
                                                            ->hidden(fn(Forms\Get $get) => !$get('is_active'))
                                                            ->prefix('Rp')
                                                            ->inputMode('decimal')
                                                            ->numeric()
                                                            ->required(),
                                                    ])
                                            ])
                                    ])
                                    ->label('')
                            ]),
                        Forms\Components\Tabs\Tab::make('Locking')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Section::make('')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_locked')
                                            ->label('Is Locked')
                                    ]),
                                Forms\Components\TextInput::make('locked_password')
                                    ->columnSpan(1)
                                    ->label('Password'),
                            ]),
                        Forms\Components\Tabs\Tab::make('Maintenance')
                            ->columns(2)
                            ->schema([
                                Forms\Components\Section::make('')
                                    ->columnSpan(1)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_maintenance')
                                            ->label('Is Maintenance')
                                    ]),
                                Forms\Components\TextInput::make('maintenance_title')
                                    ->label('Title'),
                                Forms\Components\DateTimePicker::make('maintenance_expected_finish')
                                    ->label('Expected Finish')
                                    ->minDate(
                                        fn() => $modelExists
                                            ? min(now(), optional($currentModel->eventVariables->maintenance_expected_finish) ? Carbon::parse($currentModel->eventVariables->maintenance_expected_finish) : now())
                                            : now()
                                    ),
                                Forms\Components\TextInput::make('maintenance_message')
                                    ->label('Message'),
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
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('logo')
                                    ->label('Logo'),
                                Forms\Components\TextInput::make('logo_alt')
                                    ->label('Logo Alt'),
                                Forms\Components\TextInput::make('favicon')
                                    ->label('Favicon'),
                            ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $role = $user ? $user->role : null;

        $defaultActions = [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ];

        if ($role == 'event-organizer') $defaultActions[] = self::EditSeatsButton(Tables\Actions\Action::make('editSeats'));

        $defaultActions[] = Tables\Actions\DeleteAction::make();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->limit(50)
                    ->label('Event Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_date')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('location')
                    ->limit(50)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->multiple()
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make($defaultActions),
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

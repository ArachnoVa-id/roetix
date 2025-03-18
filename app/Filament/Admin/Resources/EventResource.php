<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use App\Models\Seat;
use Filament\Tables;
use App\Models\Event;
use App\Models\Venue;
use Filament\Actions;
use App\Models\Ticket;
use Livewire\Livewire;
use Filament\Infolists;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Cache;
use App\Filament\Admin\Resources\EventResource\Pages;
use Filament\Infolists\Infolist;
use Illuminate\Console\View\Components\Info;

class EventResource extends Resource
{

    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                Infolists\Components\TextEntry::make('category')
                    ->label('Category')
                    ->icon('heroicon-m-tag'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->icon('heroicon-m-exclamation-triangle')
                    ->color('primary'),
                Infolists\Components\TextEntry::make('event_date')
                    ->label('Event Date')
                    ->icon('heroicon-m-calendar')
                    ->dateTime(),
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
                                                ->columnSpan(2)
                                                ->columns(3)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('price'),
                                                    Infolists\Components\TextEntry::make('is_active'),
                                                    Infolists\Components\TextEntry::make('timelineSession.name')
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
                                        ->label('Maintenance Title'),

                                    Infolists\Components\TextEntry::make('maintenance_message')
                                        ->label('Maintenance Message'),

                                    Infolists\Components\TextEntry::make('maintenance_expected_finish')
                                        ->label('Maintenance Expected Finish'),
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
                                Forms\Components\DateTimePicker::make('event_date')
                                    ->label('Event Date/Time')
                                    ->required()
                                    ->minDate(now()->toDateString()),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->minDate(fn() => $modelExists
                                        ? min(now()->toDateString(), optional($currentModel->start_date)->toDateString() ?? now()->toDateString())
                                        : now()->toDateString())
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        if ($get('start_date') >= $get('end_date')) {
                                            $set('end_date', null);
                                        }

                                        $copyTimeline = $get('event_timeline');

                                        foreach ($copyTimeline as $key => $timeline) {
                                            // nullify all the start_date and end_date that is outside the constraints
                                            if ($timeline['start_date'] < $get('start_date') || $timeline['start_date'] > $get('end_date')) {
                                                $copyTimeline[$key]['start_date'] = null;
                                            }
                                            if ($timeline['end_date'] < $get('start_date') || $timeline['end_date'] > $get('end_date')) {
                                                $copyTimeline[$key]['end_date'] = null;
                                            }
                                        }

                                        $set('event_timeline', $copyTimeline);
                                    }),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->minDate(fn(Forms\Get $get) => Carbon::parse($get('start_date'))->addDay()->toDateString())
                                    ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $copyTimeline = $get('event_timeline');

                                        foreach ($copyTimeline as $key => $timeline) {
                                            // nullify all the start_date and end_date that is outside the constraints
                                            if ($timeline['start_date'] < $get('start_date') || $timeline['start_date'] > $get('end_date')) {
                                                $copyTimeline[$key]['start_date'] = null;
                                            }
                                            if ($timeline['end_date'] < $get('start_date') || $timeline['end_date'] > $get('end_date')) {
                                                $copyTimeline[$key]['end_date'] = null;
                                            }
                                        }

                                        $set('event_timeline', $copyTimeline);
                                    }),
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
                                Forms\Components\Repeater::make('event_timeline')
                                    ->label('')
                                    ->columns(4)
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
                                            ->default(null)
                                            ->columnSpan(2)
                                            ->required(),
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Start Date')
                                            ->default(null)
                                            ->reactive()
                                            ->columnSpan(1)
                                            ->required()
                                            ->minDate(
                                                function (Forms\Get $get) {
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
                                                    if ($index == 0) {
                                                        $minDate = $get('../../start_date');
                                                        // compare minDate and now and take the latest
                                                        $now = now();
                                                        if ($minDate) {
                                                            $minDate = Carbon::parse($minDate);
                                                            if ($minDate->gt($now)) return $minDate->toDateString();
                                                        }

                                                        return $now->toDateString();
                                                    }
                                                    // Second index prev end
                                                    else return Carbon::parse($indexedArray[$index - 1]['end_date'])->addDay()->toDateString();
                                                }
                                            )
                                            ->maxDate(
                                                function (Forms\Get $get) {
                                                    // First index now
                                                    $maxDate = $get('../../end_date');
                                                    // compare minDate and now and take the latest
                                                    if ($maxDate) {
                                                        $maxDate = Carbon::parse($maxDate);
                                                        return $maxDate->toDateString();
                                                    }

                                                    return null;
                                                }
                                            )
                                            ->afterStateUpdated(
                                                function (Forms\Set $set, Forms\Get $get) {
                                                    // Restart end_date if date clashes
                                                    if ($get('start_date') >= $get('end_date')) $set('end_date', null);
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
                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('End Date')
                                            ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                            ->minDate(fn(Forms\Get $get) => Carbon::parse($get('start_date'))->addDay()->toDateString())
                                            ->default(null)
                                            ->columnSpan(1)
                                            ->required()
                                            ->reactive()
                                            ->maxDate(
                                                function (Forms\Get $get) {
                                                    // First index now
                                                    $maxDate = $get('../../end_date');
                                                    // compare minDate and now and take the latest
                                                    if ($maxDate) {
                                                        $maxDate = Carbon::parse($maxDate);
                                                        return $maxDate->toDateString();
                                                    }

                                                    return null;
                                                }
                                            )
                                            ->afterStateUpdated(
                                                function (Forms\Get $get, Forms\Set $set) {
                                                    // remove next if date overlaps
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
                                                    $lastIdx = count($indexedArray) - 1;
                                                    if ($index != $lastIdx) {
                                                        $keysArray = array_keys($array);
                                                        $next = $indexedArray[$index + 1];

                                                        if ($next['start_date'] <= $get('end_date')) {
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
                                    ->minDate(fn() => $modelExists
                                        ? min(now()->toDateString(), optional($currentModel->start_date)->toDateString() ?? now()->toDateString())
                                        : now()->toDateString())
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Event Name')
                    ->sortable()
                    ->searchable(),
                // Tables\Columns\TextColumn::make('event_id'),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('event_date')
                    ->dateTime()
                    ->sortable(),
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
                    self::EditSeatsButton(Tables\Actions\Action::make('editSeats')),
                    Tables\Actions\DeleteAction::make(),
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

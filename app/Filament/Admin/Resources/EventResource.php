<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Event;
use App\Models\Venue;
use Filament\Actions;
use App\Enums\UserRole;
use Filament\Infolists;
use App\Enums\EventStatus;
use App\Enums\VenueStatus;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\EventResource\Pages;
use App\Filament\Admin\Resources\EventResource\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\EventResource\RelationManagers\TicketsRelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;
use Mews\Purifier\Facades\Purifier;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN, UserRole::EVENT_ORGANIZER]);
    }

    public static function canCreate(): bool
    {
        $user = session('auth_user');

        if (!$user || !$user->isAllowedInRoles([UserRole::EVENT_ORGANIZER])) {
            return false;
        }

        $tenant_id = Filament::getTenant()->id;

        $team = $user->teams()->where('teams.id', $tenant_id)->first();

        if (!$team) {
            return false;
        }

        return $team->event_quota > 0;
    }

    public static function canDelete(Model $record): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN]);
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        $user = session('auth_user');

        return $action
            ->label('Change Status')
            ->color(Color::Fuchsia)
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->modalDescription('Select a new status for this event.')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(fn($record) => $user->isAdmin() ? EventStatus::allOptions() : EventStatus::editableOptions(EventStatus::tryFrom($record->status)))
                    ->default(fn($record) => $record->status)
                    ->searchable()
                    ->preload()
                    ->validationAttribute('Status')
                    ->validationMessages([
                        'required' => 'Status is required',
                    ])
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                try {
                    $record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Event Status Changed')
                        ->body("Event {$record->name} status has been changed to " . EventStatus::tryFrom($data['status'])->getLabel())
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed to Change Event Status')
                        ->body("Failed to change event {$record->name} status: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            })
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function EditSeatsButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Seating')
            ->icon('heroicon-o-adjustments-horizontal')
            ->color(Color::Indigo)
            ->url(fn($record) => "/seats/edit?event_id={$record->id}");
    }

    public static function ExportOrdersButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Export Orders')
            ->color(Color::Green)
            ->icon('heroicon-o-arrow-down-tray')
            ->url(
                fn($record) =>
                route(
                    'orders.export',
                    ['id' => $record?->id]
                )
            )
        ;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'tickets',
                'tickets.team',
                'tickets.seat',
                'tickets.ticketOrders',
                'tickets.ticketOrders.order',
                'tickets.ticketOrders.order.user',
                'ticketCategories',
                'ticketCategories.eventCategoryTimeboundPrices',
                'ticketCategories.eventCategoryTimeboundPrices.timelineSession',
                'orders',
                'orders.user',
                'orders.team'
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, $record = null, bool $showOrders = true, bool $showTickets = true): Infolists\Infolist
    {
        return $infolist
            ->record($record ?? $infolist->record)
            ->schema(
                [
                    Infolists\Components\Section::make()->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Event ID')
                            ->icon('heroicon-o-identification'),
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status')
                            ->icon(fn($state) => EventStatus::tryFrom($state)->getIcon())
                            ->formatStateUsing(fn($state) => EventStatus::tryFrom($state)->getLabel())
                            ->color(fn($state) => EventStatus::tryFrom($state)->getColor())
                            ->badge(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Name')
                            ->icon('heroicon-m-film'),
                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug (click to open site)')
                            ->action(
                                Infolists\Components\Actions\Action::make('actionSlug')
                                    ->action(function ($record) {
                                        $protocol = config('session.secure') ? 'https://' : 'http://';
                                        $url = $protocol . $record->slug . '.' . config('app.domain');

                                        // redirect new page to url
                                        return redirect()->to($url);
                                    })
                            )
                            ->icon('heroicon-m-magnifying-glass-plus'),
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
                            Infolists\Components\Tabs\Tab::make('Scan Tickets')
                                ->hidden(function () {
                                    $user = session('auth_user');
                                    return !$user->isAllowedInRoles([UserRole::ADMIN, UserRole::EVENT_ORGANIZER]);
                                })
                                ->schema([
                                    Infolists\Components\Livewire::make('event-scan-ticket', ['event' => $infolist->record])
                                ]),
                            Infolists\Components\Tabs\Tab::make('Timeline and Categories')
                                ->schema([
                                    Infolists\Components\Section::make('Timeline')
                                        ->schema([
                                            Infolists\Components\RepeatableEntry::make('timelineSessions')
                                                ->label('')
                                                ->columns(3)
                                                ->grid(2)
                                                ->schema([
                                                    Infolists\Components\TextEntry::make('name')
                                                        ->icon('heroicon-o-tag'),
                                                    Infolists\Components\TextEntry::make('start_date')
                                                        ->icon('heroicon-o-clock')
                                                        ->label('Start Date'),
                                                    Infolists\Components\TextEntry::make('end_date')
                                                        ->icon('heroicon-o-clock')
                                                        ->label('End Date'),
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
                                                        ->icon('heroicon-o-tag')
                                                        ->columnSpan(1),
                                                    Infolists\Components\ColorEntry::make('color')
                                                        ->columnSpan(1),
                                                    Infolists\Components\RepeatableEntry::make('eventCategoryTimeboundPrices')
                                                        ->label('Timeline')
                                                        ->grid(3)
                                                        ->columnSpan(2)
                                                        ->columns(2)
                                                        ->schema([
                                                            Infolists\Components\TextEntry::make('timelineSession.name')
                                                                ->label('Timeline')
                                                                ->icon('heroicon-o-tag')
                                                                ->columnSpan(2),
                                                            Infolists\Components\TextEntry::make('price')
                                                                ->icon('heroicon-o-banknotes')
                                                                ->money('IDR'),
                                                            Infolists\Components\TextEntry::make('is_active')
                                                                ->label('Status')
                                                                ->formatStateUsing(fn($state) => $state ? 'Active' : 'Inactive')
                                                                ->color(fn($state) => $state ? 'success' : 'danger')
                                                                ->icon(fn($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                                                ->badge(),
                                                        ])
                                                ])
                                        ]),
                                ]),
                            Infolists\Components\Tabs\Tab::make('Event Variables')
                                ->columns(4)
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\Section::make('Lock')
                                            ->relationship('eventVariables')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('is_locked')
                                                    ->label('Is Locked')
                                                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                                                Infolists\Components\TextEntry::make('locked_password')
                                                    ->label('Locked Password'),
                                            ]),
                                        Infolists\Components\Section::make('Etc')
                                            ->relationship('eventVariables')
                                            ->schema([
                                                Infolists\Components\TextEntry::make('ticket_limit')
                                                    ->label('Purchase Limit'),
                                            ]),
                                    ])
                                        ->columnSpan(1),
                                    Infolists\Components\Section::make('Maintenance')
                                        ->relationship('eventVariables')
                                        ->columnSpan(1)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('is_maintenance')
                                                ->label('Is Maintenance')
                                                ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                                            Infolists\Components\TextEntry::make('maintenance_title')
                                                ->label('Title')
                                                ->formatStateUsing(fn($state) => $state ?? 'Not Set'),

                                            Infolists\Components\TextEntry::make('maintenance_message')
                                                ->label('Message')
                                                ->formatStateUsing(fn($state) => $state ?? 'Not Set'),

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

                                            Infolists\Components\TextEntry::make('texture')
                                                ->label('Texture'),

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
                                        ]),
                                ]),
                            Infolists\Components\Tabs\Tab::make('Terms and Conditions')
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('terms_and_conditions')
                                            ->label(''),
                                    ])->relationship('eventVariables')
                                ]),

                            Infolists\Components\Tabs\Tab::make('Privacy Policy')
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('privacy_policy')
                                            ->label(''),
                                    ])->relationship('eventVariables')
                                ]),

                            Infolists\Components\Tabs\Tab::make('Midtrans')
                                ->hidden(!session('auth_user')->isAllowedInRoles([UserRole::ADMIN]))
                                ->schema([
                                    Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('midtrans_client_key_sb')
                                            ->label('Client Key SB')
                                            ->formatStateUsing(fn($state) => Crypt::decryptString($state)),

                                        Infolists\Components\TextEntry::make('midtrans_server_key_sb')
                                            ->label('Server Key SB')
                                            ->formatStateUsing(fn($state) => Crypt::decryptString($state)),
                                        Infolists\Components\TextEntry::make('midtrans_client_key')
                                            ->label('Client Key')
                                            ->formatStateUsing(fn($state) => Crypt::decryptString($state)),

                                        Infolists\Components\TextEntry::make('midtrans_server_key')
                                            ->label('Server Key')
                                            ->formatStateUsing(fn($state) => Crypt::decryptString($state)),

                                        Infolists\Components\TextEntry::make('midtrans_is_production')
                                            ->label('Production')
                                            ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),

                                        Infolists\Components\TextEntry::make('midtrans_use_novatix')
                                            ->label('Using NovaTix Midtrans')
                                            ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No'),
                                    ])->relationship('eventVariables')->columns(2)
                                ]),
                            Infolists\Components\Tabs\Tab::make('Orders')
                                ->hidden(!$showOrders)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(OrdersRelationManager::class)
                                ]),
                            Infolists\Components\Tabs\Tab::make('Tickets')
                                ->hidden(!$showTickets)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(TicketsRelationManager::class)
                                ]),
                        ])
                        ->columnSpan('full'),
                ]
            );
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        // get current form values
        $currentModel = $form->model;
        $modelExists = !is_string($currentModel);

        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('General')
                        ->columns(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->placeholder('Event Name')
                                ->required()
                                ->maxLength(255)
                                ->validationAttribute('Name')
                                ->validationMessages([
                                    'required' => 'Name is required',
                                    'max' => 'Name must not exceed 255 characters',
                                ])
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    // reject if name already used
                                    $event_id = $get('event_id');
                                    $foundEvent = Event::where('name', $get('name'))->first();
                                    if ($foundEvent && $foundEvent->id != $event_id) {
                                        $set('name', null);
                                        $set('slug', null);

                                        Notification::make()
                                            ->title('Event Name Rejected')
                                            ->body('Event name already exist')
                                            ->info()
                                            ->send();
                                    }

                                    // incrementing slug if exist same slug
                                    $increment = 0;
                                    $base_slug = Str::slug($get('name'));
                                    $slug = $base_slug;

                                    $foundEvent = Event::where('slug', $slug)->first();

                                    while ($foundEvent && $foundEvent->id != $event_id) {
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
                                ->placeholder('Event Slug')
                                ->maxLength(255)
                                ->validationAttribute('Slug')
                                ->validationMessages([
                                    'required' => 'Slug is required',
                                    'max' => 'Slug must not exceed 255 characters',
                                ])
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    // incrementing slug if exist same slug
                                    $increment = 0;
                                    $base_slug = Str::slug($get('slug'));
                                    $slug = $base_slug;

                                    // current event id
                                    $event_id = $get('event_id');
                                    $foundEvent = Event::where('slug', $slug)->first();

                                    while ($foundEvent && $foundEvent->id != $event_id) {
                                        if ($increment > 0) {
                                            $slug = $base_slug . '-' . $increment;
                                            $foundEvent = Event::where('slug', $slug)->first();
                                        }
                                        $increment++;
                                    }
                                    $set('slug', $slug);
                                })
                                ->reactive(),
                            Forms\Components\DateTimePicker::make('start_date')
                                ->label('Start Date')
                                ->helperText('The date when the event timeline starts. Must be not earlier than the current time.')
                                ->required()
                                ->validationAttribute('Start Date')
                                ->validationMessages([
                                    'required' => 'Start date is required',
                                    'after_or_equal' => 'Start date must be not earlier than current time',
                                ])
                                ->minDate(
                                    fn() => $modelExists
                                        ? min(now(), $currentModel?->start_date ?? now())
                                        : now()
                                )
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    $carbonifiedStart = Carbon::parse($get('start_date'));
                                    $carbonifiedEnd = Carbon::parse($get('event_date'));

                                    if ($get('event_date') && ($carbonifiedStart >= $carbonifiedEnd)) {
                                        $set('event_date', null);

                                        Notification::make()
                                            ->title('Event Date Rejected')
                                            ->body('Event date is before the start date')
                                            ->info()
                                            ->send();
                                    }

                                    $copyTimeline = $get('event_timeline');

                                    $idx = 1;
                                    foreach ($copyTimeline as $key => $timeline) {
                                        $carbonifiedTLStart = Carbon::parse($timeline['start_date']);
                                        $carbonifiedTLEnd = Carbon::parse($timeline['end_date']);

                                        // nullify all the start_date and event_date that is outside the constraints
                                        if ($timeline['start_date'] && ($carbonifiedTLStart < $carbonifiedStart || $carbonifiedTLStart > $carbonifiedEnd)) {
                                            $copyTimeline[$key]['start_date'] = null;

                                            Notification::make()
                                                ->title('Event Date Rejected for timeline: ' . ($timeline['name'] ?? 'Timeline no. ' . $idx))
                                                ->body('Event start date is outside the event date constraints')
                                                ->info()
                                                ->send();
                                        }

                                        if ($timeline['end_date'] && ($carbonifiedTLEnd < $carbonifiedStart || $carbonifiedTLEnd > $carbonifiedEnd)) {
                                            $copyTimeline[$key]['end_date'] = null;

                                            Notification::make()
                                                ->title('Event Date Rejected for timeline: ' . ($timeline['name'] ?? 'Timeline no. ' . $idx))
                                                ->body('Event end date is outside the event date constraints')
                                                ->info()
                                                ->send();
                                        }
                                        $idx++;
                                    }

                                    $set('event_timeline', $copyTimeline);
                                }),
                            Forms\Components\DateTimePicker::make('event_date')
                                ->label('Event Date')
                                ->required()
                                ->validationAttribute('End Date')
                                ->validationMessages([
                                    'required' => 'End date is required',
                                    'after_or_equal' => 'End date must be after or equal to start date',
                                ])
                                ->minDate(
                                    fn(Forms\Get $get) => Carbon::parse($get('start_date'))
                                )
                                ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                ->reactive()
                                ->helperText(fn(Forms\Get $get) => $get('start_date') ? 'Event execution date. Will be the limit for all timeline.' : 'Select start date first')
                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                    $copyTimeline = $get('event_timeline');

                                    $carbonifiedStart = Carbon::parse($get('start_date'));
                                    $carbonifiedEnd = Carbon::parse($get('event_date'));

                                    $idx = 1;
                                    foreach ($copyTimeline as $key => $timeline) {
                                        $carbonifiedTLStart = Carbon::parse($timeline['start_date']);
                                        $carbonifiedTLEnd = Carbon::parse($timeline['end_date']);

                                        // nullify all the start_date and event_date that is outside the constraints
                                        if ($timeline['start_date'] && ($carbonifiedTLStart < $carbonifiedStart || $carbonifiedTLStart > $carbonifiedEnd)) {
                                            $copyTimeline[$key]['start_date'] = null;

                                            Notification::make()
                                                ->title('Event Date Rejected for timeline: ' . ($timeline['name'] ?? 'Timeline no. ' . $idx))
                                                ->body('Timeline start date is outside the event date constraints')
                                                ->info()
                                                ->send();
                                        }
                                        if ($timeline['end_date'] && ($carbonifiedTLEnd < $carbonifiedStart || $carbonifiedTLEnd > $carbonifiedEnd)) {
                                            $copyTimeline[$key]['end_date'] = null;

                                            Notification::make()
                                                ->title('Event Date Rejected for timeline: ' . ($timeline['name'] ?? 'Timeline no. ' . $idx))
                                                ->body('Timeline end date is outside the event date constraints')
                                                ->info()
                                                ->send();
                                        }
                                        $idx++;
                                    }

                                    if ($carbonifiedStart >= $carbonifiedEnd) {
                                        $set('event_date', null);

                                        Notification::make()
                                            ->title('Event Date Rejected')
                                            ->body('Event date is before the start date')
                                            ->info()
                                            ->send();
                                    }

                                    $set('event_timeline', $copyTimeline);
                                }),
                            Forms\Components\Select::make('venue_id')
                                ->required()
                                ->validationAttribute('Venue')
                                ->validationMessages([
                                    'required' => 'Venue is required',
                                ])
                                ->searchable()
                                ->optionsLimit(5)
                                ->options(
                                    function () {
                                        $venues = Venue::where('status', VenueStatus::ACTIVE)->get()->pluck('name', 'id');
                                        return $venues;
                                    }
                                )
                                ->preload()
                                ->disabled(!session('auth_user')->isAllowedInRoles([UserRole::ADMIN]) && $modelExists)
                                ->helperText(!session('auth_user')->isAllowedInRoles([UserRole::ADMIN]) && $modelExists ? 'You can\'t change the selected venue.' : 'Note: You can only set this once!')
                                ->label('Venue')
                                ->placeholder('Select Venue')
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    $venue = Venue::find($get('venue_id'));
                                    if ($venue) {
                                        $set('location', $venue->location);
                                    }
                                })
                                ->reactive(),
                            Forms\Components\TextInput::make('location')
                                ->required()
                                ->placeholder('Event Location')
                                ->maxLength(255)
                                ->validationAttribute('Location')
                                ->validationMessages([
                                    'required' => 'Location is required',
                                    'max' => 'Location must not exceed 255 characters',
                                ]),
                        ]),
                    Forms\Components\Wizard\Step::make('Timeline')
                        ->schema([
                            Forms\Components\Repeater::make('event_timeline')
                                ->label('')
                                ->columns([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 5,
                                ])
                                ->minItems(1)
                                ->validationAttribute('Event Timeline')
                                ->validationMessages([
                                    'min' => 'At least one timeline is required',
                                ])
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
                                                    // dd($timeline);
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
                                        ->placeholder('Timeline Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->validationAttribute('Timeline Name')
                                        ->validationMessages([
                                            'required' => 'Timeline Name is required',
                                            'max' => 'Timeline Name must not exceed 255 characters',
                                        ])
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 1,
                                        ])
                                        ->default(null)
                                        ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                            // calculate how many names that is the same
                                            $array = $get('../');

                                            // in array, find how many with name exactly as state
                                            $count = collect($array)->filter(function ($item) use ($state) {
                                                return $item['name'] == $state;
                                            })->count();

                                            // if only one, then it is already unique, else reject state
                                            if ($count > 1) {
                                                $set('name', null);

                                                Notification::make()
                                                    ->title('Timeline Name Rejected')
                                                    ->body('Timeline name already exist')
                                                    ->info()
                                                    ->send();
                                            }
                                        }),
                                    Forms\Components\DateTimePicker::make('start_date')
                                        ->label('Start Date')
                                        ->required()
                                        ->validationAttribute('Timeline Start Date')
                                        ->validationMessages([
                                            'required' => 'Timeline Start Date is required',
                                            'after_or_equal' => 'Timeline Start Date must be after or equal to the previous end date',
                                            'before_or_equal' => 'Timeline Start Date must be before or equal to the event date',
                                        ])
                                        ->default(null)
                                        ->reactive()
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                        ])
                                        ->minDate(function (Forms\Get $get) use ($modelExists) {
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

                                                    // If model exists, compare with the current start_date
                                                    if ($modelExists) {
                                                        $currentStartDate = Carbon::parse($get('start_date'));

                                                        return $minDate->greaterThan($currentStartDate) ? $minDate : $currentStartDate;
                                                    }

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
                                                // If start date set is greater than event date or earlier than event start date, reset end date
                                                $eventStartDate = Carbon::parse($get('../../start_date'));
                                                $eventDate = Carbon::parse($get('../../event_date'));

                                                // Make sure the selected date is not earlier than the previous date
                                                $array = $get('../');
                                                $current_body = [
                                                    'name' => $get('name'),
                                                    'start_date' => $get('start_date'),
                                                    'end_date' => $get('end_date')
                                                ];

                                                $indexedArray = array_values($array);

                                                // Clean indexedArray objects to only have name, start_date, and end_date
                                                $indexedArray = array_map(function ($item) {
                                                    return [
                                                        'name' => $item['name'],
                                                        'start_date' => $item['start_date'],
                                                        'end_date' => $item['end_date']
                                                    ];
                                                }, $indexedArray);

                                                $index = array_search($current_body, $indexedArray);

                                                // First index now
                                                if ($index > 0) {
                                                    $prev = $indexedArray[$index - 1];

                                                    $carbonifiedPrev = Carbon::parse($prev['end_date']);
                                                    $carbonifiedStart = Carbon::parse($get('start_date'));

                                                    if ($carbonifiedPrev >= $carbonifiedStart) {
                                                        $set('start_date', null);

                                                        Notification::make()
                                                            ->title('Timeline Date Rejected for ' . (!empty($get('name')) ? $get('name') : 'Timeline with unset name'))
                                                            ->body('Timeline start date is before the previous end date')
                                                            ->info()
                                                            ->send();
                                                    }
                                                }

                                                // Handle event date constraints
                                                if ($get('start_date')) {
                                                    if ($startDate->greaterThan($eventDate) || $startDate->lessThan($eventStartDate)) {
                                                        $set('start_date', null);

                                                        Notification::make()
                                                            ->title('Event Date Rejected')
                                                            ->body('Event start date is outside the event date constraints')
                                                            ->info()
                                                            ->send();
                                                    }
                                                }

                                                $endDate = Carbon::parse($get('end_date'));

                                                if ($get('start_date') && $get('end_date') && $startDate->greaterThanOrEqualTo($endDate)) {
                                                    $set('end_date', null); // Reset end_date if it's before start_date

                                                    Notification::make()
                                                        ->title('Event Date Rejected')
                                                        ->body('Event end date is before the start date')
                                                        ->info()
                                                        ->send();
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
                                        ->validationAttribute('Timeline End Date')
                                        ->validationMessages([
                                            'required' => 'Timeline End Date is required',
                                            'after_or_equal' => 'Timeline End Date must be after the start date',
                                            'before_or_equal' => 'Timeline End Date must be before the event date',
                                        ])
                                        ->disabled(fn(Forms\Get $get) => $get('start_date') == null)
                                        ->minDate(
                                            fn(Forms\Get $get) =>
                                            // Carbon::parse($get('start_date'))->addDay()
                                            Carbon::parse($get('start_date'))
                                        )
                                        ->default(null)
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                        ])
                                        ->required()
                                        ->reactive()
                                        ->maxDate(
                                            fn(Forms\Get $get) => $get('../../event_date')
                                                ? Carbon::parse($get('../../event_date'))->max(now())
                                                : now()
                                        )

                                        ->afterStateUpdated(
                                            function (Forms\Get $get, Forms\Set $set) {
                                                // If end date is greater than event date, reset
                                                $event_date = Carbon::parse($get('../../event_date'));
                                                $end_date = Carbon::parse($get('end_date'));
                                                if ($get('end_date') && $end_date->greaterThan($event_date)) {
                                                    $set('end_date', null);

                                                    Notification::make()
                                                        ->title('Event Date Rejected')
                                                        ->body('Event end date is after the event date')
                                                        ->info()
                                                        ->send();
                                                }

                                                // remove next if date overlaps
                                                $array = $get('../');

                                                // if end date overlaps start date, reset
                                                if ($get('start_date') && $get('end_date')) {
                                                    $carbonifiedStart = Carbon::parse($get('start_date'));
                                                    $carbonifiedEnd = Carbon::parse($get('end_date'));

                                                    if ($carbonifiedStart >= $carbonifiedEnd) {
                                                        $set('end_date', null);

                                                        Notification::make()
                                                            ->title('Timeline Date Rejected for ' . (!empty($get('name')) ? $get('name') : 'Timeline with unset name'))
                                                            ->body('Timeline end date is before the start date')
                                                            ->info()
                                                            ->send();
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

                    Forms\Components\Wizard\Step::make('Ticket Prices')
                        ->schema([
                            Forms\Components\Repeater::make('ticket_categories')
                                ->relationship('ticketCategories')
                                ->minItems(1)
                                ->columns([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 9,
                                ])
                                ->validationAttribute('Event Category')
                                ->validationMessages([
                                    'min' => 'At least one category is required',
                                ])
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
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 2,
                                        ])
                                        ->schema([
                                            Forms\Components\Hidden::make('ticket_category_id')
                                                ->default('-'),
                                            Forms\Components\TextInput::make('name')
                                                ->default('')
                                                ->placeholder('Category Name')
                                                ->maxLength(255)
                                                ->required()
                                                ->validationAttribute('Category Name')
                                                ->validationMessages([
                                                    'required' => 'Category Name is required',
                                                    'max' => 'Category Name must not exceed 255 characters',
                                                ])
                                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                                    // calculate how many names that is the same
                                                    $array = $get('../');

                                                    // in array, find how many with name exactly as state
                                                    $count = collect($array)->filter(function ($item) use ($state) {
                                                        return $item['name'] == $state;
                                                    })->count();

                                                    // if only one, then it is already unique, else reject state
                                                    if ($count > 1) {
                                                        $set('name', null);

                                                        Notification::make()
                                                            ->title('Category Name Rejected')
                                                            ->body('Category name already exist')
                                                            ->info()
                                                            ->send();
                                                    }
                                                }),
                                            Forms\Components\ColorPicker::make('color')
                                                ->default('')
                                                ->placeholder('Category Color')
                                                ->validationAttribute('Category Color')
                                                ->validationMessages([
                                                    'required' => 'Category Color is required',
                                                ])
                                                ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                                                    // If the input is not color, reject
                                                    if (!preg_match('/^#[0-9A-F]{3,8}$/i', $state)) {
                                                        $set('color', null);

                                                        Notification::make()
                                                            ->title('Category Color Rejected')
                                                            ->body('Category Color must be a valid hex color')
                                                            ->info()
                                                            ->send();

                                                        return;
                                                    }

                                                    // calculate how many names that is the same
                                                    $array = $get('../');

                                                    // in array, find how many with name exactly as state
                                                    $count = collect($array)->filter(function ($item) use ($state) {
                                                        return $item['color'] == $state;
                                                    })->count();

                                                    // if only one, then it is already unique, else reject state
                                                    if ($count > 1) {
                                                        $set('color', null);

                                                        Notification::make()
                                                            ->title('Category Color Rejected')
                                                            ->body('Category color already exist')
                                                            ->info()
                                                            ->send();
                                                    }
                                                })
                                                ->hex()
                                                ->required(),
                                        ]),
                                    Forms\Components\Section::make('Timeline')
                                        ->columnSpan([
                                            'default' => 1,
                                            'sm' => 1,
                                            'md' => 7,
                                        ])
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
                                                    ])->columns([
                                                        'default' => 1,
                                                        'sm' => 1,
                                                        'md' => 2,
                                                    ]),

                                                    Forms\Components\Hidden::make('name')
                                                        ->default('')
                                                        ->formatStateUsing(function (Forms\Get $get) {
                                                            $id = $get('timeline_id');
                                                            $timeline = $get('../../../../event_timeline');

                                                            // timeline has key => obj where one of th obj is timeline_id to matched with $id, find and acquire
                                                            $found = collect($timeline)->firstWhere('id', $id);

                                                            if ($found) return $found['name'];
                                                            else return '';
                                                        }),

                                                    Forms\Components\TextInput::make('price')
                                                        ->default(1)
                                                        ->placeholder('Timeline Price')
                                                        ->hidden(fn(Forms\Get $get) => !$get('is_active'))
                                                        ->prefix('Rp')
                                                        ->inputMode('decimal')
                                                        ->numeric()
                                                        ->required()
                                                        ->validationAttribute('Timeline Price')
                                                        ->validationMessages([
                                                            'required' => 'Timeline Price is required',
                                                            'numeric' => 'Timeline Price must be a number',
                                                            'min' => 'Timeline Price must be greater than 0',
                                                        ])
                                                        ->minValue(1)
                                                ])
                                        ])
                                ])
                                ->label('')
                        ]),
                    Forms\Components\Wizard\Step::make('Locking')
                        ->hidden(!$modelExists)
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\Section::make('')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->schema([
                                    Forms\Components\Toggle::make('is_locked')
                                        ->reactive()
                                        ->label('Is Locked')
                                ]),
                            Forms\Components\TextInput::make('locked_password')
                                ->placeholder('Password')
                                ->required(fn(Forms\Get $get) => $get('is_locked'))
                                ->maxLength(255)
                                ->validationAttribute('Password')
                                ->validationMessages([
                                    'required' => 'Password is required',
                                    'max' => 'Password must not exceed 255 characters',
                                ])
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->label('Password'),
                        ]),
                    Forms\Components\Wizard\Step::make('Maintenance')
                        ->hidden(!$modelExists)
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\Section::make('')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->schema([
                                    Forms\Components\Toggle::make('is_maintenance')
                                        ->reactive()
                                        ->label('Is Maintenance')
                                ]),
                            Forms\Components\TextInput::make('maintenance_title')
                                ->placeholder('Title')
                                ->maxLength(255)
                                ->validationAttribute('Title')
                                ->validationMessages([
                                    'max' => 'Title must not exceed 255 characters',
                                ])
                                ->disabled(fn(Forms\Get $get) => !$get('is_maintenance'))
                                ->label('Title'),
                            Forms\Components\DateTimePicker::make('maintenance_expected_finish')
                                ->label('Expected Finish')
                                ->validationAttribute('Expected Finish')
                                ->validationMessages([
                                    'after_or_equal' => 'Expected Finish must be after or equal to now',
                                ])
                                ->disabled(fn(Forms\Get $get) => !$get('is_maintenance'))
                                ->minDate(
                                    fn() => $modelExists
                                        ? min(now(), optional($currentModel->eventVariables->maintenance_expected_finish) ? Carbon::parse($currentModel->eventVariables->maintenance_expected_finish) : now())
                                        : now()
                                ),
                            Forms\Components\TextInput::make('maintenance_message')
                                ->placeholder('Message')
                                ->maxLength(255)
                                ->validationAttribute('Message')
                                ->validationMessages([
                                    'max' => 'Message must not exceed 255 characters',
                                ])
                                ->disabled(fn(Forms\Get $get) => !$get('is_maintenance'))
                                ->label('Message'),
                        ]),
                    Forms\Components\Wizard\Step::make('Limits')
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\TextInput::make('ticket_limit')
                                ->placeholder('Ticket Purchase Limit')
                                ->default(1)
                                ->minValue(1)
                                ->maxValue(20)
                                ->required()
                                ->validationAttribute('Ticket Purchase Limit')
                                ->validationMessages([
                                    'required' => 'Ticket Purchase Limit is required',
                                    'numeric' => 'Ticket Purchase Limit must be a number',
                                    'min' => 'Ticket Purchase Limit must be at least 1',
                                    'max' => 'Ticket Purchase Limit must not exceed 20',
                                ])
                                ->label('Ticket Purchase Limit')
                                ->helperText('Maximum number of tickets a customer can purchase at once')
                                ->afterStateUpdated(
                                    function (Forms\Set $set, $state) {
                                        if (!is_numeric($state)) {
                                            $set('ticket_limit', 1);

                                            Notification::make()
                                                ->title('Ticket Purchase Limit Rejected')
                                                ->body('Ticket Purchase Limit must be a number')
                                                ->info()
                                                ->send();
                                        }
                                    }
                                ),
                        ]),
                    Forms\Components\Wizard\Step::make('Colors')
                        ->schema(fn($record) => [
                            Forms\Components\Livewire::make('color-preview', ['record' => $record])
                        ]),
                    Forms\Components\Wizard\Step::make('Identity')
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Forms\Components\FileUpload::make('logo')
                                ->placeholder('Event Logo')
                                ->label('Logo')
                                ->directory('logos')
                                ->maxSize(2048) // Max size in kilobytes (2MB)
                                ->helperText('Click or drag to upload. Max file size is 2MB. PNG, JPEG, JPG, and SVG only.')
                                ->validationAttribute('Logo')
                                ->validationMessages([
                                    'max' => 'The :attribute file size must not exceed 2MB.',
                                    'mimes' => 'Only PNG, JPEG, JPG, and SVG files are allowed.',
                                    'upload' => 'Failed to upload the logo. Please try again.',
                                ])
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml']) // Accept only images
                                ->preserveFilenames(),

                            Forms\Components\FileUpload::make('texture')
                                ->placeholder('Event Texture')
                                ->label('Texture')
                                ->directory('textures')
                                ->maxSize(2048) // Max size in kilobytes (2MB)
                                ->helperText('Click or drag to upload. Max file size is 2MB. PNG, JPEG, JPG, and SVG only.')
                                ->validationAttribute('Texture')
                                ->validationMessages([
                                    'max' => 'The :attribute file size must not exceed 2MB.',
                                    'mimes' => 'Only PNG, JPEG, JPG, and SVG files are allowed.',
                                    'upload' => 'Failed to upload the logo. Please try again.',
                                ])
                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml']) // Accept only images
                                ->preserveFilenames(),

                            Forms\Components\TextInput::make('logo_alt')
                                ->placeholder('Event Logo Alt')
                                ->helperText('Alternative text for the logo if it fails to load')
                                ->maxLength(255)
                                ->validationAttribute('Logo Alt')
                                ->validationMessages([
                                    'max' => 'Logo Alt must not exceed 255 characters',
                                ])
                                ->label('Logo Alt'),

                            Forms\Components\FileUpload::make('favicon')
                                ->placeholder('Event Icon')
                                ->label('Favicon')
                                ->directory('favicons')
                                ->maxSize(2048) // Max size in kilobytes (2MB)
                                ->helperText('Click or drag to upload. Max file size is 2MB. ICO only.')
                                ->validationAttribute('Favicon')
                                ->validationMessages([
                                    'max' => 'The :attribute file size must not exceed 2MB.',
                                    'mimes' => 'Only ICO files are allowed.',
                                    'upload' => 'Failed to upload the logo. Please try again.',
                                ])
                                ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico']) // Allowing all possible ICO mime types
                                ->rule('mimes:ico') // Explicitly enforcing .ico file validation
                                ->preserveFilenames(),
                        ]),
                    Forms\Components\Wizard\Step::make('Terms and Condition')
                        ->hidden(!$modelExists)
                        ->schema([
                            Forms\Components\RichEditor::make('terms_and_conditions')
                                ->label('')
                                ->placeholder('Terms and Condition')
                                ->maxLength(65535)
                                ->validationAttribute('Terms and Condition')
                                ->validationMessages([
                                    'max' => 'Terms and Condition must not exceed 65535 characters',
                                ])
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $state = Purifier::clean($state); // Use HTML purifier if you want to sanitize
                                    $set('terms_and_conditions', $state);
                                }),
                        ]),
                    Forms\Components\Wizard\Step::make('Privacy Policy')
                        ->hidden(!$modelExists)
                        ->schema([
                            Forms\Components\RichEditor::make('privacy_policy')
                                ->label('')
                                ->placeholder('Privacy Policy')
                                ->maxLength(65535)
                                ->validationAttribute('Privacy Policy')
                                ->validationMessages([
                                    'max' => 'Privacy Policy must not exceed 65535 characters',
                                ])
                                ->afterStateUpdated(function (Forms\Set $set, $state) {
                                    $state = Purifier::clean($state); // Use HTML purifier if you want to sanitize
                                    $set('privacy_policy', $state);
                                }),
                        ]),
                    Forms\Components\Wizard\Step::make('Midtrans')
                        ->hidden(!session('auth_user')->isAdmin())
                        ->schema([
                            Forms\Components\Group::make([
                                Forms\Components\TextInput::make('midtrans_client_key_sb')
                                    ->label('Client Key Sandbox')
                                    ->placeholder('Client Key Sandbox')
                                    ->formatStateUsing(fn($state) => $modelExists && $state ? Crypt::decryptString($state) : null)
                                    ->maxLength(65535)
                                    ->validationAttribute('Client Key Sandbox')
                                    ->validationMessages([
                                        'max' => 'Client Key Sandbox must not exceed 65535 characters',
                                    ]),
                                Forms\Components\TextInput::make('midtrans_server_key_sb')
                                    ->label('Server Key Sandbox')
                                    ->placeholder('Server Key Sandbox')
                                    ->formatStateUsing(fn($state) => $modelExists && $state ? Crypt::decryptString($state) : null)
                                    ->maxLength(65535)
                                    ->validationAttribute('Server Key Sandbox')
                                    ->validationMessages([
                                        'max' => 'Server Key Sandbox must not exceed 65535 characters',
                                    ]),
                                Forms\Components\TextInput::make('midtrans_client_key')
                                    ->label('Client Key')
                                    ->placeholder('Client Key')
                                    ->formatStateUsing(fn($state) => $modelExists && $state ? Crypt::decryptString($state) : null)
                                    ->maxLength(65535)
                                    ->validationAttribute('Client Key')
                                    ->validationMessages([
                                        'max' => 'Client Key must not exceed 65535 characters',
                                    ]),
                                Forms\Components\TextInput::make('midtrans_server_key')
                                    ->label('Server Key')
                                    ->placeholder('Server Key')
                                    ->formatStateUsing(fn($state) => $modelExists && $state ? Crypt::decryptString($state) : null)
                                    ->maxLength(65535)
                                    ->validationAttribute('Server Key')
                                    ->validationMessages([
                                        'max' => 'Server Key must not exceed 65535 characters',
                                    ]),
                                Forms\Components\Toggle::make('midtrans_is_production')
                                    ->label('Is Production'),
                                Forms\Components\Toggle::make('midtrans_use_novatix')
                                    ->label('Use NovaTix Central Midtrans')
                            ])
                                ->columns([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                        ])

                ])
                    ->skippable($modelExists)
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table, bool $showTeamName = true, bool $filterStatus = false): Table
    {
        $user = session('auth_user');

        $defaultActions = [
            Tables\Actions\ViewAction::make()
                ->modalHeading('View Event'),
            Tables\Actions\EditAction::make()
                ->color(Color::Orange),
        ];

        if ($user->isAllowedInRoles([UserRole::ADMIN, UserRole::EVENT_ORGANIZER])) {
            $defaultActions[] = self::ChangeStatusButton(Tables\Actions\Action::make('changeStatus'));
            $defaultActions[] = self::EditSeatsButton(Tables\Actions\Action::make('editSeats'));
            $defaultActions[] = self::ExportOrdersButton(Actions\Action::make('export'));
        }

        $defaultActions[] = Tables\Actions\DeleteAction::make()
            ->icon('heroicon-o-trash');

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->limit(20)
                    ->label('Event Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable()
                    ->hidden(!($user->isAdmin()) || !$showTeamName)
                    ->limit(20),
                Tables\Columns\TextColumn::make('slug')
                    ->limit(20)
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
                    ->formatStateUsing(fn($state) => EventStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => EventStatus::tryFrom($state)->getColor())
                    ->icon(fn($state) => EventStatus::tryFrom($state)->getIcon())
                    ->badge()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->options(EventStatus::allOptions())
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->hidden(!$filterStatus),
                    Tables\Filters\SelectFilter::make('team_id')
                        ->label('Filter by Team')
                        ->relationship('team', 'name')
                        ->searchable()
                        ->preload()
                        ->optionsLimit(5)
                        ->multiple()
                        ->hidden(!($user->isAdmin())),
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make($defaultActions),
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

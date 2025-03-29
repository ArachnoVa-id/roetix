<?php

namespace App\Filament\Admin\Resources;

use App\Enums\EventStatus;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Filament\Infolists;
use App\Enums\OrderStatus;
use Filament\Tables\Table;
use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers\TicketsRelationManager;
use App\Models\TicketOrder;
use Filament\Facades\Filament;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $tenantRelationshipName = 'orders';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, [UserRole::ADMIN->value, UserRole::EVENT_ORGANIZER->value]);
    }

    public static function tableQuery(): Builder
    {
        return parent::tableQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        $tenant_id = Filament::getTenant()->team_id;
        // get current form values
        $currentModel = $form->model;
        $modelExists = !is_string($currentModel);

        return $form
            ->schema([
                Forms\Components\Section::make('Buyer')
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        // define the user
                        Forms\Components\Select::make('user_id')
                            ->label('User')
                            ->searchable()
                            ->options(fn() => User::pluck('email', 'id'))
                            ->optionsLimit(5)
                            ->required()
                            ->disabled($modelExists),
                        // define the event
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->searchable()
                            ->reactive()
                            ->optionsLimit(5)
                            ->options(fn() => Event::where('team_id', $tenant_id)->pluck('name', 'event_id'))
                            ->required()
                            ->disabled($modelExists),
                    ]),
                Forms\Components\Section::make('Tickets')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('Please choose an event first')
                            ->hidden(fn(Forms\Get $get) => $get('event_id') != null),
                        // define the repeater of tickets on that event
                        Forms\Components\Repeater::make('tickets')
                            ->hidden(fn(Forms\Get $get) => $get('event_id') == null)
                            ->minItems(1)
                            ->grid(3)
                            ->columns([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 5,
                            ])
                            ->label('')
                            ->deletable(!$modelExists)
                            ->addable(!$modelExists)
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record) {
                                    $return = [];

                                    foreach ($record->tickets as $ticket) {
                                        $uuid = \Illuminate\Support\Str::uuid()->toString();
                                        $ticketOrder = TicketOrder::where('order_id', $record->order_id)
                                            ->where('ticket_id', $ticket->ticket_id)
                                            ->latest()
                                            ->first();

                                        $return[$uuid] = [
                                            'ticket_id' => $ticket->ticket_id,
                                            'status' => $ticketOrder ? $ticketOrder->status : TicketOrderStatus::ENABLED,
                                        ];
                                    }

                                    $set('tickets', $return);
                                }
                            })
                            ->schema([
                                Forms\Components\Select::make('ticket_id')
                                    ->label('Ticket')
                                    ->searchable()
                                    ->optionsLimit(5)
                                    ->disabled($modelExists)
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 1,
                                        'md' => fn() => !$modelExists ? 5 : 2,
                                    ])
                                    ->options(
                                        function (Forms\Get $get) {
                                            // Get all currently selected ticket IDs in the repeater
                                            $selectedTickets = $get('../../tickets')
                                                ? collect($get('../../tickets'))->pluck('ticket_id')->filter()->toArray() // Filter out null values
                                                : [];

                                            return Ticket::where('event_id', $get('../../event_id'))
                                                ->where('status', TicketStatus::AVAILABLE)
                                                ->whereHas('seat') // Ensure the ticket has a related seat
                                                ->with('seat') // Load the seat relationship
                                                ->when(!empty($selectedTickets), function ($query) use ($selectedTickets) {
                                                    return $query->whereNotIn('ticket_id', $selectedTickets); // Use 'id' if it's the primary key
                                                })
                                                ->get()
                                                ->sortBy('seat.seat_number')
                                                ->pluck('seat.seat_number', 'ticket_id') // Pluck seat_number as label, ticket_id as value
                                                ->toArray();
                                        }
                                    )
                                    ->getOptionLabelUsing(fn($value) => Ticket::find($value)?->seat->seat_number ?? '')
                                    ->preload()
                                    ->placeholder('Choose') // This will be shown when there are no options
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options(TicketOrderStatus::editableOptions())
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 1,
                                        'md' => 3,
                                    ])
                                    ->default(TicketOrderStatus::ENABLED)
                                    ->placeholder('Choose')
                                    ->hidden(!$modelExists)
                                    ->required(),
                            ]),
                    ])
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showTickets = true): Infolists\Infolist
    {
        $order = Order::find($infolist->record->order_id);
        $firstEvent = $order->getSingleEvent();
        return $infolist
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 3,
            ])
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('order_id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('order_date')
                            ->label('Date'),
                        Infolists\Components\TextEntry::make('total_price')
                            ->label('Total')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn($state) => OrderStatus::tryFrom($state)->getLabel())
                            ->color(fn($state) => OrderStatus::tryFrom($state)->getColor())
                            ->badge(),
                    ]),
                Infolists\Components\Section::make('Buyer')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 1,
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->relationship('user', 'id')
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('email')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                            ]),
                    ]),
                Infolists\Components\Section::make('Event')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 1,
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->default(fn() => $firstEvent->name),
                        Infolists\Components\TextEntry::make('location')
                            ->default(fn() => $firstEvent->location),
                        Infolists\Components\TextEntry::make('event_date')
                            ->label('D-Day')
                            ->default(fn() => $firstEvent->event_date),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn() => EventStatus::tryFrom($firstEvent->status)->getLabel())
                            ->color(fn() => EventStatus::tryFrom($firstEvent->status)->getColor())
                            ->badge(),
                    ]),
                Infolists\Components\Tabs::make()
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\Tabs\Tab::make('Tickets')
                            ->hidden(!$showTickets)
                            ->schema([
                                \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                    ->manager(TicketsRelationManager::class)
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order_code')
                    ->label('Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user')
                    ->formatStateUsing(function ($state) {
                        return $state->getUserName();
                    })
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => OrderStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => OrderStatus::tryFrom($state)->getColor())
                    ->badge(),
                Tables\Columns\TextColumn::make('events.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $parsed = explode(',', $state);
                        return $parsed[0];
                    }),

            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('status')
                        ->options(OrderStatus::editableOptions())
                        ->multiple()
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->modalHeading('View Order'),
                    Tables\Actions\EditAction::make(),
                ])
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

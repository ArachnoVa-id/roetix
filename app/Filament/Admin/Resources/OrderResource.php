<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Event;
use App\Models\Order;
use Filament\Actions;
use App\Models\Ticket;
use App\Enums\UserRole;
use Filament\Infolists;
use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use Filament\Tables\Table;
use App\Enums\TicketStatus;
use App\Models\TicketOrder;
use Filament\Facades\Filament;
use App\Enums\TicketOrderStatus;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers\TicketsRelationManager;
use App\Filament\Components\CustomPagination;
use App\Models\Team;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class OrderResource extends Resource
{
    protected static ?int $navigationSort = 3;
    protected static ?string $model = Order::class;
    protected static ?string $tenantRelationshipName = 'orders';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN, UserRole::EVENT_ORGANIZER]);
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        $user = session('auth_user');

        return $action
            ->label('Change Status')
            ->color(Color::Fuchsia)
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->modalDescription('Select a new status for this order.')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(fn($record) => $user->isAdmin() ? OrderStatus::allOptions() : OrderStatus::editableOptions(OrderStatus::tryFrom($record->status)))
                    ->default(fn($record) => $record->status)
                    ->preload()
                    ->searchable()
                    ->default(fn($record) => $record->status) // Set the current value as default
                    ->validationAttribute('Status')
                    ->validationMessages([
                        'required' => 'The Status field is required',
                    ])
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                try {
                    $record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Success')
                        ->success()
                        ->body('Status changed successfully.')
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();
                }
            })
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([]);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        $user = session('auth_user');

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
                            ->validationAttribute('User')
                            ->validationMessages([
                                'required' => 'The User field is required',
                            ])
                            ->searchable()
                            ->options(fn() => User::pluck('email', 'id'))
                            ->optionsLimit(5)
                            ->required()
                            ->preload()
                            ->disabled($modelExists),
                        // define the event
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->validationAttribute('Event')
                            ->validationMessages([
                                'required' => 'The Event field is required',
                            ])
                            ->searchable()
                            ->reactive()
                            ->optionsLimit(5)
                            ->options(function () {
                                $tenant_id = Filament::getTenant()?->id;
                                $query = Event::query();
                                if ($tenant_id) {
                                    $query->where('team_id', $tenant_id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->required()
                            ->preload()
                            ->disabled($modelExists),
                        Forms\Components\DateTimePicker::make('expired_at')
                            ->label('Expired At')
                            ->format('Y-m-d H:i:s')
                            ->default(now()->addHours(1))
                            ->disabled($modelExists)
                            ->required()
                            ->validationAttribute('Expired At')
                            ->validationMessages([
                                'required' => 'The Expired At field is required',
                            ]),
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
                            ->validationAttribute('Tickets')
                            ->validationMessages([
                                'min' => 'The Tickets field must have at least one item',
                            ])
                            ->minItems(1)
                            ->grid(3)
                            ->columns([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 5,
                            ])
                            ->label('')
                            ->deletable(fn(Forms\Get $get) => !$modelExists && count($get('tickets')) > 1)
                            ->addable(!$modelExists)
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record) {
                                    $return = [];

                                    foreach ($record->tickets as $ticket) {
                                        $uuid = \Illuminate\Support\Str::uuid()->toString();
                                        $ticketOrder = TicketOrder::where('order_id', $record->id)
                                            ->where('ticket_id', $ticket->id)
                                            ->latest()
                                            ->first();

                                        $return[$uuid] = [
                                            'ticket_id' => $ticket->id,
                                            'status' => $ticketOrder ? $ticketOrder->status : TicketOrderStatus::ENABLED,
                                        ];
                                    }

                                    $set('tickets', $return);
                                }
                            })
                            ->schema([
                                Forms\Components\Select::make('ticket_id')
                                    ->label('Ticket')
                                    ->validationAttribute('Ticket')
                                    ->validationMessages([
                                        'required' => 'The Ticket field is required',
                                    ])
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
                                                ->whereHas('seat')
                                                ->with('seat')
                                                ->when(!empty($selectedTickets), function ($query) use ($selectedTickets) {
                                                    return $query->whereNotIn('id', $selectedTickets);
                                                })
                                                ->get()
                                                ->sortBy('seat.seat_number')
                                                ->pluck('seat.seat_number', 'id')
                                                ->toArray();
                                        }
                                    )
                                    ->getOptionLabelUsing(fn($value) => Ticket::find($value)?->seat->seat_number ?? '')
                                    ->preload()
                                    ->placeholder('Choose') // This will be shown when there are no options
                                    ->required(),

                                Forms\Components\Select::make('status')
                                    ->options(
                                        fn($state) =>
                                        $user->isAdmin() ? TicketOrderStatus::allOptions() : TicketOrderStatus::editableOptions(TicketOrderStatus::tryFrom($state))
                                    )
                                    ->searchable()
                                    ->columnSpan([
                                        'default' => 1,
                                        'sm' => 1,
                                        'md' => 3,
                                    ])
                                    ->default(TicketOrderStatus::ENABLED)
                                    ->placeholder('Choose')
                                    ->preload()
                                    ->hidden(!$modelExists)
                                    ->validationAttribute('Ticket Order Status')
                                    ->validationMessages([
                                        'required' => 'The Ticket Order Status field is required',
                                    ])
                                    ->required(),
                            ]),
                    ])
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showTickets = true): Infolists\Infolist
    {
        $order = $infolist->record;

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
                        Infolists\Components\TextEntry::make('id')
                            ->icon('heroicon-o-shopping-cart')
                            ->label('Order ID'),
                        Infolists\Components\TextEntry::make('order_date')
                            ->icon('heroicon-o-calendar')
                            ->label('Date'),
                        Infolists\Components\TextEntry::make('total_price')
                            ->icon('heroicon-o-banknotes')
                            ->label('Total')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn($state) => OrderStatus::tryFrom($state)->getLabel())
                            ->color(fn($state) => OrderStatus::tryFrom($state)->getColor())
                            ->icon(fn($state) => OrderStatus::tryFrom($state)->getIcon())
                            ->badge(),
                        Infolists\Components\TextEntry::make('expired_at')
                            ->icon('heroicon-o-clock')
                            ->label('Expired At'),
                        Infolists\Components\TextEntry::make('order_code')
                            ->icon('heroicon-o-key')
                            ->label('Order Code'),
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
                            ->icon('heroicon-o-user')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->icon('heroicon-o-user')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-at-symbol')
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
                            ->icon('heroicon-o-ticket')
                            ->default(fn() => $firstEvent->name),
                        Infolists\Components\TextEntry::make('location')
                            ->icon('heroicon-o-map')
                            ->default(fn() => $firstEvent->location),
                        Infolists\Components\TextEntry::make('event_date')
                            ->icon('heroicon-o-calendar')
                            ->label('D-Day')
                            ->default(fn() => $firstEvent->event_date),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn() => EventStatus::tryFrom($firstEvent->status)->getLabel())
                            ->color(fn() => EventStatus::tryFrom($firstEvent->status)->getColor())
                            ->icon(fn() => EventStatus::tryFrom($firstEvent->status)->getIcon())
                            ->badge(),
                    ]),
                Infolists\Components\Tabs::make()
                    ->hidden(!$showTickets)
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\Tabs\Tab::make('Tickets')
                            ->schema([
                                \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                    ->manager(TicketsRelationManager::class)
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table, bool $filterStatus = false, bool $filterEvent = true, bool $filterTeam = true): Table
    {
        $user = session('auth_user');

        return
            CustomPagination::apply($table)
            ->defaultSort('order_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('events.name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state) {
                        $parsed = explode(',', $state);
                        return $parsed[0];
                    }),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable()
                    ->hidden(!($user->isAdmin()) || !$filterTeam)
                    ->limit(50),
                Tables\Columns\TextColumn::make('order_code')
                    ->label('Code')
                    ->copyable()
                    ->copyMessage('Order code copied to clipboard')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user')
                    ->label('User')
                    ->searchable(query: function ($query, $search) {
                        $query->whereHas('user', function ($subQuery) use ($search) {
                            $subQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable()
                    ->formatStateUsing(fn($record) => $record->user?->getFilamentName() ?? 'N/A'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expired At')
                    ->sortable()
                    ->dateTime('Y-m-d H:i:s'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => OrderStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => OrderStatus::tryFrom($state)->getColor())
                    ->icon(fn($state) => OrderStatus::tryFrom($state)->getIcon())
                    ->badge(),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('team_id')
                        ->label('Filter by Team')
                        ->relationship('team', 'name')
                        ->searchable()
                        ->preload()
                        ->optionsLimit(5)
                        ->multiple()
                        ->hidden(!($user->isAdmin())),
                    Tables\Filters\SelectFilter::make('event_id')
                        ->label('Filter by Event')
                        ->relationship('events', 'name')
                        ->searchable()
                        ->preload()
                        ->optionsLimit(5)
                        ->multiple()
                        ->hidden(!$filterEvent),
                    Tables\Filters\SelectFilter::make('status')
                        ->options(OrderStatus::allOptions())
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->hidden(!$filterStatus)
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Order'),
                    Tables\Actions\EditAction::make()
                        ->color(Color::Orange),
                    self::ChangeStatusButton(
                        Tables\Actions\Action::make('changeStatus')
                    ),
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

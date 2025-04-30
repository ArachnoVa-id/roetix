<?php

namespace App\Filament\Admin\Resources;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Filament\Forms;
use Filament\Actions;
use Filament\Tables;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Infolists;
use Filament\Tables\Table;
use App\Enums\TicketStatus;
use App\Models\TicketOrder;
use Filament\Facades\Filament;
use App\Enums\TicketOrderStatus;
use App\Enums\UserRole;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers\TicketOrdersRelationManager;
use App\Filament\Components\CustomPagination;
use App\Models\Order;
use App\Models\Team;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TicketResource extends Resource
{
    protected static ?int $navigationSort = 2;
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN, UserRole::EVENT_ORGANIZER]);
    }

    public static function TraceTicketOrderButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Trace Order')
            ->color('info')
            ->icon('heroicon-o-magnifying-glass')
            ->action(function ($record) {
                // Ensure the record exists
                if (!$record || !$record->id) {
                    Notification::make()
                        ->title('Error')
                        ->error()
                        ->body('Ticket not found')
                        ->send();
                    return;
                }

                // Get the View page URL for the ticket
                $redirectUrl = TicketResource::getUrl('view', ['record' => $record->id]);

                // Perform the redirect
                return redirect($redirectUrl);
            });
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        $user = session('auth_user');

        return $action
            ->label('Change Status')
            ->color(Color::Fuchsia)
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(fn($record) => $user->isAdmin() ? TicketStatus::allOptions() : TicketStatus::editableOptions(TicketStatus::tryFrom($record->status)))
                    ->preload()
                    ->searchable()
                    ->default(fn($record) => $record->status) // Set the current value as default
                    ->validationAttribute('Status')
                    ->validationMessages([
                        'required' => 'Please select a status for the ticket.'
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
            ->requiresConfirmation(fn($record) => $record->status === TicketStatus::IN_TRANSACTION->value)
            ->modalDescription(fn($record) => $record->status === TicketStatus::IN_TRANSACTION->value ? 'Ticket is in an ongoing transaction. Are you sure to change the status?' : 'Select a new status for this ticket.')
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function TransferOwnershipButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Transfer Ownership')
            ->color('warning')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Transfer Ownership')
            ->form([
                Forms\Components\Hidden::make('ticket_id')
                    ->default(fn($record) => $record->id),
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->preload()
                    ->options(function ($record) {
                        // Get the latest ticket order for this ticket
                        $latestTicketOrder = TicketOrder::where('ticket_id', $record->id)
                            ->latest()
                            ->first();

                        // Get the currently selected user's ID
                        $selectedUserId = $latestTicketOrder?->order?->user?->id;

                        // Fetch users excluding the selected one
                        return User::where('id', '!=', $selectedUserId)
                            ->pluck('email', 'id') // ID as value, email as label
                            ->toArray(); // Ensure it's returned as an array
                    })
                    ->default(function ($record) {
                        $latestTicketOrder = TicketOrder::where('ticket_id', $record->id)
                            ->latest()
                            ->first();

                        return $latestTicketOrder?->order?->user?->id ?? null;
                    })
                    ->getOptionLabelUsing(fn($value) => User::find($value)?->email ?? '')
                    ->optionsLimit(5)
                    ->validationAttribute('User')
                    ->validationMessages([
                        'required' => 'Please select a user to transfer the ticket to.'
                    ])
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                DB::beginTransaction();
                try {
                    // Check if ticket_id exists in the data
                    if (empty($data['ticket_id'])) {
                        throw new \Exception('Ticket ID is missing.');
                    }

                    // Correct way to lock a record for update
                    $ticket = Ticket::where('id', $data['ticket_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$ticket) {
                        throw new \Exception('Ticket not found.');
                    }

                    // Check if the latest user is not the same
                    $previousOwner = TicketOrder::where('ticket_id', $ticket->id)->get()->sortBy('created_at')->last()?->order->user;
                    if ($previousOwner) {
                        $user = User::find($data['user_id']);

                        if ($user == $previousOwner)
                            throw new \Exception('Cannot transfer to the same client.');

                        // Deactivate old ticket orders
                        TicketOrder::where('ticket_id', $ticket->id)
                            ->lockForUpdate()
                            ->update(['status' => TicketOrderStatus::DEACTIVATED]);
                    }

                    // Create new order
                    $order_code = Order::keyGen(OrderType::TRANSFER, $ticket->event);
                    $order = Order::create([
                        'order_code'  => $order_code,
                        'user_id'     => $data['user_id'],
                        'event_id'    => $ticket->event_id,
                        'team_id'     => $ticket->team_id,
                        'order_date'  => now(),
                        'total_price' => $ticket->price,
                        'status'      => OrderStatus::COMPLETED,
                        'expired_at'  => now()
                    ]);

                    if (!$order) {
                        throw new \Exception('Failed to create order.');
                    }

                    // Create new ticket order
                    $ticketOrder = TicketOrder::create([
                        'ticket_id' => $ticket->id,
                        'order_id'  => $order->id,
                        'event_id'  => $ticket->event_id,
                        'status'    => TicketOrderStatus::ENABLED,
                    ]);

                    if (!$ticketOrder) {
                        throw new \Exception('Failed to create ticket order.');
                    }

                    // Update ticket status
                    $ticket->update(['status' => TicketStatus::BOOKED]);

                    DB::commit();

                    $user = User::find($data['user_id']);

                    Notification::make()
                        ->title('Success')
                        ->body("Ownership of the ticket: {$ticket->seat->seat_number} has been transferred successfully to user: {$user->email}")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Notification::make()
                        ->title('Error')
                        ->body("Failed to transfer ownership: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation(fn($record) => $record->status === TicketStatus::IN_TRANSACTION->value)
            ->modalDescription(fn($record) => $record->status === TicketStatus::IN_TRANSACTION->value ? 'Ticket is in an ongoing transaction. Are you sure to change the owner?' : 'Select a new user for this ticket.')
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'ticketOrders',
                'ticketOrders.order',
                'ticketOrders.order.user',
                'ticketOrders.event',
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showBuyer = true, bool $showOrders = true): Infolists\Infolist
    {
        $ticket = $infolist->record;

        // get latest buyer
        $ticketOrder = $ticket->ticketOrders
            ->sortByDesc('created_at')
            ->first();

        $buyer = $ticket->latestTicketOrder?->order?->user ?? null;
        return $infolist
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 3,
            ])
            ->schema(
                [
                    Infolists\Components\Section::make('Ticket Information')
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 3,
                        ])
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 6,
                        ])
                        ->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->icon('heroicon-o-ticket')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->label('ID'),
                            Infolists\Components\TextEntry::make('ticket_type')
                                ->icon('heroicon-o-ticket')
                                ->label('Type'),
                            Infolists\Components\TextEntry::make('price')
                                ->icon('heroicon-o-banknotes')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn($state) => TicketStatus::tryFrom($state)->getLabel())
                                ->color(fn($state) => TicketStatus::tryFrom($state)->getColor())
                                ->icon(fn($state) => TicketStatus::tryFrom($state)->getIcon())
                                ->badge(),
                            Infolists\Components\TextEntry::make('ticket_order_status')
                                ->label('Latest Validity')
                                ->default(function ($record) {
                                    $ticket_id = $record->id;
                                    $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
                                        ->latest()
                                        ->first();

                                    if (!$ticketOrder) {
                                        return TicketOrderStatus::ENABLED->value;
                                    }

                                    return $ticketOrder->status;
                                })
                                ->formatStateUsing(fn($state) => TicketOrderStatus::tryFrom($state)->getLabel())
                                ->color(fn($state) => TicketOrderStatus::tryFrom($state)->getColor())
                                ->icon(fn($state) => TicketOrderStatus::tryFrom($state)->getIcon())
                                ->badge(),
                        ]),
                    Infolists\Components\Section::make('Latest Buyer')
                        ->hidden(!$showBuyer)
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
                            Infolists\Components\TextEntry::make('order_code')
                                ->icon('heroicon-o-document-text')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->default(fn() => $ticketOrder?->order?->order_code)
                                ->label('Order Code'),
                            Infolists\Components\TextEntry::make('first_name')
                                ->icon('heroicon-o-user')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->default(fn() => $buyer?->first_name)
                                ->label('First Name'),
                            Infolists\Components\TextEntry::make('last_name')
                                ->icon('heroicon-o-user')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->default(fn() => $buyer?->last_name)
                                ->label('Last Name'),
                            Infolists\Components\TextEntry::make('email')
                                ->icon('heroicon-o-at-symbol')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->label('Email')
                                ->default(fn() => $buyer?->email),
                        ]),
                    Infolists\Components\Section::make('Event')
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 1,
                            'md' => fn() => $showBuyer ? 1 : 2,
                        ])
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->icon('heroicon-o-ticket')
                                ->default(fn($record) => $record->event->name),
                            Infolists\Components\TextEntry::make('location')
                                ->icon('heroicon-o-map')
                                ->default(fn($record) => $record->event->location),
                            Infolists\Components\TextEntry::make('event_date')
                                ->icon('heroicon-o-calendar')
                                ->label('D-Day')
                                ->default(fn($record) => $record->event->event_date),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn($record) => EventStatus::tryFrom($record->event->status)->getLabel())
                                ->color(fn($record) => EventStatus::tryFrom($record->event->status)->getColor())
                                ->icon(fn($record) => EventStatus::tryFrom($record->event->status)->getIcon())
                                ->badge(),
                        ]),
                    Infolists\Components\Section::make("Seat")
                        ->relationship('seat', 'id')
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
                            Infolists\Components\TextEntry::make('seat_number')
                                ->label('Number'),
                            Infolists\Components\TextEntry::make('position'),
                            Infolists\Components\TextEntry::make('row'),
                            Infolists\Components\TextEntry::make('column'),
                        ]),
                    Infolists\Components\Section::make('Orders')
                        ->hidden(!$showOrders)
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 3,
                        ])
                        ->schema([
                            \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                ->manager(TicketOrdersRelationManager::class)
                        ]),
                ]
            );
    }

    public static function table(Table $table, bool $showEvent = true, bool $showTraceButton = false, bool $filterStatus = false, bool $filterEvent = true, bool $filterTeam = true): Table
    {
        $user = session('auth_user');

        $ownership = Tables\Columns\TextColumn::make('ticket_order_status')
            ->label('Latest Validity')
            ->default(function ($record) {
                $ticketOrder = collect($record->ticketOrders)->sortByDesc('created_at')->first();

                if (!$ticketOrder) {
                    return TicketOrderStatus::ENABLED->value;
                }

                return $ticketOrder->status;
            })
            ->formatStateUsing(fn($state) => TicketOrderStatus::tryFrom($state)?->getLabel())
            ->color(fn($state) => TicketOrderStatus::tryFrom($state)?->getColor())
            ->icon(fn($state) => TicketOrderStatus::tryFrom($state)?->getIcon())
            ->badge();

        $latestOwner = Tables\Columns\TextColumn::make('ticket_owner')
            ->label('Latest Owner')
            ->default(function ($record) {
                $ticketOrder = collect($record->ticketOrders)->sortByDesc('created_at')->first();

                if (!$ticketOrder) {
                    return 'N/A';
                }

                return $ticketOrder->order->user->email;
            });

        return
            CustomPagination::apply($table)
            ->columns([
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable()
                    ->hidden(!($user->isAdmin()))
                    ->limit(50),
                Tables\Columns\TextColumn::make('seat.seat_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.name')
                    ->hidden(!$showEvent)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket_type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => TicketStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => TicketStatus::tryFrom($state)->getColor())
                    ->icon(fn($state) => TicketStatus::tryFrom($state)->getIcon())
                    ->badge(),
                $ownership,
                $latestOwner
            ])
            ->defaultSort('seat.seat_number', 'asc')
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

                    SelectFilter::make('event_id')
                        ->label('Filter by Event')
                        ->relationship('event', 'name')
                        ->searchable()
                        ->preload()
                        ->optionsLimit(5)
                        ->multiple()
                        ->hidden(!$filterEvent),

                    SelectFilter::make('status')
                        ->label('Filter by Status')
                        ->options(TicketStatus::allOptions())
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->hidden(!$filterStatus),

                    SelectFilter::make('ticket_order_status')
                        ->label('Filter by Validity')
                        ->options(TicketOrderStatus::allOptions())
                        ->searchable()
                        ->preload()
                        ->multiple()
                        ->query(function ($query, $data) {
                            if (!empty($data['values'])) {
                                $query->whereHas('ticketOrders', function ($query) use ($data) {
                                    $query->joinSub(function ($subquery) {
                                        // Subquery to get the latest ticket order for each ticket_id
                                        $subquery->from('ticket_order')
                                            ->selectRaw('ticket_id, MAX(created_at) as latest_created_at')
                                            ->groupBy('ticket_id');
                                    }, 'latest_ticket_orders', function ($join) {
                                        // Join with the tickets based on ticket_id and the latest created_at
                                        $join->on('ticket_order.ticket_id', '=', 'latest_ticket_orders.ticket_id')
                                            ->on('ticket_order.created_at', '=', 'latest_ticket_orders.latest_created_at');
                                    })
                                        ->whereIn('ticket_order.status', $data['values']); // Filter by ticket order status
                                });
                            }
                        })
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->modalHeading('View Ticket'),
                    self::TraceTicketOrderButton(
                        Tables\Actions\Action::make('TraceTicketOrderButton')
                    )->hidden(!$showTraceButton),
                    self::ChangeStatusButton(
                        Tables\Actions\Action::make('changeStatus')
                    ),
                    self::TransferOwnershipButton(
                        Tables\Actions\Action::make('transferOwnership')
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
            'index' => Pages\ListTickets::route('/'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}

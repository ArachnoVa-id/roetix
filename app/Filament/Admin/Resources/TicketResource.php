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
use App\Models\Order;
use App\Models\Seat;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\DB;

class TicketResource extends Resource
{
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
        $user = Auth::user();

        return $user && in_array($user->role, [UserRole::ADMIN->value, UserRole::EVENT_ORGANIZER->value]);
    }

    public static function TraceTicketOrderButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Trace Order')
            ->color('info')
            ->icon('heroicon-o-magnifying-glass')
            ->action(function ($record) {
                // Ensure the record exists
                if (!$record || !$record->ticket_id) {
                    Notification::make()
                        ->title('Error')
                        ->error()
                        ->body('Ticket not found')
                        ->send();
                    return;
                }

                // Get the View page URL for the ticket
                $redirectUrl = TicketResource::getUrl('view', ['record' => $record->ticket_id]);

                // Perform the redirect
                return redirect($redirectUrl);
            });
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Change Status')
            ->color('success')
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->modalDescription('Select a new status for this ticket.')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(TicketStatus::editableOptions())
                    ->default(fn($record) => $record->status) // Set the current value as default
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                $record->update(['status' => $data['status']]);
            })
            ->modal(true);
    }

    public static function TransferOwnershipButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {

        return $action
            ->label('Transfer Ownership')
            ->color('warning')
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Transfer Ownership')
            ->modalDescription('Select a new user for this ticket.')
            ->form([
                Forms\Components\Hidden::make('ticket_id')
                    ->default(fn($record) => $record->ticket_id),
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->options(function ($record) {
                        // Get the latest ticket order for this ticket
                        $latestTicketOrder = TicketOrder::where('ticket_id', $record->ticket_id)
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
                        $latestTicketOrder = TicketOrder::where('ticket_id', $record->ticket_id)
                            ->latest()
                            ->first();

                        return $latestTicketOrder?->order?->user?->id ?? null;
                    })
                    ->getOptionLabelUsing(fn($value) => User::find($value)?->email ?? '')
                    ->optionsLimit(5)
                    ->required(),
            ])
            ->action(function (array $data) {
                DB::beginTransaction();
                try {
                    // Check if ticket_id exists in the data
                    if (empty($data['ticket_id'])) {
                        throw new \Exception('Ticket ID is missing.');
                    }

                    // Correct way to lock a record for update
                    $ticket = Ticket::where('ticket_id', $data['ticket_id'])
                        ->lockForUpdate()
                        ->first();

                    if (!$ticket) {
                        throw new \Exception('Ticket not found.');
                    }

                    // Deactivate old ticket orders
                    TicketOrder::where('ticket_id', $ticket->ticket_id)
                        ->update(['status' => TicketOrderStatus::DEACTIVATED]);

                    // Create new order
                    $order_code = Order::keyGen(OrderType::TRANSFER);
                    $order = Order::create([
                        'order_code'  => $order_code,
                        'user_id'     => $data['user_id'],
                        'event_id'    => $ticket->event_id,
                        'team_id'     => $ticket->team_id,
                        'order_date'  => now(),
                        'total_price' => $ticket->price,
                        'status'      => OrderStatus::COMPLETED,
                    ]);

                    if (!$order) {
                        throw new \Exception('Failed to create order.');
                    }

                    // Create new ticket order
                    $ticketOrder = TicketOrder::create([
                        'ticket_id' => $ticket->ticket_id,
                        'order_id'  => $order->order_id,
                        'event_id'  => $ticket->event_id,
                        'status'    => TicketOrderStatus::ENABLED,
                    ]);

                    if (!$ticketOrder) {
                        throw new \Exception('Failed to create ticket order.');
                    }

                    // Update ticket status
                    $ticket->update(['status' => TicketStatus::BOOKED]);

                    DB::commit();

                    Notification::make()
                        ->title('Success')
                        ->success()
                        ->body('Ownership transferred successfully.')
                        ->send();
                } catch (\Exception $e) {
                    DB::rollBack();
                    Notification::make()
                        ->title('Error')
                        ->danger()
                        ->body($e->getMessage())
                        ->send();
                }
            })
            ->modal(true);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showBuyer = true, bool $showOrders = true): Infolists\Infolist
    {
        $ticket_id = $infolist->record->ticket_id;
        $ticket = Ticket::find($ticket_id);
        $event = $ticket->event;

        // get latest buyer
        $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
            ->latest()
            ->first();

        $buyer = $ticketOrder?->order?->user;
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
                            Infolists\Components\TextEntry::make('ticket_id')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->label('ID'),
                            Infolists\Components\TextEntry::make('ticket_type')
                                ->label('Type'),
                            Infolists\Components\TextEntry::make('price')
                                ->money('IDR'),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn($state) => TicketStatus::tryFrom($state)->getLabel())
                                ->color(fn($state) => TicketStatus::tryFrom($state)->getColor())
                                ->badge(),
                            Infolists\Components\TextEntry::make('ticket_order_status')
                                ->label('Ownership')
                                ->default(function ($record) {
                                    $ticket_id = $record->ticket_id;
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
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->default(fn() => $ticketOrder?->order?->order_code)
                                ->label('Order Code'),
                            Infolists\Components\TextEntry::make('first_name')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->default(fn() => $buyer?->first_name)
                                ->label('First Name'),
                            Infolists\Components\TextEntry::make('last_name')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                ])
                                ->default(fn() => $buyer?->last_name)
                                ->label('Last Name'),
                            Infolists\Components\TextEntry::make('email')
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
                                ->default(fn() => $event->name),
                            Infolists\Components\TextEntry::make('location')
                                ->default(fn() => $event->location),
                            Infolists\Components\TextEntry::make('event_date')
                                ->label('D-Day')
                                ->default(fn() => $event->event_date),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn() => EventStatus::tryFrom($event->status)->getLabel())
                                ->color(fn() => EventStatus::tryFrom($event->status)->getColor())
                                ->badge(),
                        ]),
                    Infolists\Components\Section::make("Seat")
                        ->relationship('seat', 'seat_id')
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

    public static function table(Table $table, array $dataSource = [], bool $showEvent = true, bool $showTraceButton = false): Table
    {
        $tenant_id = Filament::getTenant()->team_id;

        $ownership = Tables\Columns\TextColumn::make('ticket_order_status')
            ->label('Ownership')
            ->default(function ($record) use ($dataSource) {
                $ticketOrder = null;
                $ticket_id = $record->ticket_id;
                if (empty($dataSource)) {
                    $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
                        ->latest()
                        ->first();
                } else {
                    $order_id = $dataSource['order_id'];
                    $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
                        ->where('order_id', $order_id)
                        ->first();
                }
                if (!$ticketOrder) {
                    return TicketOrderStatus::ENABLED->value;
                }

                return $ticketOrder->status;
            })
            ->formatStateUsing(fn($state) => TicketOrderStatus::tryFrom($state)->getLabel())
            ->color(fn($state) => TicketOrderStatus::tryFrom($state)->getColor())
            ->badge();

        $latestOwner = Tables\Columns\TextColumn::make('ticket_owner')
            ->label('Latest Owner')
            ->default(function ($record) use ($dataSource) {
                $ticketOrder = null;
                $ticket_id = $record->ticket_id;
                if (empty($dataSource)) {
                    $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
                        ->latest()
                        ->first();
                } else {
                    $order_id = $dataSource['order_id'];
                    $ticketOrder = TicketOrder::where('ticket_id', $ticket_id)
                        ->where('order_id', $order_id)
                        ->first();
                }
                if (!$ticketOrder) {
                    return 'N/A';
                }

                return $ticketOrder->order->user->email;
            });

        return $table
            ->columns([
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
                    ->badge(),
                $ownership,
                $latestOwner,
            ])
            ->defaultSort('seat.seat_number', 'asc')
            ->filters(
                [
                    SelectFilter::make('event_id')
                        ->label('Filter by Event')
                        ->options(fn() => Event::where('team_id', $tenant_id)->pluck('name', 'event_id'))
                        ->searchable()
                        ->multiple()
                        ->default(request()->query('tableFilters')['event_id']['value'] ?? null),

                    SelectFilter::make('status')
                        ->label('Filter by Status')
                        ->options(TicketStatus::editableOptions())
                        ->multiple()
                        ->default(request()->query('tableFilters')['status']['value'] ?? null),

                    SelectFilter::make('ticket_order_status')
                        ->label('Filter by Ownership')
                        ->options(TicketOrderStatus::editableOptions())
                        ->multiple()
                        ->query(function ($query, array $data) {
                            if (!empty($data['values'])) {
                                $query->whereHas('ticketOrders', function ($subQuery) use ($data) {
                                    $subQuery->whereIn('status', $data['values'])
                                        ->orderByDesc('created_at'); // Ensure the latest status is considered
                                });
                            }
                        })
                        ->default(request()->query('tableFilters')['ticket_order_status']['value'] ?? null),

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

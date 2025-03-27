<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Event;
use App\Models\Ticket;
use Filament\Infolists;
use Filament\Tables\Table;
use App\Enums\TicketStatus;
use App\Models\TicketOrder;
use Filament\Facades\Filament;
use App\Enums\TicketOrderStatus;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Actions;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Admin\Resources\TicketResource\Pages;

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

        return $user && in_array($user->role, ['admin', 'event-organizer']);
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

    public static function infolist(Infolists\Infolist $infolist, bool $showBuyer = true): Infolists\Infolist
    {
        return $infolist
            ->columns(3)
            ->schema(
                [
                    Infolists\Components\Section::make('Ticket Information')
                        ->columnSpan(3)
                        ->columns(6)
                        ->schema([
                            Infolists\Components\TextEntry::make('ticket_id')
                                ->columnSpan(2)
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
                    Infolists\Components\Section::make('Buyer')
                        ->hidden(!$showBuyer)
                        ->relationship('ticketOrders', 'ticket_id')
                        ->columnSpan(1)
                        ->columns(1)
                        ->schema([
                            Infolists\Components\TextEntry::make('order_id')
                                ->label('Order ID'),
                            Infolists\Components\TextEntry::make('first_name')
                                ->label('First Name'),
                            Infolists\Components\TextEntry::make('last_name')
                                ->label('Last Name'),
                            Infolists\Components\TextEntry::make('email'),
                        ]),
                    Infolists\Components\Section::make("Event")
                        ->relationship('event', 'event_id')
                        ->columnSpan(fn() => $showBuyer ? 1 : 2)
                        ->columns(1)
                        ->schema([
                            Infolists\Components\TextEntry::make('name'),
                            Infolists\Components\TextEntry::make('location')->columnSpan(2),
                        ]),
                    Infolists\Components\Section::make("Seat")
                        ->relationship('seat', 'seat_id')
                        ->columnSpan(1)
                        ->columns(2)
                        ->schema([
                            Infolists\Components\TextEntry::make('seat_number')
                                ->label('Number'),
                            Infolists\Components\TextEntry::make('position'),
                            Infolists\Components\TextEntry::make('row'),
                            Infolists\Components\TextEntry::make('column'),
                        ]),
                ]
            );
    }

    public static function table(Table $table): Table
    {
        $tenant_id = Filament::getTenant()->team_id;

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seat.seat_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event.name')
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
                Tables\Columns\TextColumn::make('ticket_order_status')
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
                    Tables\Actions\ViewAction::make(),
                    self::ChangeStatusButton(
                        Tables\Actions\Action::make('changeStatus')
                    )
                ])
            ])
            ->bulkActions([]);
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

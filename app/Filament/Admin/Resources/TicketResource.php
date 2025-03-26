<?php

namespace App\Filament\Admin\Resources;

use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    public static function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist->schema(
            [
                Infolists\Components\Section::make('Ticket Information')
                    ->columns(5)
                    ->schema([
                        Infolists\Components\TextEntry::make('ticket_id')
                            ->columnSpan(2)
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('ticket_type')
                            ->label('Type'),
                        Infolists\Components\TextEntry::make('price'),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn($state) => TicketStatus::tryFrom($state)->getLabel())
                            ->color(fn($state) => TicketStatus::tryFrom($state)->getColor())
                            ->badge(),
                    ]),
                Infolists\Components\Section::make('Buyer')
                    ->relationship('ticketOrders', 'ticket_id')
                    ->columns(4)
                    ->schema([
                        Infolists\Components\TextEntry::make('order_id')
                            ->columnSpan(1)
                            ->label('Order ID'),
                        Infolists\Components\Group::make([
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('first_name')
                                    ->label('First Name'),
                                Infolists\Components\TextEntry::make('last_name')
                                    ->label('Last Name'),
                                Infolists\Components\TextEntry::make('email'),
                            ])
                                ->columns(3)
                                ->relationship('user', 'user_id')
                        ])
                            ->columnSpan(3)
                            ->relationship('order', 'order_id'),
                    ]),
                Infolists\Components\Section::make("Event")
                    ->relationship('event', 'event_id')
                    ->columns(3)
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('location')->columnSpan(2),
                    ]),
                Infolists\Components\Section::make("Seat")
                    ->relationship('seat', 'seat_id')
                    ->columns(4)
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ticket_type')
                    ->label('Type'),
                Tables\Columns\TextColumn::make('price'),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => TicketStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => TicketStatus::tryFrom($state)->getColor())
                    ->badge(),
            ])
            ->filters(
                [
                    // SelectFilter::make('event_id')
                    //     ->label('Filter by Event')
                    //     ->relationship('event', 'name')
                    //     ->searchable()
                    //     ->multiple()
                    //     ->default(request()->query('tableFilters')['event_id']['value'] ?? null),
                    SelectFilter::make('status')
                        ->label('Filter by Status')
                        ->options(TicketStatus::editableOptions())
                        ->multiple()
                        ->default(request()->query('tableFilters')['status']['value'] ?? null),
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ViewAction::make(),
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

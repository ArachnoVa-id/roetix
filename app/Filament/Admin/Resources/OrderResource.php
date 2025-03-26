<?php

namespace App\Filament\Admin\Resources;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers;
use App\Filament\Admin\Resources\OrderResource\RelationManagers\TicketsRelationManager;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Infolists;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin', 'event-organizer']);
    }

    public static function tableQuery(): Builder
    {
        return parent::tableQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showTickets = true): Infolists\Infolist
    {
        return $infolist
            ->columns(2)
            ->schema([
                Infolists\Components\Section::make('Order Information')
                    ->columnSpan(2)
                    ->columns(2)
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
                    ->columnSpan(1)
                    ->relationship('user', 'id')
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name')
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('email'),
                    ]),
                Infolists\Components\Section::make('Event')
                    ->columnSpan(1)
                    ->relationship('events', 'event_id')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')
                            ->formatStateUsing(function ($state) {
                                $parsed = explode(',', $state);
                                return $parsed[0];
                            }),
                        Infolists\Components\TextEntry::make('location')
                            ->formatStateUsing(function ($state) {
                                $parsed = explode(',', $state);
                                return $parsed[0];
                            }),
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
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order')
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
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}

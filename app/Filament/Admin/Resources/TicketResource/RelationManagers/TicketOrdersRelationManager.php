<?php

namespace App\Filament\Admin\Resources\TicketResource\RelationManagers;

use App\Enums\TicketOrderStatus;
use App\Filament\Admin\Resources\OrderResource;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketOrders';

    public function getTableQuery(): Builder
    {
        return $this->ownerRecord
            ->ticketOrders()
            ->getQuery();
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_code')
                    ->label('Order Code'),
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event'),
                Tables\Columns\TextColumn::make('order.user')
                    ->label('Buyer')
                    ->formatStateUsing(fn($state) => $state->getFilamentName()),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => TicketOrderStatus::tryFrom($state)?->getLabel() ?? 'Unknown')
                    ->color(fn($state) => TicketOrderStatus::tryFrom($state)?->getColor())
                    ->icon(fn($state) => TicketOrderStatus::tryFrom($state)?->getIcon())
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->action(function ($record) {
                        if (!$record || !$record->ticket_id) {
                            Notification::make()
                                ->title('Error')
                                ->error()
                                ->body('Ticket not found')
                                ->send();
                            return;
                        }

                        $redirectUrl = OrderResource::getUrl('view', ['record' => $record->order_id]);
                        return redirect($redirectUrl);
                    })
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('');
    }
}

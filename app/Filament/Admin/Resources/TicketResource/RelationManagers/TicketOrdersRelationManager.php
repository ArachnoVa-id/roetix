<?php

namespace App\Filament\Admin\Resources\TicketResource\RelationManagers;

use App\Enums\TicketOrderStatus;
use App\Filament\Admin\Resources\OrderResource;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TicketOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketOrders';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_code')
                    ->label('Order Code'),
                Tables\Columns\TextColumn::make('event.name')
                    ->label('Event'),
                Tables\Columns\TextColumn::make('order.user')
                    ->formatStateUsing(fn($state) => $state->getFullNameAttribute())
                    ->label('Buyer'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn($state) => TicketOrderStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => TicketOrderStatus::tryFrom($state)->getColor())
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
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
                        $redirectUrl = OrderResource::getUrl('view', ['record' => $record->order_id]);

                        // Perform the redirect
                        return redirect($redirectUrl);
                    })
            ])
            ->defaultSort('created_at', 'desc')
            ->heading('');
    }
}

<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\OrderResource;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketOrder;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function beforeCreate()
    {
        DB::beginTransaction();
        try {
            $data = $this->data;
            $tenant_id = Filament::getTenant()->team_id;
            $event_id = $data['event_id'];
            $user_id = $data['user_id'];

            // Lock all tickets
            foreach ($data['tickets'] as $ticket) {
                $ticket_id = $ticket['name']; // Yes this makes no sense, but I'm tired refactoring this
                $ticketModel = Ticket::where('event_id', $event_id)->where('ticket_id', $ticket_id)
                    ->where('status', TicketStatus::AVAILABLE)
                    ->lockForUpdate()
                    ->first();
                if (!$ticketModel) {
                    throw new \Exception('Ticket not found or already booked');
                }
            }

            // Create Order
            $orderCode = 'M-ORDER-' . time() . '-' . rand(1000, 9999);
            $order = Order::create([
                'order_code' => $orderCode,
                'user_id' => $user_id,
                'event_id' => $event_id,
                'team_id' => $tenant_id,
                'order_date' => now(),
                'total_price' => 0,
                'status' => OrderStatus::COMPLETED,
            ]);

            // Calculate tickets selected
            $tickets = $data['tickets'] ?? [];
            $totalPrice = 0;

            foreach ($tickets as $ticket) {
                $ticket_id = $ticket['name']; // Yes this makes no sense, but I'm tired refactoring this
                $ticketModel = Ticket::find($ticket_id);
                $totalPrice += $ticketModel->price;

                // Create Ticket Order
                $ticketOrder = TicketOrder::create([
                    'ticket_id' => $ticketModel->ticket_id,
                    'order_id' => $order->order_id,
                    'event_id' => $event_id,
                    'status' => TicketOrderStatus::ENABLED,
                ]);

                // If success, update ticket status
                $ticketOrder->ticket->update([
                    'status' => TicketStatus::BOOKED,
                ]);
            }

            // Update total price
            $order->update([
                'total_price' => $totalPrice,
            ]);

            Notification::make('saved')
                ->success()
                ->title('Saved')
                ->send();

            DB::commit();
        } catch (\Exception $e) {
            Notification::make('error')
                ->title('Failed to save')
                ->body($e->getMessage())
                ->danger()
                ->send();

            DB::rollBack();
        }
        $this->halt();
    }
}

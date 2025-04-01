<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Enums\TicketOrderStatus;
use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Components\BackButtonAction;
use App\Models\Ticket;
use App\Models\TicketOrder;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            OrderResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
        ];
    }

    protected function beforeSave()
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        $order_id = $this->data['order_id'];
        $event_id = $this->data['event_id'];
        $tickets = $this->data['tickets'];

        DB::beginTransaction();
        try {
            // Lock all tickets
            foreach ($tickets as $ticket) {
                $ticketModel = Ticket::where('event_id', $event_id)->where('ticket_id', $ticket['ticket_id'])
                    ->lockForUpdate()
                    ->first();
                if (!$ticketModel) {
                    throw new \Exception('Ticket not found or already booked');
                }
            }

            // Loop for each ticket and then update their status
            foreach ($tickets as $ticket) {
                $ticket_id = $ticket['ticket_id'];

                $ticketModel = Ticket::find($ticket_id);
                if (!$ticketModel) {
                    throw new \Exception('Ticket not found');
                }

                $ticketOrderModel = TicketOrder::where('order_id', $order_id)
                    ->where('ticket_id', $ticket_id)
                    ->first();

                if (!$ticketOrderModel) {
                    throw new \Exception('Ticket order not found');
                }

                $status = $ticket['status'];

                TicketOrder::where('order_id', $order_id)
                    ->where('ticket_id', $ticket_id)
                    ->update(['status' => $status]);

                // Update the ticket status
                if ($status === TicketOrderStatus::DEACTIVATED->value) {
                    Ticket::where('ticket_id', $ticket_id)
                        ->update([
                            'status' => TicketStatus::RESERVED,
                        ]);
                } else if ($status === TicketOrderStatus::ENABLED->value) {
                    // check if the ticket is already booked
                    if ($ticketModel->status === TicketStatus::BOOKED->value) {
                        throw new \Exception('Ticket is already booked');
                    }

                    Ticket::where('ticket_id', $ticket_id)
                        ->update([
                            'status' => TicketStatus::BOOKED,
                        ]);
                }
            }

            Notification::make()
                ->success()
                ->title('Saved')
                ->send();

            DB::commit();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to Save')
                ->body($e->getMessage())
                ->danger()
                ->send();

            DB::rollBack();
        }

        // Get the redirect URL (like getRedirectUrl)
        $redirectUrl = $this->getResource()::getUrl('edit', ['record' => $order_id]);

        // Determine whether to use navigate (SPA mode)
        $navigate = FilamentView::hasSpaMode() && Filament::isAppUrl($redirectUrl);

        // Perform the redirect
        $this->redirect($redirectUrl, navigate: $navigate);

        $this->halt();
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Update Order')
            ->icon('heroicon-o-folder');
    }
}

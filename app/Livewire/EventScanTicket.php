<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\Ticket;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\TicketOrder;
use App\Enums\TicketOrderStatus;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;

use Filament\Tables\Concerns\InteractsWithTable;

class EventScanTicket extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public Event $event;
    public $ticket_code = '';

    public function mount($event)
    {
        $this->event = $event;
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('ticket_code')
                ->label('Ticket Code')
                ->placeholder('Manually enter ticket code')
                ->suffixAction(
                    Action::make('submit')
                        ->icon('heroicon-m-paper-airplane')
                        ->action(fn() => $this->submit())
                        ->color('primary')
                ),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::query()->where('event_id', $this->event->event_id))
            ->columns([
                TextColumn::make('ticket_code')->label('Ticket Code')->searchable(),
                TextColumn::make('ticket_order_status')
                    ->label('Status')
                    ->default(function ($record) {
                        $ticketOrder = collect($record->ticketOrders)->sortByDesc('created_at')->first();
                        return $ticketOrder ? TicketOrderStatus::from($ticketOrder->status)->getLabel() : TicketOrderStatus::ENABLED->getLabel();
                    })
                    ->color(function ($record) {
                        $ticketOrder = collect($record->ticketOrders)->sortByDesc('created_at')->first();
                        return $ticketOrder ? TicketOrderStatus::from($ticketOrder->status)->getColor() : TicketOrderStatus::ENABLED->getColor();
                    })
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')->label('Created At')->dateTime()->sortable()->searchable(),
            ]);
    }

    public function submit()
    {
        $this->validate([
            'ticket_code' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $ticket = Ticket::where('ticket_code', $this->ticket_code)
                ->where('event_id', $this->event->event_id)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                throw new \Exception('Tiket tidak ditemukan atau tidak valid.');
            }

            $ticketOrder = TicketOrder::where('ticket_id', $ticket->ticket_id)->first();

            if (!$ticketOrder) {
                throw new \Exception('Order tiket tidak ditemukan.');
            }

            $ticketOrder->status = TicketOrderStatus::SCANNED;
            $ticketOrder->save();

            DB::commit();

            // Notifikasi sukses
            Notification::make()
                ->title('Ticket Berhasil discan')
                ->success()
                ->body("Tiket dengan kode {$this->ticket_code} berhasil discan.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
            return;
        }


        $this->ticket_code = ''; // Reset input field
    }

    public function render()
    {
        return view('livewire.event-scan-ticket');
    }
}

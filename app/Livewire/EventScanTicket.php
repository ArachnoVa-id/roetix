<?php

namespace App\Livewire;

use Livewire\Component;

use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class EventScanTicket extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public Event $event;
    public $ticket_code = '';

    public function mount($eventId)
    {
        // dd($eventId);
        // $this->event = Event::first();
        $this->event = Event::findOrFail($eventId);
        // dd($this->event->event_id);
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
                        ->action(fn () => $this->submit())
                        ->color('primary')
                ),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::query()->where('event_id', $this->event->event_id))
            ->columns([
                TextColumn::make('ticket_id')->label('Ticket Code'),
                TextColumn::make('status')->label('Status'),
                TextColumn::make('created_at')->label('Created At')->dateTime()->sortable(),
            ]);
    }

    public function submit()
    {
        $this->validate([
            'ticket_code' => 'required',
        ]);

        $ticket = Ticket::where('ticket_id', $this->ticket_code)
            ->where('event_id', $this->event->event_id)
            ->first();

        if ($ticket) {
            Notification::make()
                ->title('Ticket Valid')
                ->success()
                ->body('The ticket has been successfully scanned.')
                ->send();
        } else {
            Notification::make()
                ->title('Invalid Ticket')
                ->danger()
                ->body('This ticket code is not valid.')
                ->send();
        }

        $this->ticket_code = ''; // Reset input field
    }

    public function render()
    {
        return view('livewire.event-scan-ticket');
    }
}

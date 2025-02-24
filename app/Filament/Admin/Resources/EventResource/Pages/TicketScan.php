<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use Filament\Resources\Pages\Page;
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

class TicketScan extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static string $resource = EventResource::class;
    protected static string $view = 'filament.admin.resources.event-resource.pages.ticket-scan';

    protected static ?string $navigationIcon = 'heroicon-o-qrcode';
    protected static ?string $slug = 'ticket-scan';
    protected static ?string $navigationLabel = 'Scan Ticket';

    public Event $event;
    public $ticket_code;

    public function mount($record): void
    {
        $this->event = Event::findOrFail($record);
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('ticket_code')
                ->label('Ticket Code')
                ->placeholder('Mannually enter ticket code')
                ->suffixAction(
                    Action::make('submit')
                        ->icon('heroicon-m-paper-airplane')
                        ->action(fn () => $this->submit())
                        ->color('primary')
                ),
        ])
        ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Ticket::query()->where('event_id', $this->event->event_id))
            ->columns([
                TextColumn::make('ticket_id')->label('ID')->sortable(),
                TextColumn::make('ticket_code')->label('Ticket Code')->searchable(),
                TextColumn::make('status')->label('Status')->sortable(),
                TextColumn::make('created_at')->label('Created At')->dateTime(),
            ]);
    }

    // public function table(Table $table): Table
    // {
    //     return $table
    //         ->query(Order::query()->where('event_id', $this->event->event_id))
    //         ->columns([
    //             TextColumn::make('ticket_id')->label('ID')->sortable(),
    //             TextColumn::make('ticket_code')->label('Ticket Code')->searchable(),
    //             TextColumn::make('status')->label('Status')->sortable(),
    //             TextColumn::make('created_at')->label('Created At')->dateTime(),
    //         ]);
    // }

    public function submit()
    {
        $data = $this->form->getState();
        // dummy aja dulu ini
        if ($data['ticket_code'] === '12345') {
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

        $this->form->fill(['ticket_code' => '']);
    }
}

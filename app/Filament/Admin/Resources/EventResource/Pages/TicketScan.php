<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\TicketResource;
use App\Filament\Admin\Resources\EventResource;

use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use App\Models\Ticket;
use App\Models\Event;

class TicketScan extends Page
{
    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.admin.resources.event-resource.pages.ticket-scan';

    protected static ?string $navigationIcon = 'heroicon-o-qrcode';
    protected static ?string $navigationLabel = 'Scan Ticket';
    protected static ?string $slug = 'ticket-scan';

    public Event $event;

    public function mount($record): void
    {
        // dd($record);
        $this->event = Event::findOrFail($record);
    }
}

<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Filament\Admin\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(fn() => TicketResource::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            TicketResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            )
        ];
    }
}

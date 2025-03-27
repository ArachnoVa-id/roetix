<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Filament\Admin\Resources\TicketResource;
use Filament\Actions;
use Filament\Tables;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Back')
                ->url(
                    fn() => request()->headers->get('referer') !== url()->current()
                        ? url()->previous()
                        : $this->getResource()::getUrl()
                )
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
            TicketResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            TicketResource::TransferOwnershipButton(
                Actions\Action::make('transferOwnership')
            )
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Filament\Admin\Resources\TicketResource;
use App\Filament\Components\BackButtonAction;
use Filament\Actions;
use Filament\Tables;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    public function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            TicketResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
            TicketResource::TransferOwnershipButton(
                Actions\Action::make('transferOwnership')
            )
        ];
    }
}

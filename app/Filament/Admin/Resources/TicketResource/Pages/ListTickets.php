<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        $tenant_id = Filament::getTenant()->team_id;

        $tabs = [
            'All' => Tab::make()
                ->badge(
                    Ticket::query()
                        ->where('team_id', $tenant_id)
                        ->count()
                ),
        ];

        foreach (TicketStatus::toArray() as $status) {
            $status_enum = TicketStatus::fromLabel($status);
            $status_value = $status_enum->value;
            $status_label = $status_enum->getLabel();

            $tabs[$status_label] = Tab::make()
                ->badge(
                    Ticket::query()
                        ->where('status', $status_value)
                        ->where('team_id', $tenant_id)
                        ->count()
                )
                ->modifyQueryUsing(
                    function (Builder $query) use ($status_value) {
                        $query->where('status', $status_value);
                    }
                );
        }

        return $tabs;
    }
}

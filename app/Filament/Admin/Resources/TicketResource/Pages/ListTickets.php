<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\TicketResource;
use App\Models\Ticket;
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
        $tenant_id = Filament::getTenant()?->id ?? null;

        $baseQuery = Ticket::query();
        if ($tenant_id) {
            $baseQuery->where('team_id', $tenant_id);
        }

        $tabs = [
            'All' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->mergeConstraintsFrom(clone $baseQuery)
                ),
        ];

        foreach (TicketStatus::toArray() as $status) {
            $status_enum = TicketStatus::fromLabel($status);
            $status_value = $status_enum->value;
            $status_label = $status_enum->getLabel();

            $tabs[$status_label] = Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($baseQuery, $status_value) {
                    $query->mergeConstraintsFrom(clone $baseQuery)
                        ->where('status', $status_value);
                });
        }

        return $tabs;
    }
}

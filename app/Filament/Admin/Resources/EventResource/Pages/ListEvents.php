<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Models\Event;
use Filament\Actions;
use App\Enums\EventStatus;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\EventResource;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Event')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $tenant_id = Filament::getTenant()?->team_id ?? null;

        $baseQuery = Event::query();
        if ($tenant_id) {
            $baseQuery->where('team_id', $tenant_id);
        }

        $tabs = [
            'All' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->mergeConstraintsFrom(clone $baseQuery)
                ),
        ];

        foreach (EventStatus::toArray() as $status) {
            $status_enum = EventStatus::fromLabel($status);
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

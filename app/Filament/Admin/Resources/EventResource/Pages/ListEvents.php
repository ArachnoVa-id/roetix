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
        $tenant_id = Filament::getTenant()->team_id;

        $tabs = [
            'All' => Tab::make()
                ->badge(
                    Event::query()
                        ->where('team_id', $tenant_id)
                        ->count()
                ),
        ];

        foreach (EventStatus::toArray() as $status) {
            $status_enum = EventStatus::fromLabel($status);
            $status_value = $status_enum->value;
            $status_label = $status_enum->getLabel();

            $tabs[$status_label] = Tab::make()
                ->badge(
                    Event::query()
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

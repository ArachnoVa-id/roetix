<?php

namespace App\Filament\Admin\Resources\VenueResource\Pages;

use App\Enums\VenueStatus;
use App\Filament\Admin\Resources\VenueResource;
use App\Models\Venue;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVenues extends ListRecords
{
    protected static string $resource = VenueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Venue')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $tenant_id = Filament::getTenant()->team_id;

        $tabs = [
            'All' => Tab::make()
                ->badge(
                    Venue::query()
                        ->where('team_id', $tenant_id)
                        ->count()
                ),
        ];

        foreach (VenueStatus::toArray() as $status) {
            $status_enum = VenueStatus::fromLabel($status);
            $status_value = $status_enum->value;
            $status_label = $status_enum->getLabel();

            $tabs[$status_label] = Tab::make()
                ->badge(
                    Venue::query()
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

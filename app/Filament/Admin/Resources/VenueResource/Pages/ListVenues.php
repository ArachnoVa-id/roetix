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
        $tenant_id = Filament::getTenant()?->id ?? null;

        $baseQuery = Venue::query();
        if ($tenant_id) {
            $baseQuery->where('team_id', $tenant_id);
        }

        $tabs = [
            'All' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->mergeConstraintsFrom(clone $baseQuery)
                ),
        ];

        foreach (VenueStatus::toArray() as $status) {
            $status_enum = VenueStatus::fromLabel($status);
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

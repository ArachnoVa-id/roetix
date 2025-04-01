<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Admin\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create Order')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $tenant_id = Filament::getTenant()?->team_id ?? null;

        $baseQuery = Order::query();
        if ($tenant_id) {
            $baseQuery->where('team_id', $tenant_id);
        }

        $tabs = [
            'All' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->mergeConstraintsFrom(clone $baseQuery)
                ),
        ];

        foreach (OrderStatus::toArray() as $status) {
            $status_enum = OrderStatus::fromLabel($status);
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

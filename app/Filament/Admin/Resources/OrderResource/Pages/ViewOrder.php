<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use App\Filament\Components\BackButtonAction;
use App\Models\Order;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Colors\Color;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            Actions\EditAction::make('Edit Order')
                ->icon('heroicon-m-pencil-square')
                ->color(Color::Orange),
            OrderResource::ChangeStatusButton(
                Actions\Action::make('changeStatus')
            ),
        ];
    }
}

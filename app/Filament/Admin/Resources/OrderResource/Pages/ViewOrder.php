<?php

namespace App\Filament\Admin\Resources\OrderResource\Pages;

use App\Filament\Admin\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

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
            Actions\EditAction::make('Edit Order')
                ->icon('heroicon-o-pencil'),
        ];
    }
}

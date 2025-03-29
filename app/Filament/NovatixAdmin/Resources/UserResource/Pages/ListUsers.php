<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Create User')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'All' => Tab::make()
                ->badge(
                    User::query()
                        ->count()
                ),
        ];

        foreach (UserRole::toArray() as $status) {
            $status_enum = UserRole::fromLabel($status);
            $status_value = $status_enum->value;
            $status_label = $status_enum->getLabel();

            $tabs[$status_label] = Tab::make()
                ->badge(
                    User::query()
                        ->where('role', $status_value)
                        ->count()
                )
                ->modifyQueryUsing(
                    function (Builder $query) use ($status_value) {
                        $query->where('role', $status_value);
                    }
                );
        }

        return $tabs;
    }
}

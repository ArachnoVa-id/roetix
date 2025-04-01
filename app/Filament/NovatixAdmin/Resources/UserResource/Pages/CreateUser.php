<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Filament\Components\BackButtonAction;
use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\IconPosition;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create User')
            ->icon('heroicon-o-plus');
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Create & Create Another User')
            ->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()->hidden();
    }

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            )
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->iconPosition(IconPosition::After)
        ];
    }

    public function afterCreate()
    {
        $data = $this->data;

        $user = $this->record;

        $userdata = $data ?? [];

        if (!empty($userdata)) {
            $teams = $userdata['teams'] ?? [];
            foreach ($teams as $team) {
                $team = Team::find($team);
                $user->teams()->attach($team);
            }
        }
    }
}

<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

use App\Filament\Components\BackButtonAction;
use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\Team;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            BackButtonAction::make(
                Actions\Action::make('back')
            ),
            Actions\DeleteAction::make('Delete Event')
                ->icon('heroicon-o-trash'),
        ];
    }

    public function afterSave()
    {
        $data = $this->data;

        $user = $this->record;

        $userdata = $data ?? [];

        if (!empty($userdata)) {
            $teams = $userdata['teams'] ?? [];

            $teams = array_map(function ($team) {
                // if team_id exists
                if (isset($team['team_id'])) {
                    return $team['team_id'];
                } else return $team['name'];
            }, $teams);

            // Attach the teams to the user
            $user->teams()->sync($teams);
            // Detach the teams nonexist in the array
            $user->teams()->detach(
                Team::whereNotIn('id', $teams)->pluck('id')
            );
        }
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Update User')
            ->icon('heroicon-o-check-circle');
    }
}

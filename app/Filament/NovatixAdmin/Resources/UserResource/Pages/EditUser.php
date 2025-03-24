<?php

namespace App\Filament\NovatixAdmin\Resources\UserResource\Pages;

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
            Actions\Action::make('Back')
                ->url(fn() => url()->previous())
                ->icon('heroicon-o-arrow-left')
                ->color('info'),
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
            foreach ($teams as $team) {
                $team = Team::find($team);
                $user->teams()->attach($team);
            }
        }
    }
}

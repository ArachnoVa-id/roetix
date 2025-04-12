<?php

namespace App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers;

use App\Filament\NovatixAdmin\Resources\UserResource;
use App\Models\User;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function infolist(Infolist $infolist): Infolist
    {
        return UserResource::infolist($infolist, showTeams: false);
    }

    public static function KickMemberButton(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('remove_member')
            ->label('Remove from Team')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Remove Member')
            ->modalDescription('Are you sure you want to remove this user from the team?')
            ->action(function (User $record, $livewire) {
                $team = $livewire->ownerRecord ?? null; // In RelationManager, this would be the Team

                if (!$team) {
                    Notification::make()
                        ->title('Team not found')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $team->users()->detach($record->id);

                    Notification::make()
                        ->title("Removed from Team")
                        ->body("User {$record->name} has been removed from the team.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed to Remove User')
                        ->body("Error: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            });
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return UserResource::table($table, filterRole: true, additionActions: [self::KickMemberButton()])
            ->heading('');
    }
}

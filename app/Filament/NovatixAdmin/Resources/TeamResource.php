<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Enums\UserRole;
use App\Filament\NovatixAdmin\Resources\TeamResource\Pages;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\EventsRelationManager;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\UsersRelationManager;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\VenuesRelationManager;
use App\Models\Team;
use App\Models\User;
use Filament\Forms;
use Filament\Actions;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN]);
    }

    public static function AddMemberButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Add Member')
            ->color(Color::Blue)
            ->icon('heroicon-o-user-plus')
            ->modalHeading('Add Member to Team')
            ->modalDescription('Select a user to add to this team.')
            ->form([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(function ($record) {
                        return
                            User::whereNotIn('id', $record->users->pluck('id'))
                            ->wherein('role', [
                                UserRole::VENDOR,
                                UserRole::EVENT_ORGANIZER,
                            ])
                            ->pluck('email', 'id');
                    })
                    ->optionsLimit(5)
                    ->searchable()
                    ->preload()
                    ->validationAttribute('User')
                    ->validationMessages([
                        'required' => 'A user is required',
                    ])
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                try {
                    // Add the selected user to the team
                    $user = User::find($data['user_id']);
                    $record->users()->attach($user);

                    // Send a notification
                    Notification::make()
                        ->title('Member Added to Team')
                        ->body("User {$user->name} has been added to the team {$record->name}.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    // Handle any errors that occur during the add member action
                    Notification::make()
                        ->title('Failed to Add Member')
                        ->body("Failed to add user to team {$record->name}: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            })
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'events',
                'events.team',
                'events.ticketCategories',
                'events.ticketCategories.eventCategoryTimeboundPrices',
                'events.ticketCategories.eventCategoryTimeboundPrices.timelineSession',
                'venues',
                'venues.seats',
                'venues.team',
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showMembers = true, bool $showEvents = true, bool $showVenues = true): Infolists\Infolist
    {
        return $infolist
            ->schema(
                [
                    Infolists\Components\Section::make('Team Information')
                        ->columns(2)
                        ->columnSpanFull()
                        ->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->icon('heroicon-o-users'),
                            Infolists\Components\TextEntry::make('code')
                                ->icon('heroicon-o-key'),
                            Infolists\Components\TextEntry::make('vendor_quota')
                                ->icon('heroicon-o-ticket')
                                ->label('Venue Quota')
                                ->formatStateUsing(fn($state) => max(0, $state)),
                            Infolists\Components\TextEntry::make('event_quota')
                                ->icon('heroicon-o-ticket')
                                ->label('Event Quota')
                                ->formatStateUsing(fn($state) => max(0, $state)),
                        ]),
                    Infolists\Components\Tabs::make('')
                        ->hidden(!$showMembers && !$showEvents && !$showVenues)
                        ->columnSpanFull()
                        ->schema([
                            Infolists\Components\Tabs\Tab::make('Members')
                                ->hidden(!$showMembers)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(UsersRelationManager::class)
                                ]),
                            Infolists\Components\Tabs\Tab::make('Events')
                                ->hidden(!$showEvents)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(EventsRelationManager::class)
                                ]),
                            Infolists\Components\Tabs\Tab::make('Venues')
                                ->hidden(!$showVenues)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(VenuesRelationManager::class)
                                ]),
                        ]),
                ]
            );
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Team Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->placeholder('Team name')
                            ->required()
                            ->maxLength(255)
                            ->validationAttribute('Team Name')
                            ->validationMessages([
                                'required' => 'The Team Name field is required.',
                                'max' => 'The Team Nmae field must not exceed :max characters.',
                            ]),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->validationAttribute('Team Code')
                            ->validationMessages([
                                'required' => 'The Team Code field is required.',
                                'max' => 'The Team Code field must not exceed :max characters.',
                            ])
                            ->reactive()
                            ->unique('teams', 'code', ignoreRecord: true)
                            ->afterStateUpdated(function (Forms\Set $set, $state) use ($form) {
                                $current_team_id = $form->model?->id ?? null;
                                $findTeam = Team::where('code', $state)->first();
                                if (
                                    $findTeam &&
                                    $findTeam->id !== $current_team_id
                                ) {
                                    $set('code', '');
                                } else {
                                    $set('code', strtoupper($state));
                                }
                            }),
                        Forms\Components\TextInput::make('vendor_quota')
                            ->label('Venue Quota')
                            ->placeholder('Venue Quota')
                            ->helperText('Remaining quota for vendors in this group. Will be reduced each usage.')
                            ->default(0)
                            ->minValue(0)
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (!is_numeric($state)) {
                                    $set('vendor_quota', 0);

                                    Notification::make()
                                        ->title('Vendor Quota Rejected')
                                        ->body('Vendor Quota must be a number')
                                        ->info()
                                        ->send();
                                }

                                if ($state < 0) {
                                    $set('vendor_quota', 0);

                                    Notification::make()
                                        ->title('Vendor Quota Rejected')
                                        ->body('Vendor Quota must be a positive number')
                                        ->info()
                                        ->send();
                                }
                            })
                            ->validationAttribute('Venue Quota')
                            ->validationMessages([
                                'required' => 'The Venue Quota field is required.',
                                'max' => 'The Venue Quota field must not exceed :max characters.',
                                'min' => 'The Venue Quota field must be at least :min characters.',
                            ]),
                        Forms\Components\TextInput::make('event_quota')
                            ->label('Event Quota')
                            ->placeholder('Event Quota')
                            ->helperText('Remaining quota for events in this group. Will be reduced each usage.')
                            ->default(0)
                            ->minValue(0)
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if (!is_numeric($state)) {
                                    $set('event_quota', 0);

                                    Notification::make()
                                        ->title('Event Quota Rejected')
                                        ->body('Event Quota must be a number')
                                        ->info()
                                        ->send();
                                }

                                if ($state < 0) {
                                    $set('event_quota', 0);

                                    Notification::make()
                                        ->title('Event Quota Rejected')
                                        ->body('Event Quota must be a positive number')
                                        ->info()
                                        ->send();
                                }
                            })
                            ->validationAttribute('Event Quota')
                            ->validationMessages([
                                'required' => 'The Event Quota field is required.',
                                'max' => 'The Event Quota field must not exceed :max characters.',
                                'min' => 'The Event Quota field must be at least :min characters.',
                            ]),
                    ])
            ]);
    }

    public static function table(Tables\Table $table, $showAddMemberAction = true, $additionActions = null): Tables\Table
    {
        $actions = [
            Tables\Actions\ViewAction::make()
                ->modalHeading('View Team'),
            Tables\Actions\EditAction::make()
                ->color(Color::Orange),
        ];

        if ($showAddMemberAction) {
            $actions[] = self::AddMemberButton(
                Tables\Actions\Action::make('addMember')
            );
        }

        if ($additionActions) {
            $actions = array_merge($actions, $additionActions);
        }

        $actions[] = Tables\Actions\DeleteAction::make();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('vendor_quota')
                    ->label('Vendor Quota')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('event_quota')
                    ->label('Event Quota')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make($actions),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }
}

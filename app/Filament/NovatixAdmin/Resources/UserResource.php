<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Enums\UserRole;
use App\Filament\Components\CustomPagination;
use App\Filament\NovatixAdmin\Resources\UserResource\Pages;
use App\Filament\NovatixAdmin\Resources\UserResource\RelationManagers\TeamsRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Resources;
use Filament\Tables;
use App\Models\Team;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resources\Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN]);
    }

    public static function KickMemberButton(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('remove_member')
            ->label('Remove from Team')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Remove Member')
            ->modalDescription(
                function ($record, $livewire) {
                    if ($record instanceof User) {
                        $team = $livewire->ownerRecord ?? null;
                        $user = $record;
                    } else if ($record instanceof Team) {
                        $team = $record;
                        $user = $livewire->ownerRecord ?? null;
                    }

                    if (!$team) {
                        return 'Team not found';
                    }

                    return "Are you sure you want to remove {$user->getFilamentName()} ({$user->email}) from the team {$team->name}?";
                }
            )
            ->action(function (User $record, $livewire) {
                if ($record instanceof User) {
                    $team = $livewire->ownerRecord ?? null;
                    $user = $record;
                } else if ($record instanceof Team) {
                    $team = $record;
                    $user = $livewire->ownerRecord ?? null;
                }
                if (!$team) {
                    Notification::make()
                        ->title('Team not found')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    $team->users()->detach($user->id);
                    Notification::make()
                        ->title("Removed from Team")
                        ->body("User {$user->getFilamentName()} has been removed from the team {$team->name}.")
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'teams'
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showTeams = true): Infolists\Infolist
    {
        return $infolist
            ->columns(4)
            ->schema([
                Infolists\Components\Section::make('User Information')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name')
                            ->icon('heroicon-o-user')
                            ->label('First Name'),
                        Infolists\Components\TextEntry::make('last_name')
                            ->icon('heroicon-o-user')
                            ->label('Last Name'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-at-symbol')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('role')
                            ->formatStateUsing(fn($state) => UserRole::tryFrom($state)->getLabel())
                            ->color(fn($state) => UserRole::tryFrom($state)->getColor())
                            ->icon(fn($state) => UserRole::tryFrom($state)->getIcon())
                            ->badge(),
                    ]),
                Infolists\Components\Section::make('User Contact')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->relationship('contactInfo')
                    ->schema([
                        Infolists\Components\TextEntry::make('phone_number')
                            ->icon('heroicon-o-phone')
                            ->label('Phone Number'),
                        Infolists\Components\TextEntry::make('email')
                            ->icon('heroicon-o-at-symbol')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('whatsapp_number')
                            ->icon('heroicon-o-phone')
                            ->label('WhatsApp Number'),
                        Infolists\Components\TextEntry::make('instagram')
                            ->icon('heroicon-o-share')
                            ->label('Instagram Handle')
                            ->prefix('@'),
                    ]),
                Infolists\Components\Tabs::make('')
                    ->columnSpanFull()
                    ->hidden(!$showTeams)
                    ->schema([
                        Infolists\Components\Tabs\Tab::make('Teams')
                            ->schema([
                                \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                    ->manager(TeamsRelationManager::class),
                            ]),
                    ]),
            ]);
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        $allTeams = Team::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return $form
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 2,
            ])
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 1,
                    ])
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('first_name')
                                ->label('First Name')
                                ->placeholder('First Name')
                                ->required()
                                ->maxLength(255)
                                ->validationAttribute('First Name')
                                ->validationMessages([
                                    'required' => 'The First Name field is required.',
                                    'max' => 'The First Name may not be greater than 255 characters.',
                                ]),
                            Forms\Components\TextInput::make('last_name')
                                ->label('Last Name')
                                ->placeholder('Last Name')
                                ->required()
                                ->maxLength(255)
                                ->validationAttribute('Last Name')
                                ->validationMessages([
                                    'required' => 'The Last Name field is required.',
                                    'max' => 'The Last Name may not be greater than 255 characters.',
                                ]),
                            Forms\Components\Select::make('role')
                                ->options(UserRole::editableOptions())
                                ->preload()
                                ->reactive()
                                ->searchable()
                                ->validationAttribute('Role')
                                ->validationMessages([
                                    'required' => 'The Role field is required.',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('email')
                                ->label('Personal Email')
                                ->required()
                                ->email()
                                ->live(debounce: 1000)
                                ->validationAttribute('Email')
                                ->validationMessages([
                                    'required' => 'The Email field is required.',
                                    'email' => 'The Email must be a valid email address.',
                                ])
                                ->afterStateUpdated(function ($state, $record, Forms\Set $set) {
                                    $find = User::where('email', $state)->first();
                                    if ($record && $record->id === $find?->id) return;
                                    if ($find) {
                                        $set('email', null);
                                        Notification::make()
                                            ->title('Email Already Exists')
                                            ->body('The email you entered already exists in the system. Please enter a different email.')
                                            ->danger()
                                            ->send();
                                    }
                                })
                                ->unique('users', 'email', ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\Toggle::make('change_password')
                                ->label('Change Password')
                                ->reactive()
                                ->hidden(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->default(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                            Forms\Components\TextInput::make('password')
                                ->disabled(fn(Forms\Get $get) => !$get('change_password'))
                                ->hidden(fn(Forms\Get $get) => !$get('change_password'))
                                ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->password()
                                ->placeholder('Password')
                                ->validationAttribute('Password')
                                ->validationMessages([
                                    'required' => 'The Password field is required.',
                                    'min' => 'The Password must be at least 8 characters.',
                                    'max' => 'The Password may not be greater than 255 characters.',
                                ])
                                ->maxLength(255)
                                ->minLength(8),
                        ]),
                    ]),
                Forms\Components\Section::make('User Contact')
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 1,
                    ])
                    ->relationship('contactInfo')
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->maxLength(24)
                            ->validationAttribute('Phone Number')
                            ->validationMessages([
                                'max' => 'The phone number may not be greater than 24 characters.',
                                'regex' => 'The phone number format is invalid.',
                            ])
                            ->placeholder('e.g. 089919991999')
                            ->label('Phone Number')
                            ->tel(),

                        Forms\Components\TextInput::make('email')
                            ->label('Professional Email')
                            ->maxLength(255)
                            ->validationAttribute('Email')
                            ->validationMessages([
                                'max' => 'The email may not be greater than 255 characters.',
                                'email' => 'The email must be a valid email address.',
                            ])
                            ->placeholder('e.g. username@example.com')
                            ->label('Email')
                            ->email(),

                        Forms\Components\TextInput::make('whatsapp_number')
                            ->maxLength(24)
                            ->validationAttribute('WhatsApp Number')
                            ->validationMessages([
                                'required' => 'The WhatsApp number field is required.',
                                'max' => 'The WhatsApp number may not be greater than 24 characters.',
                                'regex' => 'The WhatsApp number format is invalid.',
                            ])
                            ->placeholder('e.g. 089919991999')
                            ->label('WhatsApp Number')
                            ->tel(),

                        Forms\Components\TextInput::make('instagram')
                            ->maxLength(256)
                            ->validationAttribute('Instagram Handle')
                            ->validationMessages([
                                'required' => 'The Instagram handle field is required.',
                                'max' => 'The Instagram handle may not be greater than 256 characters.',
                            ])
                            ->placeholder('e.g. novatix.id')
                            ->label('Instagram Handle')
                            ->prefix('@'),
                    ]),
                Forms\Components\Section::make('Teams')
                    ->hidden(fn(Forms\Get $get) => in_array($get('role'), [UserRole::USER->value, UserRole::ADMIN->value]) ? 1 : 0)
                    ->columnSpan([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('teams')
                            ->grid(4)
                            ->live()
                            ->minItems(fn(Forms\Get $get) => in_array($get('role'), [UserRole::USER->value, UserRole::ADMIN->value]) ? 0 : 1)
                            ->validationAttribute('Teams')
                            ->validationMessages([
                                'required' => 'The Teams field is required.',
                                'min' => 'The Teams field must have at least 1 team.',
                            ])
                            ->addable(function ($get) use ($allTeams) {
                                // overall team size
                                $teamSize = count($allTeams);

                                // count how many teams slots are already created
                                $currentSlots = collect($get('teams'))->count();
                                if ($currentSlots >= $teamSize) {
                                    return false;
                                }

                                // if there exist null values, then disable (location is inside the first array of the array)
                                $existsNull = collect($get('teams'))
                                    ->pluck('name')
                                    ->contains(null);
                                if ($existsNull) {
                                    return false;
                                }

                                // check if there is any remaining team to add
                                $selectedTeams = collect($get('teams'))
                                    ->pluck('name')
                                    ->filter() // Remove null values
                                    ->toArray();

                                $remainingTeams = Team::whereNotIn('id', $selectedTeams)
                                    ->count();

                                return $remainingTeams > 0;
                            })
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('name')
                                    ->label('Assign to Team')
                                    ->options(function (callable $get) use ($allTeams) {
                                        // Get already selected team IDs
                                        $selectedTeams = collect($get('../../teams'))
                                            ->pluck('name', 'team_id')
                                            ->filter()
                                            ->toArray();

                                        // Exclude already selected teams
                                        $return = array_diff_key($allTeams, $selectedTeams);

                                        return $return;
                                    })
                                    ->preload()
                                    ->searchable()
                                    ->optionsLimit(5)
                                    ->validationAttribute('Team Name')
                                    ->validationMessages([
                                        'required' => 'The Team Name field is required.',
                                    ])
                                    ->required()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $name = Team::find($state);
                                        if ($name) {
                                            $set('team_id', $name->id);
                                            $set('name', $name->name);
                                        } else {
                                            $set('team_id', null);
                                            $set('name', null);
                                        }
                                    })
                            ])
                            ->afterStateHydrated(function ($set, $record) {
                                if ($record) {
                                    $return = [];

                                    foreach ($record->teams as $team) {
                                        $uuid = \Illuminate\Support\Str::uuid()->toString();
                                        $return[$uuid] = [
                                            'team_id' => $team->id,
                                            'name' => $team->name,
                                        ];
                                    }

                                    $set('teams', $return);
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table, bool $filterRole = false, $additionActions = null): Tables\Table
    {
        $actions = [
            Tables\Actions\ViewAction::make()
                ->modalHeading('View User'),
            Tables\Actions\EditAction::make()
                ->color(Color::Orange),
        ];

        if ($additionActions)
            $actions = array_merge($actions, $additionActions);

        $actions[] = Tables\Actions\DeleteAction::make();

        return
            CustomPagination::apply($table)
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('role')
                    ->formatStateUsing(fn($state) => UserRole::tryFrom($state)->getLabel())
                    ->color(fn($state) => UserRole::tryFrom($state)->getColor())
                    ->icon(fn($state) => UserRole::tryFrom($state)->getIcon())
                    ->badge(),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Teams')
                    ->searchable()
                    ->formatStateUsing(fn() => 'Hover to View')
                    ->tooltip(
                        fn(User $record) =>
                        $record->teams->pluck('name')->sort()->join(', ')
                    )
                    ->getStateUsing(fn(User $record) => $record->teams->pluck('name')->join(', ')),

            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('role')
                        ->options(UserRole::editableOptions())
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->hidden(!$filterRole),
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}

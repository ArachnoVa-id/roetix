<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Enums\UserRole;
use App\Filament\NovatixAdmin\Resources\UserResource\Pages;
use App\Filament\NovatixAdmin\Resources\UserResource\RelationManagers\TeamsRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Resources;
use Filament\Tables;
use App\Models\Team;
use Filament\Infolists;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resources\Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin']);
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showTeams = true): Infolists\Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('User Information')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('first_name')
                        ->label('First Name'),
                    Infolists\Components\TextEntry::make('last_name')
                        ->label('Last Name'),
                    Infolists\Components\TextEntry::make('email'),
                    Infolists\Components\TextEntry::make('role')
                        ->formatStateUsing(fn($state) => UserRole::tryFrom($state)->getLabel())
                        ->color(fn($state) => UserRole::tryFrom($state)->getColor())
                        ->badge(),
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
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('first_name')
                                ->label('First Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('last_name')
                                ->label('Last Name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->required()
                                ->email()
                                ->unique('users', 'email', ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\Toggle::make('change_password')
                                ->label('Change Password')
                                ->reactive()
                                ->hidden(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->default(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
                            Forms\Components\TextInput::make('password')
                                ->disabled(fn(Forms\Get $get) => !$get('change_password'))
                                ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                                ->password()
                                ->maxLength(255),
                            Forms\Components\Select::make('role')
                                ->options(UserRole::editableOptions())
                                ->required(),
                        ]),
                        Forms\Components\Section::make('Teams')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Repeater::make('teams')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('team_id')
                                            ->label('Assign to Team')
                                            ->options(function (callable $get) {
                                                // Get already selected team IDs
                                                $selectedTeams = collect($get('../../teams'))
                                                    ->pluck('team_id')
                                                    ->filter() // Remove null values
                                                    ->toArray();

                                                // Exclude already selected teams
                                                return Team::whereNotIn('team_id', $selectedTeams)
                                                    ->pluck('name', 'team_id');
                                            })
                                            ->searchable()
                                            ->optionsLimit(5)
                                            ->required()
                                    ])
                            ])
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
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
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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

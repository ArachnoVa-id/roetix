<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Filament\NovatixAdmin\Resources\UserResource\Pages;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin']);
    }

    public static function infolist(Infolists\Infolist $infolist): Infolists\Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        Infolists\Components\TextEntry::make('first_name'),
                        Infolists\Components\TextEntry::make('last_name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('role'),
                        Infolists\Components\TextEntry::make('team_id')
                            ->label('Team')
                            ->getStateUsing(fn(User $record) => $record->teams->pluck('name')->join(', ')),
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
                            Forms\Components\TextInput::make('password')
                                ->required()
                                ->password()
                                ->maxLength(255),
                            Forms\Components\Select::make('role')
                                ->options([
                                    'vendor' => 'vendor',
                                    'event-organizer' => 'event-organizer',
                                ])
                                ->required(),
                        ]),
                        Forms\Components\Section::make('Teams')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Repeater::make('teams')
                                    ->relationship('teams')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('team_id')
                                            ->label('Assign to Team')
                                            ->options(Team::pluck('name', 'team_id'))
                                            ->searchable()
                                            ->optionsLimit(5)
                                            ->required(),
                                    ])
                            ]),
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')->label('First Name'),
                Tables\Columns\TextColumn::make('last_name')->label('Last Name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('teams.name')
                    ->label('Teams')
                    ->searchable()
                    ->getStateUsing(fn(User $record) => $record->teams->pluck('name')->join(', ')),

            ])
            ->filters([
                //
            ])
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

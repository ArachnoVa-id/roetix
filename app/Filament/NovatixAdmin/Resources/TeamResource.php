<?php

namespace App\Filament\NovatixAdmin\Resources;

use App\Filament\NovatixAdmin\Resources\TeamResource\Pages;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\EventsRelationManager;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\UsersRelationManager;
use App\Filament\NovatixAdmin\Resources\TeamResource\RelationManagers\VenuesRelationManager;
use App\Models\Team;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\Auth;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin']);
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
                            Infolists\Components\TextEntry::make('name'),
                            Infolists\Components\TextEntry::make('code'),

                            Infolists\Components\TextEntry::make('vendor_quota')
                                ->label('Vendor Quota')
                                ->formatStateUsing(fn($state) => max(0, $state)),

                            Infolists\Components\TextEntry::make('event_quota')
                                ->label('Event Quota')
                                ->formatStateUsing(fn($state) => max(0, $state)),
                        ]),
                    Infolists\Components\Tabs::make('')
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
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->unique('teams', 'code', ignoreRecord: true)
                            ->afterStateUpdated(function (Forms\Set $set, $state) use ($form) {
                                $current_team_id = $form->model?->team_id ?? null;
                                $findTeam = Team::where('code', $state)->first();
                                if (
                                    $findTeam &&
                                    $findTeam->team_id !== $current_team_id
                                ) {
                                    $set('code', '');
                                } else {
                                    $set('code', strtoupper($state));
                                }
                            }),
                        Forms\Components\TextInput::make('vendor_quota')
                            ->label('Vendor Quota')
                            ->minValue(0)
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('event_quota')
                            ->label('Event Quota')
                            ->minValue(0)
                            ->numeric()
                            ->maxLength(255),
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->modalHeading('View Team'),
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
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }
}

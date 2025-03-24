<?php

namespace App\Filament\Admin\Resources;

use App\Enums\VenueStatus;
use Filament\Forms;
use Filament\Tables;
use App\Models\Venue;
use Filament\Infolists;
use Filament\Resources;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\VenueResource\Pages;
use App\Filament\Admin\Resources\VenueResource\RelationManagers\EventsRelationManager;
use Filament\Actions;

class VenueResource extends Resources\Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin', 'vendor']);
    }

    public static function EditVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Edit Venue')
            ->icon('heroicon-m-map')
            ->color('success')
            ->url(fn($record) => "/seats/grid-edit?venue_id={$record->venue_id}");
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showEvents = true): Infolists\Infolist
    {
        return $infolist
            ->columns(2)
            ->schema(
                [
                    Infolists\Components\Section::make('Venue Information')
                        ->columnSpan(1)
                        ->schema([
                            Infolists\Components\TextEntry::make('venue_id')
                                ->label('Venue ID'),
                            Infolists\Components\TextEntry::make('name'),
                            Infolists\Components\TextEntry::make('location'),
                            Infolists\Components\TextEntry::make('capacity_qty')
                                ->label('Capacity')
                                ->getStateUsing(fn($record) => $record->capacity() ?? 'N/A'),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn($state) => VenueStatus::tryFrom($state)->getLabel())
                                ->color(fn($state) => VenueStatus::tryFrom($state)->getColor())
                                ->badge(),
                        ]),
                    Infolists\Components\Section::make('Venue Contact')
                        ->relationship('contactInfo', 'venue_id')
                        ->columnSpan(1)
                        ->schema([
                            Infolists\Components\TextEntry::make('phone_number'),
                            Infolists\Components\TextEntry::make('email'),
                            Infolists\Components\TextEntry::make('whatsapp_number'),
                            Infolists\Components\TextEntry::make('instagram'),
                        ]),
                    Infolists\Components\Section::make('Venue Owner')
                        ->columnSpanFull()
                        ->relationship('team', 'team_id')
                        ->columns(2)
                        ->schema([
                            Infolists\Components\TextEntry::make('name'),
                            Infolists\Components\TextEntry::make('code'),
                        ]),
                    Infolists\Components\Tabs::make()
                        ->columnSpanFull()
                        ->schema([
                            Infolists\Components\Tabs\Tab::make('Events')
                                ->hidden(!$showEvents)
                                ->schema([
                                    \Njxqlus\Filament\Components\Infolists\RelationManager::make()
                                        ->manager(EventsRelationManager::class)
                                ])
                        ])
                ]
            );
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->columns(2)
            ->schema([
                Forms\Components\Section::make('Venue Information')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(VenueStatus::editableOptions())
                            ->required(),
                    ]),
                Forms\Components\Section::make('Venue Contact')
                    ->relationship('contactInfo', 'venue_id')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('WhatsApp Number')
                            ->tel(),

                        Forms\Components\TextInput::make('instagram')
                            ->label('Instagram Handle')
                            ->prefix('@'),
                    ])
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue_id')
                    ->limit(50)
                    ->label('Venue'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Capacity')
                    ->getStateUsing(fn($record) => $record->capacity() ?? 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => VenueStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => VenueStatus::tryFrom($state)->getColor())
                    ->badge(),
            ])
            ->filters(
                [
                    Tables\Filters\Filter::make('capacity')
                        ->columns(2)
                        ->form([
                            Forms\Components\TextInput::make('min')
                                ->placeholder('Min')
                                ->numeric()
                                ->reactive()
                                ->formatStateUsing(fn($state) => $state ?? null)
                                ->afterStateUpdated(fn($state) => $state ? ($state < 0 ? 0 : $state) : null)
                                ->label('Min Capacity')
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('max')
                                ->placeholder('Max')
                                ->numeric()
                                ->reactive()
                                ->formatStateUsing(fn($state) => $state ?? null)
                                ->afterStateUpdated(fn($state) => $state ? ($state < 0 ? 0 : $state) : null)
                                ->label('Max Capacity')
                                ->columnSpan(1),
                        ])
                        ->query(function ($query, array $data) {
                            return $query->whereIn('venue_id', function ($subquery) use ($data) {
                                $subquery->select('venues.venue_id')
                                    ->from('venues')
                                    ->leftJoin('seats', 'venues.venue_id', '=', 'seats.venue_id')
                                    ->groupBy('venues.venue_id')
                                    ->havingRaw('COUNT(seats.venue_id) >= ?', [(int) (empty($data['min']) ? 0 : $data['min'])])
                                    ->havingRaw('COUNT(seats.venue_id) <= ?', [(int) (empty($data['max']) ? PHP_INT_MAX : $data['max'])]);
                            });
                        }),
                    Tables\Filters\SelectFilter::make('status')
                        ->options(VenueStatus::editableOptions())
                        ->multiple(),
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    self::EditVenueButton(
                        Tables\Actions\Action::make('Edit Venue')
                    ),
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
            'index' => Pages\ListVenues::route('/'),
            'create' => Pages\CreateVenue::route('/create'),
            'edit' => Pages\EditVenue::route('/{record}/edit'),
            'view' => Pages\ViewVenue::route('/{record}'),
        ];
    }
}

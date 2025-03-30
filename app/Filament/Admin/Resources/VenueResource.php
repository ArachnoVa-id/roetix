<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use App\Models\Team;
use App\Models\User;
use Filament\Tables;
use App\Models\Venue;
use Filament\Actions;
use App\Enums\UserRole;
use Filament\Infolists;
use Filament\Resources;
use App\Enums\VenueStatus;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\VenueResource\Pages;
use App\Filament\Admin\Resources\VenueResource\RelationManagers\EventsRelationManager;

class VenueResource extends Resources\Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, [UserRole::ADMIN->value, UserRole::VENDOR->value]);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        if (!$user || $user->role !== UserRole::VENDOR->value) {
            return false;
        }

        $user = User::find($user->id);

        $tenant_id = Filament::getTenant()->team_id;

        $team = $user->teams()->where('teams.team_id', $tenant_id)->first();

        if (!$team) {
            return false;
        }

        return $team->vendor_quota > $team->venues->count();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Change Status')
            ->color('success')
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->modalDescription('Select a new status for this venue.')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(VenueStatus::editableOptions())
                    ->preload()
                    ->default(fn($record) => $record->status) // Set the current value as default
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                try {
                    $record->update(['status' => $data['status']]);

                    Notification::make()
                        ->title('Success')
                        ->body("Venue {$record->name} status has been changed to " . VenueStatus::tryFrom($data['status'])->getLabel())
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed')
                        ->body("Failed to change venue {$record->name} status: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            })
            ->modal(true);
    }

    public static function EditVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Edit Venue')
            ->icon('heroicon-m-map')
            ->color('info')
            ->url(fn($record) => "/seats/grid-edit?venue_id={$record->venue_id}");
    }

    public static function ExportVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Export Venue')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('info')
            ->action(function ($record) {
                try {
                    $record->exportSeats();
                    Notification::make()
                        ->title('Success')
                        ->body("Venue {$record->name} seats have been exported.")
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed')
                        ->body("Failed to export venue {$record->name} seats: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            });
    }

    public static function ImportVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Import Venue')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('info')
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\FileUpload::make('venue_json')
                    ->label('Upload Venue JSON')
                    ->required()
                    ->acceptedFileTypes(['application/json']) // Accepts only .json files
                    ->disk('local'),
            ])
            ->action(function ($record, array $data) {
                try {
                    // Get the uploaded file path
                    $filePath = $data['venue_json'];

                    if (!$filePath) {
                        return;
                    }

                    // Read the file content
                    $jsonContent = file_get_contents(storage_path('app/private/' . $filePath));

                    // Decode the JSON content
                    $config = json_decode($jsonContent, true);

                    // Optionally delete the original temporary file
                    Storage::disk('local')->delete($filePath);

                    // Call the import function
                    [$res, $message] = $record->importSeats(
                        config: $config,
                    );

                    if ($res)
                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body($message)
                            ->send();
                    else Notification::make()
                        ->danger()
                        ->title('Failed')
                        ->body($message)
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed')
                        ->body("Failed to import venue {$record->name} seats: {$e->getMessage()}")
                        ->danger()
                        ->send();
                }
            });
    }

    public static function infolist(Infolists\Infolist $infolist, bool $showEvents = true): Infolists\Infolist
    {
        return $infolist
            ->columns([
                'default' => 1,
                'sm' => 1,
                'md' => 2,
            ])
            ->schema(
                [
                    Infolists\Components\Section::make('Venue Information')
                        ->columnSpan([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 1,
                        ])
                        ->columns([
                            'default' => 1,
                            'sm' => 1,
                            'md' => 2,
                        ])
                        ->schema([
                            Infolists\Components\TextEntry::make('venue_id')
                                ->label('Venue ID')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ]),
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
                    Infolists\Components\Group::make([
                        Infolists\Components\Section::make('Venue Contact')
                            ->relationship('contactInfo', 'venue_id')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->columns([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                Infolists\Components\TextEntry::make('phone_number'),
                                Infolists\Components\TextEntry::make('email'),
                                Infolists\Components\TextEntry::make('whatsapp_number'),
                                Infolists\Components\TextEntry::make('instagram'),
                            ]),
                        Infolists\Components\Section::make('Venue Owner')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->relationship('team', 'team_id')
                            ->columns([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                Infolists\Components\TextEntry::make('name'),
                                Infolists\Components\TextEntry::make('code'),
                            ]),
                    ]),
                    Infolists\Components\Tabs::make()
                        ->hidden(!$showEvents)
                        ->columnSpanFull()
                        ->schema([
                            Infolists\Components\Tabs\Tab::make('Events')
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
            ->schema([
                Forms\Components\Section::make('Venue Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Venue Contact')
                    ->relationship('contactInfo', 'venue_id')
                    ->columns(2)
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

    public static function table(Tables\Table $table, bool $filterStatus = false): Tables\Table
    {
        $user = User::find(Auth::id());

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Venue Name')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('team.name')
                    ->label('Team Name')
                    ->searchable()
                    ->sortable()
                    ->hidden(!($user->isAdmin()))
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
                        ->searchable()
                        ->multiple()
                        ->preload()
                        ->hidden(!$filterStatus),
                    Tables\Filters\SelectFilter::make('team_id')
                        ->label('Filter by Team')
                        ->relationship('team', 'name')
                        ->searchable()
                        ->optionsLimit(5)
                        ->preload()
                        ->multiple()
                        ->options(Team::pluck('name', 'team_id')->toArray())
                        ->hidden(!($user->isAdmin())),
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->modalHeading('View Venue'),
                    Tables\Actions\EditAction::make(),
                    self::ChangeStatusButton(
                        Tables\Actions\Action::make('changeStatus')
                    ),
                    self::EditVenueButton(
                        Tables\Actions\Action::make('editVenue')
                    ),
                    self::ExportVenueButton(
                        Tables\Actions\Action::make('exportVenue')
                    ),
                    self::ImportVenueButton(
                        Tables\Actions\Action::make('importVenue')
                    )
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

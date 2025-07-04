<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use App\Models\Team;
use Filament\Tables;
use App\Models\Venue;
use Filament\Actions;
use App\Enums\UserRole;
use Filament\Infolists;
use Filament\Resources;
use App\Enums\VenueStatus;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Filament\Admin\Resources\VenueResource\Pages;
use App\Filament\Admin\Resources\VenueResource\RelationManagers\EventsRelationManager;
use App\Filament\Components\CustomPagination;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;

class VenueResource extends Resources\Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    public static function canAccess(): bool
    {
        $user = session('auth_user');

        return $user && $user->isAllowedInRoles([UserRole::ADMIN, UserRole::VENDOR]);
    }

    public static function canCreate(): bool
    {
        $user = session('auth_user');

        if (!$user || !$user->isAllowedInRoles([UserRole::VENDOR])) {
            return false;
        }

        $tenant_id = Filament::getTenant()->id;

        $team = $user->teams()->where('teams.id', $tenant_id)->first();

        if (!$team) {
            return false;
        }

        return $team->vendor_quota > 0;
    }

    public static function canDelete(Model $record): bool
    {
        $user = session('auth_user');

        return $user->isAdmin();
    }

    public static function ChangeStatusButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Change Status')
            ->color(Color::Fuchsia)
            ->icon('heroicon-o-cog')
            ->modalHeading('Change Status')
            ->modalDescription('Select a new status for this venue.')
            ->form([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(VenueStatus::editableOptions())
                    ->preload()
                    ->default(fn($record) => $record->status) // Set the current value as default
                    ->validationAttribute('Status')
                    ->validationMessages([
                        'required' => 'The Status field is required',
                    ])
                    ->searchable()
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
            ->modalWidth('sm')
            ->modal(true);
    }

    public static function EditVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Edit Venue')
            ->icon('heroicon-m-map')
            ->color(Color::Indigo)
            ->url(fn($record) => "/seats/grid-edit?venue_id={$record->id}");
    }

    public static function ExportVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Export Venue')
            ->icon('heroicon-o-arrow-down-tray')
            ->color(Color::Emerald)
            ->url(fn($record) => route('venues.export', ['venue' => $record->id]));
    }

    public static function ImportVenueButton($action): Actions\Action | Tables\Actions\Action | Infolists\Components\Actions\Action
    {
        return $action
            ->label('Import Venue')
            ->icon('heroicon-o-arrow-up-tray')
            ->color(Color::Teal)
            ->requiresConfirmation()
            ->form([
                \Filament\Forms\Components\FileUpload::make('venue_json')
                    ->label('Upload Venue JSON')
                    ->required()
                    ->acceptedFileTypes(['application/json']) // Accepts only .json files
                    ->validationMessages([
                        'required' => 'The venue JSON file is required.',
                        'mimes' => 'The venue JSON file must be a valid JSON file.',
                    ])
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'events',
                'events.team',
                'events.ticketCategories',
                'events.ticketCategories.eventCategoryTimeboundPrices',
                'events.ticketCategories.eventCategoryTimeboundPrices.timelineSession',
            ]);
    }

    public static function infolist(Infolists\Infolist $infolist, $record = null, bool $showEvents = true): Infolists\Infolist
    {
        return $infolist
            ->record($record ?? $infolist->record)
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
                            Infolists\Components\TextEntry::make('id')
                                ->icon('heroicon-o-hashtag')
                                ->label('Venue ID')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                ]),
                            Infolists\Components\TextEntry::make('name')
                                ->icon('heroicon-o-map-pin'),
                            Infolists\Components\TextEntry::make('location')
                                ->icon('heroicon-o-map'),
                            Infolists\Components\TextEntry::make('capacity_qty')
                                ->icon('heroicon-o-users')
                                ->label('Capacity')
                                ->getStateUsing(fn($record) => $record->seats->count() ?? 'N/A'),
                            Infolists\Components\TextEntry::make('status')
                                ->formatStateUsing(fn($state) => VenueStatus::tryFrom($state)->getLabel())
                                ->color(fn($state) => VenueStatus::tryFrom($state)->getColor())
                                ->icon(fn($state) => VenueStatus::tryFrom($state)->getIcon())
                                ->badge(),
                        ]),
                    Infolists\Components\Group::make([
                        Infolists\Components\Section::make('Venue Contact')
                            ->relationship('contactInfo')
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
                        Infolists\Components\Section::make('Venue Owner')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                            ])
                            ->relationship('team', 'id')
                            ->columns([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->icon('heroicon-o-users'),
                                Infolists\Components\TextEntry::make('code')
                                    ->icon('heroicon-o-key'),
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
                            ->maxLength(255)
                            ->placeholder('Venue Name')
                            ->validationAttribute('Name')
                            ->validationMessages([
                                'required' => 'The name field is required.',
                                'max' => 'The name may not be greater than 255 characters.',
                            ])
                            ->label('Name')
                            ->required(),
                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->placeholder('Location')
                            ->validationAttribute('Location')
                            ->validationMessages([
                                'required' => 'The location field is required.',
                                'max' => 'The location may not be greater than 255 characters.',
                            ])
                            ->label('Location')
                            ->required(),
                    ]),
                Forms\Components\Section::make('Venue Contact')
                    ->relationship('contactInfo')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('phone_number')
                            ->maxLength(24)
                            ->validationAttribute('Phone Number')
                            ->validationMessages([
                                'required' => 'The phone number field is required.',
                                'max' => 'The phone number may not be greater than 24 characters.',
                            ])
                            ->placeholder('e.g. 089919991999')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),

                        Forms\Components\TextInput::make('email')
                            ->maxLength(255)
                            ->validationAttribute('Email')
                            ->validationMessages([
                                'required' => 'The email field is required.',
                                'max' => 'The email may not be greater than 255 characters.',
                            ])
                            ->placeholder('e.g. username@example.com')
                            ->label('Email')
                            ->email()
                            ->required(),

                        Forms\Components\TextInput::make('whatsapp_number')
                            ->maxLength(24)
                            ->validationAttribute('WhatsApp Number')
                            ->validationMessages([
                                'required' => 'The WhatsApp number field is required.',
                                'max' => 'The WhatsApp number may not be greater than 24 characters.',
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
                    ])
            ]);
    }

    public static function table(Tables\Table $table, bool $filterStatus = false, bool $filterCapacity = true): Tables\Table
    {
        $user = session('auth_user');

        return
            CustomPagination::apply($table)
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
                    ->getStateUsing(fn($record) => $record->seats->count() ?? 'N/A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn($state) => VenueStatus::tryFrom($state)->getLabel())
                    ->color(fn($state) => VenueStatus::tryFrom($state)->getColor())
                    ->icon(fn($state) => VenueStatus::tryFrom($state)->getIcon())
                    ->badge(),
            ])
            ->filters(
                [
                    Tables\Filters\SelectFilter::make('team_id')
                        ->label('Filter by Team')
                        ->relationship('team', 'name')
                        ->searchable()
                        ->optionsLimit(5)
                        ->preload()
                        ->multiple()
                        ->options(Team::pluck('name', 'id')->toArray())
                        ->hidden(!($user->isAdmin())),
                    Tables\Filters\Filter::make('capacity')
                        ->columns(2)
                        ->hidden(!$filterCapacity)
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
                            return $query
                                ->whereIn('id', function ($subquery) use ($data) {
                                    $subquery->select('venues.id')
                                        ->from('venues')
                                        ->leftJoin('seats', 'venues.id', '=', 'seats.venue_id')
                                        ->groupBy('venues.id')
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
                ],
                layout: Tables\Enums\FiltersLayout::Modal
            )
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading('View Venue'),
                    Tables\Actions\EditAction::make()
                        ->color(Color::Orange),
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
                    ),
                    Tables\Actions\DeleteAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ]);
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

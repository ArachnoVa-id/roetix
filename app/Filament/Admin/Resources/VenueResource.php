<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Venue;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\VenueResource\Pages;

class VenueResource extends Resource
{
    protected static ?string $model = Venue::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, ['admin', 'vendor']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required(),
                Forms\Components\TextInput::make('location')
                    ->label('Location')
                    ->required(),
                Forms\Components\TextInput::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->required(),
                Forms\Components\Fieldset::make('New Contact')
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
                    ]),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('venue_id'),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('location'),
                Tables\Columns\TextColumn::make('capacity'),
                Tables\Columns\TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('editVenue')
                        ->label('Edit Venue')
                        ->icon('heroicon-m-map')
                        ->button()
                        ->color('success')
                        ->url(fn($record) => "/seats/grid-edit?venue_id={$record->venue_id}")
                        ->openUrlInNewTab(),
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
        ];
    }
}

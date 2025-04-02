<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ColorPreview extends Component implements HasForms
{
    use InteractsWithForms;

    public $record;
    public $primary_color;
    public $secondary_color;
    public $text_primary_color;
    public $text_secondary_color;

    public function mount($record)
    {
        $this->record = $record;

        $settings = $record?->eventVariables ?? null;

        $this->primary_color = $settings->primary_color ?? '#ffffff';
        $this->secondary_color = $settings->secondary_color ?? '#aaaaaa';
        $this->text_primary_color = $settings->text_primary_color ?? '#000000';
        $this->text_secondary_color = $settings->text_secondary_color ?? '#333333';

        // Fill the form with retrieved values
        $this->form->fill([
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'text_primary_color' => $this->text_primary_color,
            'text_secondary_color' => $this->text_secondary_color,
        ]);

        Cache::put('color_preview_' . Auth::user()->id, [
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'text_primary_color' => $this->text_primary_color,
            'text_secondary_color' => $this->text_secondary_color,
        ], 60 * 15);
    }

    public function updated($property, $value)
    {
        $user = Auth::user();
        if (!$user) return;

        $this->$property = $value;

        Cache::put('color_preview_' . $user->id, [
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'text_primary_color' => $this->text_primary_color,
            'text_secondary_color' => $this->text_secondary_color,
        ], 60 * 15);
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                ColorPicker::make('primary_color')
                    ->placeholder('Primary Color')
                    ->label('Primary Color')
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        // If the input is not color, reject
                        if (!preg_match('/^#[0-9A-F]{3,8}$/i', $state)) {
                            $set('primary_color', null);

                            Notification::make()
                                ->title('Primary Color Rejected')
                                ->body('Primary Color must be a valid hex color')
                                ->info()
                                ->send();

                            return;
                        }
                    })
                    ->live(),

                ColorPicker::make('secondary_color')
                    ->placeholder('Secondary Color')
                    ->label('Secondary Color')
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        // If the input is not color, reject
                        if (!preg_match('/^#[0-9A-F]{3,8}$/i', $state)) {
                            $set('secondary_color', null);

                            Notification::make()
                                ->title('Secondary Color Rejected')
                                ->body('Secondary Color must be a valid hex color')
                                ->info()
                                ->send();

                            return;
                        }
                    })
                    ->live(),

                ColorPicker::make('text_primary_color')
                    ->placeholder('Text Primary Color')
                    ->label('Text Primary Color')
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        // If the input is not color, reject
                        if (!preg_match('/^#[0-9A-F]{3,8}$/i', $state)) {
                            $set('text_primary_color', null);

                            Notification::make()
                                ->title('Text Priamary Color Rejected')
                                ->body('Text Primary Color must be a valid hex color')
                                ->info()
                                ->send();

                            return;
                        }
                    })
                    ->live(),

                ColorPicker::make('text_secondary_color')
                    ->placeholder('Text Secondary Color')
                    ->label('Text Secondary Color')
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state) {
                        // If the input is not color, reject
                        if (!preg_match('/^#[0-9A-F]{3,8}$/i', $state)) {
                            $set('text_secondary_color', null);

                            Notification::make()
                                ->title('Text Secondary Color Rejected')
                                ->body('Text Secondary Color must be a valid hex color')
                                ->info()
                                ->send();

                            return;
                        }
                    })
                    ->live(),
            ]);
    }

    public function render()
    {
        return view('livewire.color-preview');
    }
}

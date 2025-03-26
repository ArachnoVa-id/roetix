<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\User;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
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
        ], 60);
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
        ], 60);
    }

    public function form(Form $form): Form
    {
        return $form
            ->columns(4)
            ->schema([
                ColorPicker::make('primary_color')
                    ->label('Primary Color')
                    ->live(),

                ColorPicker::make('secondary_color')
                    ->label('Secondary Color')
                    ->live(),

                ColorPicker::make('text_primary_color')
                    ->label('Text Primary Color')
                    ->live(),

                ColorPicker::make('text_secondary_color')
                    ->label('Text Secondary Color')
                    ->live(),
            ]);
    }

    public function render()
    {
        return view('livewire.color-preview');
    }
}

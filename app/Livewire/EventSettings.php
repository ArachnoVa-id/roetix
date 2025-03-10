<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use App\Models\EventVariables;
use Illuminate\Support\Str;

class EventSettings extends Component implements HasForms
{
    use InteractsWithForms;

    public ?bool $is_locked = false;
    public ?bool $is_maintenance = false;
    public ?string $var_title = '';
    public ?string $expected_finish = '';
    public ?string $password = '';

    public function mount()
    {
        // Load existing settings
        $settings = EventVariables::first();
        if ($settings) {
            $this->form->fill([
                'var_title' => $settings->var_title,
                'is_locked' => $settings->is_locked,
                'is_maintenance' => $settings->is_maintenance,
                'expected_finish' => $settings->expected_finish,
                'password' => $settings->password,
            ]);
        }
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('var_title')
                ->label('Title')
                ->required()
                ->maxLength(255),

            Toggle::make('is_locked')
                ->label('Is Locked')
                ->required(),

            Toggle::make('is_maintenance')
                ->label('Is Maintenance Mode')
                ->required(),

            DatePicker::make('expected_finish')
                ->label('Expected Finish')
                ->required(),

            TextInput::make('password')
                ->label('Password')
                ->nullable()
                ->maxLength(255),
        ];
    }

    public function saveSettings()
    {
        $data = $this->form->getState(); // Get validated data

        EventVariables::updateOrCreate(
            ['id' => 1], // Assuming single row for settings
            [
                'event_variables_id' => Str::uuid()->toString(),
                'var_title' => $data['var_title'],
                'is_locked' => $data['is_locked'],
                'is_maintenance' => $data['is_maintenance'],
                'expected_finish' => $data['expected_finish'],
                'password' => $data['password'],
            ]
        );

        Notification::make()
            ->title('Settings Saved')
            ->success()
            ->body('Your settings have been successfully saved.')
            ->send();
    }

    public function render()
    {
        return view('livewire.event-settings');
    }
}

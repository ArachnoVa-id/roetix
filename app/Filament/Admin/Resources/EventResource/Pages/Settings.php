<?php

namespace App\Filament\Admin\Resources\EventResource\Pages;

use App\Filament\Admin\Resources\EventResource;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use App\Models\EventVariables;
use Illuminate\Support\Str;


class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.admin.resources.event-resource.pages.settings';

    protected static ?string $slug = 'Event Setting';
    protected static ?string $navigationLabel = 'Settings';

    public EventVariables $eventVariables;

    public ?bool $is_locked = false;
    public ?bool $is_maintenance = false;
    public ?string $var_title = '';
    public ?string $expected_finish = '';
    public ?string $var_c = '';

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            // Define form fields here
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

            TextInput::make('var_c')
                ->label('Additional Variable')
                ->nullable()
                ->maxLength(255),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        EventVariables::create([
            'event_variables_id' => Str::uuid()->toString(),
            'var_title' => $data['var_title'],
            'is_locked' => $data['is_locked'],
            'is_maintenance' => $data['is_maintenance'],
            'expected_finish' => $data['expected_finish'],
            'var_c' => $data['var_c'],
        ]);

        Notification::make()
            ->title('Settings Saved')
            ->success()
            ->body('Your settings have been successfully saved.')
            ->send();
    }

}
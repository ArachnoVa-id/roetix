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

    // protected static ?string $navigationIcon = 'heroicon-o-qrcode';
    protected static ?string $slug = 'Event Setting';
    protected static ?string $navigationLabel = 'Settings';

    public function mount($record): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Toggle::make('is_locked')
                ->onColor('success')
                ->offColor('danger')
                ->required(),
            Toggle::make('is_maintenance')
                ->onColor('success')
                ->offColor('danger')
                ->required(),
            TextInput::make('Title')
                ->label('Title')
                ->placeholder('Description')
                ->required(),
            DatePicker::make('expected_finish')
                ->label('Expected Finish')
                ->required(),
        ]);
    }

    public function submit()
    {
        $data = $this->form->getState();
    
        // $validatedData = validator($data, [
        //     'is_locked' => 'required|boolean',
        //     'is_maintenance' => 'required|boolean',
        //     'title' => 'required|string|max:255',
        //     'expected_finish' => 'required|date',
        // ])->validate();
    
        // EventVariables::Create(
        //     $validatedData
        // );
    
        // Notification::make()
        //     ->title('Settings Updated')
        //     ->success()
        //     ->body('Your settings have been saved successfully.')
        //     ->send();
        dd($data);
    }
    

    public function getFormActions(): array
    {
        return [
            Action::make('submit')
                ->label('Submit')
                ->submit('submit')
                ->color('primary'),
        ];
    }

}

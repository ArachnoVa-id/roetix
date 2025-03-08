<?php

namespace App\Livewire;

use Livewire\Component;

class EventSettings extends Component
{
    public $someSetting = '';

    public function save()
    {
        // Handle saving logic here
        session()->flash('message', 'Settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.event-settings');
    }
}


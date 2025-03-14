<?php

namespace App\Livewire;

use Livewire\Component;

class EditSeatsModal extends Component
{
    public $layout;
    public $event;
    public $venue;
    public $ticketTypes;

    // Accept values when the component is initialized
    public function mount($layout, $event, $venue, $ticketTypes)
    {
        // dd($layout);
        $this->layout = $layout;
        $this->event = $event;
        $this->venue = $venue;
        $this->ticketTypes = $ticketTypes;
    }

    public function render()
    {
        return view('livewire.edit-seats-modal', [
            'layout' => $this->layout,
            'event' => $this->event,
            'venue' => $this->venue,
            'ticketTypes' => $this->ticketTypes
        ]);
    }
}

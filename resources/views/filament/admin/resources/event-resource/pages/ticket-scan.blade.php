<x-filament::page>

    {{-- form nya --}}
    <form wire:submit.prevent="submit">
        {{ $this->enterTicketCode }}
    </form>

    {{-- table --}}
    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-2">Ticket List</h2>
        {{ $this->table }}
    </div>

    
</x-filament::page>

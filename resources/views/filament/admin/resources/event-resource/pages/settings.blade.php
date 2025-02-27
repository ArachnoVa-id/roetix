<x-filament-panels::page>
    <form wire:submit.prevent="submit" id="settings-form" class="space-y-6 w-full">
        <div>
            {{ $this->form }}
        </div>

        <x-filament::button type="submit" class="mt-4">
            Submit
        </x-filament::button>
    </form>
</x-filament-panels::page>

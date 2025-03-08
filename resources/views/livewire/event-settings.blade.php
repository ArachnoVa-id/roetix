<div class="p-4">
    @if (session()->has('message'))
        <div class="mb-4 text-green-600">
            {{ session('message') }}
        </div>
    @endif

    <label class="block text-sm font-medium text-gray-700">Setting Example</label>
    <input type="text" wire:model="someSetting" class="w-full border rounded-md p-2 mt-1" placeholder="Enter a value">

    <button wire:click="save" class="mt-3 px-4 py-2 bg-blue-500 text-white rounded-md">
        Save
    </button>
</div>

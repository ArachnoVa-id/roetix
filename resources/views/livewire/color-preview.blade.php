<div class="flex flex-col gap-4">
  {{-- Filament Color Picker Form --}}
  <div>
    {{ $this->form }}
  </div>

  {{-- Live Preview --}}
  <div class="py-6 rounded-lg overflow-clip">
    <div class="mx-auto w-full sm:px-6 lg:px-8">
      <div class="overflow-hidden p-6 shadow-xl sm:rounded-lg"
        style="background-color: {{ $primary_color }}; color: {{ $text_primary_color }};">

        {{-- Event Info and Timeline --}}
        <div class="mb-6">
          {{-- Event Title and Info --}}
          <div class="mb-4">
            <h1 class="text-2xl font-bold">Event Name</h1>
            <div class="flex flex-col sm:flex-row sm:items-baseline sm:justify-between">
              <p class="text-lg" style="color: {{ $text_secondary_color }};">Venue: Example Venue</p>
              <p style="color: {{ $text_secondary_color }};">Date: MM/DD/YYYY</p>
            </div>
          </div>

          {{-- Timeline Information --}}
          <div class="mt-4">
            <h3 class="mb-3 text-xl font-semibold">Current Ticket Period</h3>
            <div class="rounded-lg bg-blue-50 p-4 text-blue-800">
              <div class="text-lg font-medium">Early Bird</div>
              <div class="text-blue-600">Start Date - End Date</div>
            </div>
          </div>

          {{-- Legend Section --}}
          <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
            {{-- Ticket Type Legend --}}
            <div class="rounded-lg p-4 shadow"
              style="background-color: {{ $secondary_color }}; color: {{ $text_secondary_color }};">
              <h4 class="mb-2 text-center text-lg font-semibold">Ticket Types</h4>
              <div class="flex flex-wrap items-center justify-center gap-4">
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-red-500"></div>
                  <span class="mt-2 text-sm font-medium">VIP</span>
                </div>
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-green-500"></div>
                  <span class="mt-2 text-sm font-medium">General</span>
                </div>
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-yellow-500"></div>
                  <span class="mt-2 text-sm font-medium">Student</span>
                </div>
              </div>
            </div>

            {{-- Status Legend --}}
            <div class="rounded-lg p-4 shadow"
              style="background-color: {{ $secondary_color }}; color: {{ $text_secondary_color }};">
              <h4 class="mb-2 text-center text-lg font-semibold">Status</h4>
              <div class="flex flex-wrap items-center justify-center gap-4">
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-gray-400"></div>
                  <span class="mt-2 text-sm font-medium">Available</span>
                </div>
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-red-400"></div>
                  <span class="mt-2 text-sm font-medium">Sold Out</span>
                </div>
                <div class="flex flex-col items-center">
                  <div class="h-8 w-8 rounded-full shadow-lg bg-blue-400"></div>
                  <span class="mt-2 text-sm font-medium">Reserved</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- Seat Map Section --}}
        <div class="mt-6">
          <h3 class="mb-4 text-center text-2xl font-bold">Seat Map</h3>
          <div class="rounded-lg border p-4" style="background-color: {{ $secondary_color }};">
            <div class="flex justify-center overflow-x-auto overflow-y-hidden">
              <div class="p-6 bg-gray-300 rounded-lg shadow-lg">[ Seat Map Placeholder ]</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<div class="flex flex-col gap-4">
  {{-- Filament Color Picker Form --}}
  <div>
    {{ $this->form }}
  </div>

  {{-- Live Preview --}}
  <div class="flex flex-col gap-3">
    <div>
      <h2>Color Preview</h2>
    </div>

    <div class="mx-auto w-full max-w-7xl sm:px-6 lg:px-8">
      <div class="flex flex-col overflow-hidden rounded-lg p-6 shadow-xl gap-4"
        style="background-color: {{ $primary_color }}; color: {{ $text_primary_color }};">
        <div class="mb-6 flex w-full flex-col gap-4 md:flex-row">
          <div
            class="flex w-full flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-3 shadow-lg md:w-[35%]"
            style="background-color: {{ $secondary_color }}; borderRight: `4px solid {{ $primary_color }}}`;">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-bold" style="color: {{ $text_primary_color }},">
                Event Name
              </h2>

              <div class="w-fit">
                <div class="flex w-full items-center justify-center rounded-lg bg-opacity-50 px-2"
                  style="background-color: rgba(34, 197, 94, 0.1)">
                  <div class="h-2 w-2 rounded-full bg-green-500 mr-2 animate-pulse"></div>
                  <span class="text-sm font-medium" style="color: #16a34a;">
                    Active
                  </span>
                </div>
              </div>
            </div>
            <div class="flex grow items-start">
              <div class="flex w-full flex-col items-stretch gap-4">
                <div class="flex w-full justify-between gap-1 text-sm">
                  <div class="flex gap-2 text-xs">
                    <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }}">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <div class="flex flex-col">
                      <p class="font-bold" style="color: {{ $text_secondary_color }};">
                        Venue
                      </p>
                      <p style="color: {{ $text_secondary_color }};">
                        Venue Name
                      </p>
                    </div>
                  </div>
                  <div class="flex justify-end text-end gap-2 text-xs">
                    <div class="flex flex-col">
                      <p class="font-bold" style="color: {{ $text_secondary_color }};">
                        D-Day
                      </p>
                      <p style="color: {{ $text_secondary_color }};">
                        Thu, 27 February 2025 at 23:59 WIB
                      </p>
                    </div>
                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                </div>
                <div class="w-full rounded-lg p-1 px-3" style="background-color: rgba(59, 130, 246, 0.1);">
                  <div class="flex items-center justify-end text-sm font-semibold text-blue-600 gap-2">
                    <p>
                      Current Timeline
                    </p>
                    <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="flex w-full items-center justify-end text-xs text-blue-500 text-end"
                    style="color: {{ $text_secondary_color }};">
                    Thu, 27 February 2025 at 23:59 WIB - Thu, 27 February 2025 at 23:59 WIB
                  </div>
                </div>
              </div>
            </div>

            <hr class="border-[1.5px]" style="borderColor: {{ $text_primary_color }};" />
            <div class="flex justify-between">
              <div class="flex items-center gap-2">
                <svg class="-mb-1 -mt-1 ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                </svg>

                <p class="text-xs leading-[1.1]" style="color: {{ $text_secondary_color }};">
                  Available for booking
                </p>
              </div>

              <div class="flex items-center gap-2">
                <p class="text-right text-xs leading-[1.1]" style="color: {{ $text_secondary_color }};">
                  You can select up to 5 seats
                </p>

                <svg class="-mb-1 -mt-1 ml-2 mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                  xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>

          <div
            class="flex w-full flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-3 shadow-lg md:w-[65%]"
            style="background-color: {{ $secondary_color }}; borderRight: `4px solid {{ $primary_color }}`;">
            <h3 class="text-center text-lg font-semibold" style="color: {{ $text_primary_color }};">
              Category & Price
            </h3>
            <div class="h-full">
              <div class="flex flex-wrap gap-2 h-fit">
                <div class="flex w-full rounded-lg p-3 shadow-sm gap-2" style="background-color: #FFFFFF11;">
                  <div class="mr-2 h-4 w-4 rounded-full" style="background-color: #000;"></div>
                  <div class="flex w-full flex-col gap-1">
                    <span class="text-xs font-medium leading-[.8]" style="color: {{ $text_secondary_color }};">
                      Normal
                    </span>
                    <div class="text-xs font-bold leading-[.8]" style="color: {{ $text_primary_color }};">
                      Rp 200.000,00
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <hr class="border-[1.5px]" style="borderColor: {{ $text_primary_color }};" />
            <div class="flex w-full items-center justify-center gap-4">
              <p class="text-xs leading-[.8]">
                Border Color:
              </p>
              <div class="flex items-center">
                <div class="h-3 w-3 mr-1.5 rounded-full"></div>
                <span class="text-xs leading-[.8]" style="color: {{ $text_secondary_color }};">
                  X X X
                </span>
              </div>
            </div>
          </div>
        </div>
        <div class="flex flex-col items-center justify-center gap-2 rounded-lg p-3"
          style="background-color: {{ $secondary_color }}; height: 80vh;">
          <div class="relative flex w-full items-center justify-center">
            <h3 class="h-fit text-center text-lg font-bold">
              Seat Map
            </h3>
          </div>
          <div class="flex h-[92%] w-full overflow-clip">

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

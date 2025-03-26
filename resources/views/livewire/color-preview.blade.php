<div class="flex flex-col gap-4">
  {{-- Filament Color Picker Form --}}
  <div>
    {{ $this->form }}
  </div>

  {{-- Live Preview --}}
  <div class="flex flex-col gap-2">
    <div>
      <h2>Color Preview</h2>
    </div>

    <div class="overflow-hidden p-6 shadow-xl rounded-lg"
      style="background-color: {{ $primary_color }}; color: {{ $text_primary_color }};">
      {{-- {/* Three-column grid for A, B, C sections */} --}}
      <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- {/* Column A: Event Info */} --}}
        <div class="flex flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-2 shadow-lg"
          style="background-color: {{ $secondary_color }}; border-right: 4px solid {{ $primary_color }};">
          <h2 class="text-md font-bold" style="color: {{ $text_primary_color }};">
            Event Name
          </h2>
          <div class="">
            <div class="flex items-center gap-3">
              <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <p class="text-xs" style="color: {{ $text_secondary_color }};">
                Example Venue
              </p>
            </div>
            <div class="flex items-center gap-3">
              <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="text-xs" style="color: {{ $text_secondary_color }};">
                MM/DD/YYYY
              </p>
            </div>
          </div>

          {{-- {/* Additional content for section A */} --}}
          <hr style="border-color: {{ $text_primary_color }}; border-width: 0.5px; border-style: solid;" />
          <div class="">
            <div class="flex items-center gap-3">
              <svg class="ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
              </svg>

              <p class="mt-[1px] text-xs leading-[.8]" style="color: {{ $text_secondary_color }};">
                Tickets are available for booking
              </p>
            </div>

            <div class="flex items-center gap-3">
              <svg class="ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg" style="color: {{ $text_secondary_color }};">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                  d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
              </svg>

              <p class="mt-[1px] text-xs leading-[.8]" style="color: {{ $text_secondary_color }};">
                You can select up to 5 tickets
              </p>
            </div>
          </div>
          </>
        </div>

        {{-- {/* Column B: Timeline and Status */} --}}
        <div class="flex flex-col justify-between gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-2 shadow-lg"
          style="background-color: {{ $secondary_color }}; border-right: 4px solid {{ $primary_color }};">
          <div class="flex flex-col gap-3">
            <div class="relative flex w-full items-center justify-start">
              <h3 class="w-full text-center text-md font-semibold" style="color: {{ $text_primary_color }};">
                Event Status
              </h3>
              {{-- {/* Status section */} --}}
              <div class="absolute left-0 top-0 w-fit">
                <div class="flex w-full items-center justify-center rounded-lg bg-opacity-50 px-2"
                  style="background-color: rgba(34, 197, 94, 0.1);">
                  <div class="h-2 w-2 rounded-full mr-2 animate-pulse" style="background-color:rgba(34, 197, 94)">
                  </div>
                  <span class="text-xs font-medium" style="color: #16a34a;">
                    Active
                  </span>
                </div>
              </div>
            </div>
            {{-- {/* Timeline section */} --}}

            <div class="w-full">
              <div class="rounded-lg p-1" style="background-color: rgba(59, 130, 246, 0.1);">
                <div class="text-center font-semibold text-blue-600 text-xs">
                  Current Timeline
                </div>
                <div class="flex items-center justify-center text-xs text-blue-500 gap-3">
                  <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  MM/DD/YYYY - MM/DD/YYYY
                </div>
              </div>
            </div>
          </div>
          <div>
            {{-- {/* Add the status legends */} --}}
            <div class="flex w-full items-center justify-center gap-4 p-2">
              <div class="flex items-center gap-3">
                <div class="h-3 w-3 mr-1.5 rounded-full" style="background-color: rgb(17, 180, 74);"></div>
                <span class="text-xs leading-[.8]" style="color: {{ $text_secondary_color }};">
                  Available
                </span>
              </div>
            </div>
          </div>
        </div>

        {{-- {/* Column C: Ticket Categories */} --}}
        <div class="flex flex-col justify-start gap-3 overflow-hidden rounded-xl bg-gradient-to-br p-2 shadow-lg"
          style="background-color: {{ $secondary_color }}; border-right: 4px solid {{ $primary_color }};">
          {{-- {/* Ticket Categories with Prices */} --}}
          <h3 class="text-center text-md font-semibold" style="color: {{ $text_primary_color }};">
            Category & Price
          </h3>
          <div class="grid grid-cols-2 gap-3">

            <div key={type} class="flex rounded-lg p-2 shadow-sm gap-3"
              style="background-color: rgba(255, 255, 255, 0.2); border-left: 3px solid #16a34a;">
              <div class="h-3 w-3 rounded-full" style="background-color: #16a34a;"></div>
              <div class="flex w-full flex-col gap-1">
                <span class="text-xs font-medium leading-[.8]" style="color: {{ $text_secondary_color }};">
                  VIP
                </span>
                <div class="text-xs font-bold leading-[.8]" style="color: {{ $text_secondary_color }};">
                  {{-- {formatRupiah(price)} --}}
                  Rp. 100.000
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- {/* Seat Map Section - takes up more vertical space */} --}}
      <div class="mt-2">
        <h3 class="mb-2 text-center text-md font-bold">
          Seat Map
        </h3>
        <div class="rounded-lg border p-2" style="background-color: {{ $secondary_color }}; height: 100px;">
          <div class="flex h-full justify-center overflow-auto">
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

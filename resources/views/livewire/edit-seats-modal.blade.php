<div>
  <!-- React Component Mount Point -->
  <div id="seat-map-editor" data-layout="{{ json_encode($layout) }}" data-event="{{ json_encode($event) }}"
    data-venue="{{ json_encode($venue) }}" data-tickettypes="{{ json_encode($ticketTypes) }}">
  </div>
</div>

@viteReactRefresh
@vite(['resources/js/app.tsx'])

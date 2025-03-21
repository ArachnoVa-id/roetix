{{-- resources/views/errors.blade.php --}}
<div>
  <div id="error-page" data-code="{{ $exception->getStatusCode() ?? 500 }}">
  </div>
</div>

@viteReactRefresh
@vite(['resources/js/app.tsx'])

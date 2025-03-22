{{-- resources/views/errors.blade.php --}}
<div>
  <div id="error-page" data-code="{{ $exception->getStatusCode() ?? 500 }}"
    data-message="{{ $exception->getMessage() ?? '' }}" data-headers="{{ json_encode($exception->getHeaders()) }}">
  </div>
</div>

@viteReactRefresh
@vite(['resources/js/app.tsx'])

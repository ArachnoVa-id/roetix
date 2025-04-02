<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  {{-- Set the page title dynamically based on the error code or message --}}
  <title>
    @if (isset($exception))
      Error {{ $exception->getStatusCode() }} - NovaTix
    @else
      Error - NovaTix
    @endif
  </title>

  {{-- Set the favicon --}}
  <link rel="icon" href="{{ asset('images/novatix-logo/favicon.ico') }}" type="image/x-icon">
  <link rel="shortcut icon" href="{{ asset('images/novatix-logo/favicon.ico') }}" type="image/x-icon">

  @viteReactRefresh
  @vite(['resources/js/app.tsx'])
</head>

<body>
  <div>
    <div id="error-page" data-code="{{ $exception->getStatusCode() ?? 500 }}"
      data-message="{{ $exception->getMessage() ?? '' }}" data-headers="{{ json_encode($exception->getHeaders()) }}">
    </div>
  </div>
</body>

</html>

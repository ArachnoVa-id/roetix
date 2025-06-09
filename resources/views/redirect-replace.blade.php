<!DOCTYPE html>
<html>

<head>
  <title>Redirecting...</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>

<body>
  <div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
    <h3>Processing your request...</h3>
    <p>Please wait while we redirect you.</p>
  </div>
  <script>
    // Clear the entire history and redirect
    if (window.history && window.history.pushState) {
      // Clear history by replacing with a clean state
      window.history.replaceState(null, null, '{{ $redirectUrl }}');

      // Add another entry to prevent back navigation
      window.history.pushState(null, null, '{{ $redirectUrl }}');

      // Redirect to the final destination
      window.location.href = '{{ $redirectUrl }}';
    } else {
      // Fallback for older browsers
      window.location.replace('{{ $redirectUrl }}');
    }

    // Prevent back button functionality
    window.addEventListener('popstate', function(event) {
      window.history.pushState(null, null, '{{ $redirectUrl }}');
      window.location.href = '{{ $redirectUrl }}';
    });
  </script>
</body>

</html>

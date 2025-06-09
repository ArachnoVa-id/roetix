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
        // Replace the current history entry instead of adding a new one
        window.location.replace('{{ $redirectUrl }}');
    </script>
</body>
</html>
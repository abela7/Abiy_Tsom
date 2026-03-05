<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ __('app.redirecting') }}</title>

    {{-- Universal fallback: fires even when JS is present but silently fails
         (common in WhatsApp's in-app browser). The 1s delay gives the browser
         time to commit Set-Cookie headers before navigating. JS below fires
         faster (100ms) when it works. --}}
    <meta http-equiv="refresh" content="1;url={{ $redirectUrl }}">
</head>
<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0d1117;color:#e6edf3;">
    <p style="text-align:center;">
        {{ __('app.redirecting') }}...<br>
        <a href="{{ $redirectUrl }}" style="color:#58a6ff;font-size:0.9em;">Click here</a>
    </p>

    <script>
        // Small delay ensures the browser has fully processed Set-Cookie headers
        // from this 200 response before navigating to the cookie-protected page.
        setTimeout(function () {
            window.location.replace(@json($redirectUrl));
        }, 100);
    </script>
</body>
</html>

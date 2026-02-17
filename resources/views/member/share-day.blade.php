<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ogTitle }} - {{ __('app.app_name') }}</title>
    <meta name="description" content="{{ $ogDescription }}">

    {{-- No <meta refresh> — JS handles redirect so we can restore the member token first --}}

    @php
        $storedOgImage = seo('og_image');
        $ogImageUrl = $storedOgImage
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($storedOgImage)
            : asset('images/og-cover.png');

        $ogImageUrl = str_starts_with($ogImageUrl, 'http')
            ? $ogImageUrl
            : (config('app.url')
                ? rtrim(config('app.url'), '/') . '/' . ltrim($ogImageUrl, '/')
                : $ogImageUrl);
    @endphp

    {{-- Open Graph (Facebook, WhatsApp, Telegram, etc.) --}}
    <meta property="og:type" content="article">
    <meta property="og:site_name" content="{{ seo('site_title_' . app()->getLocale(), __('app.app_name')) }}">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ app()->getLocale() === 'am' ? 'am_ET' : 'en_US' }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    {{--
        Noscript fallback: if JS is disabled, use meta-refresh.
        Social crawlers ignore JS and will read OG tags above.
    --}}
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ $memberUrl }}">
    </noscript>
</head>
<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0d1117;color:#e6edf3;">
    <p>{{ __('app.redirecting') }}… <a href="{{ $memberUrl }}" id="fallback-link" style="color:#58a6ff;">{{ $ogTitle }}</a></p>

    <script>
        (function() {
            var memberUrl = @js($memberUrl);
            var homeUrl = @js(url('/'));

            // Restore member_token cookie from localStorage (mirrors layout script)
            var token = null;
            try { token = localStorage.getItem('member_token'); } catch(_e) {}

            if (token) {
                // Ensure cookie is set so middleware recognises the member
                document.cookie = 'member_token=' + token + ';path=/;SameSite=Lax';
                window.location.replace(memberUrl);
            } else {
                // Not registered on this device — send to welcome/onboarding
                window.location.replace(homeUrl);
            }
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ seo('site_title_' . app()->getLocale(), __('app.app_name')) }}</title>
    <meta name="description" content="{{ seo('site_description_' . app()->getLocale(), __('app.tagline', [], app()->getLocale())) }}">
    <meta name="robots" content="noindex,nofollow">

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

        $appName = seo('site_title_' . app()->getLocale(), __('app.app_name'));
        $description = seo('site_description_' . app()->getLocale(), __('app.tagline', [], app()->getLocale()));
    @endphp

    {{-- Open Graph — WhatsApp preview bot reads these; JS below handles the actual redirect --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $appName }}">
    <meta property="og:title" content="{{ $appName }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ app()->getLocale() === 'am' ? 'am_ET' : 'en_US' }}">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $appName }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    {{-- Fallback for no-JS environments --}}
    <noscript>
        <meta http-equiv="refresh" content="0;url={{ route('home') }}">
    </noscript>
</head>
<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0d1117;color:#e6edf3;">
    <p style="text-align:center;">
        {{ __('app.redirecting') }}...<br>
        <a href="{{ route('home') }}" id="fallback-link" style="color:#58a6ff;font-size:0.9em;">Click here</a>
    </p>

    <script>
        (function () {
            var code = new URLSearchParams(window.location.search).get('code');

            if (code && /^[A-Za-z0-9]{20,128}$/.test(code)) {
                window.location.replace('/auth/access?code=' + encodeURIComponent(code));
                return;
            }

            // No valid code — just go home
            window.location.replace('/');
        })();
    </script>
</body>
</html>

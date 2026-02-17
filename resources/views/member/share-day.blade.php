<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ogTitle }} - {{ __('app.app_name') }}</title>
    <meta name="description" content="{{ $ogDescription }}">

    {{-- Redirect human visitors to the member day page after 0s --}}
    <meta http-equiv="refresh" content="0;url={{ $memberUrl }}">

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
</head>
<body style="font-family:system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#0d1117;color:#e6edf3;">
    <p>{{ __('app.redirecting') }}… <a href="{{ $memberUrl }}" style="color:#58a6ff;">{{ $ogTitle }}</a></p>
</body>
</html>

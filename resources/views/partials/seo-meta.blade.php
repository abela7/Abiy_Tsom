{{-- SEO meta tags and Open Graph for friendly sharing --}}
@php
    $ogImageUrl = $ogImage ?? asset('images/login-quote.jpg');
    $ogImageUrl = str_starts_with($ogImageUrl, 'http') ? $ogImageUrl : (config('app.url') ? rtrim(config('app.url'), '/') . '/' . ltrim($ogImageUrl, '/') : $ogImageUrl);
@endphp
<meta name="description" content="{{ __('app.meta_description') }}">

{{-- Open Graph (Facebook, WhatsApp, etc.) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ __('app.app_name') }}">
<meta property="og:title" content="{{ $ogTitle ?? __('app.og_title') }}">
<meta property="og:description" content="{{ $ogDescription ?? __('app.og_description') }}">
<meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'am' ? 'am_ET' : 'en_US' }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitle ?? __('app.og_title') }}">
<meta name="twitter:description" content="{{ $ogDescription ?? __('app.og_description') }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

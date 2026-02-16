{{-- SEO meta tags and Open Graph for friendly sharing --}}
@php
    $locale = app()->getLocale() === 'am' ? 'am' : 'en';

    $siteName = seo("site_title_{$locale}", __('app.app_name')) ?? __('app.app_name');
    $metaDescription = seo("meta_description_{$locale}", __('app.meta_description')) ?? __('app.meta_description');
    $defaultOgTitle = seo("og_title_{$locale}", __('app.og_title')) ?? __('app.og_title');
    $defaultOgDescription = seo("og_description_{$locale}", __('app.og_description')) ?? __('app.og_description');
    $robots = seo('robots', 'index,follow,max-image-preview:large') ?? 'index,follow,max-image-preview:large';
    $twitterCard = seo('twitter_card', 'summary_large_image') ?? 'summary_large_image';

    $storedOgImage = seo('og_image');
    $ogImageUrl = ($ogImage ?? null)
        ?? ($storedOgImage
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($storedOgImage)
            : asset('images/og-cover.png'));

    $ogImageUrl = str_starts_with($ogImageUrl, 'http')
        ? $ogImageUrl
        : (config('app.url')
            ? rtrim(config('app.url'), '/') . '/' . ltrim($ogImageUrl, '/')
            : $ogImageUrl);
@endphp
<meta name="description" content="{{ $metaDescription }}">
<meta name="robots" content="{{ $robots }}">

{{-- Open Graph (Facebook, WhatsApp, etc.) --}}
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $ogTitle ?? $defaultOgTitle }}">
<meta property="og:description" content="{{ $ogDescription ?? $defaultOgDescription }}">
<meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'am' ? 'am_ET' : 'en_US' }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ $ogTitle ?? $defaultOgTitle }}">
<meta name="twitter:description" content="{{ $ogDescription ?? $defaultOgDescription }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

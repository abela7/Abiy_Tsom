<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    @include('partials.favicon')
    @php
        $ogTitle = $ogTitle ?? __('app.og_title');
        $ogDescription = $ogDescription ?? __('app.og_description');
        $ogUrl = $ogUrl ?? url()->current();
    @endphp
    @include('partials.seo-meta')
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh min-h-screen font-sans antialiased text-primary overscroll-y-none overflow-x-hidden bg-[#0f0a1a]">
    @yield('content')
    @stack('scripts')
</body>
</html>

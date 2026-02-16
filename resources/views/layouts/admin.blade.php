<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.favicon')
    <title>@yield('title', __('app.admin')) - {{ __('app.app_name') }}</title>
    <script>
        (function(){var t=localStorage.getItem('admin_theme');if(t!=='light')document.documentElement.classList.add('dark');})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-primary font-sans"
      x-data="{
        sidebarOpen: false,
        desktopSidebar: localStorage.getItem('admin_sidebar') !== 'closed',
        darkMode: localStorage.getItem('admin_theme') !== 'light',
        locale: '{{ app()->getLocale() }}',
        toggleDesktopSidebar() {
          this.desktopSidebar = !this.desktopSidebar;
          localStorage.setItem('admin_sidebar', this.desktopSidebar ? 'open' : 'closed');
        },
        setLocale(lang) {
          this.locale = lang;
          const url = new URL(window.location.href);
          url.searchParams.set('lang', lang);
          window.location.href = url.toString();
        }
      }"
      x-effect="document.documentElement.classList.toggle('dark', darkMode)">
    @php
        $currentAdmin = auth()->user();
        $adminHomeUrl = $currentAdmin?->isWriter() ? route('admin.daily.index') : route('admin.dashboard');
    @endphp

    {{-- Top bar --}}
    <header class="bg-accent text-on-accent shadow-lg sticky top-0 z-50">
        <div class="flex items-center justify-between px-4 h-14">
            <div class="flex items-center gap-3">
                {{-- Mobile menu toggle --}}
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1" aria-label="{{ __('app.toggle_menu') }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                {{-- Desktop sidebar toggle --}}
                <button type="button" @click="toggleDesktopSidebar()" class="hidden lg:flex p-1 rounded-lg hover:bg-accent-overlay transition" aria-label="{{ __('app.toggle_menu') }}">
                    <svg x-show="desktopSidebar" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <svg x-show="!desktopSidebar" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="{{ $adminHomeUrl }}" class="font-bold text-lg">{{ __('app.app_name') }} <span class="text-accent-secondary">{{ __('app.admin') }}</span></a>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="darkMode = !darkMode; localStorage.setItem('admin_theme', darkMode ? 'dark' : 'light')"
                        class="p-1.5 rounded-lg hover:bg-accent-overlay transition" aria-label="{{ __('app.toggle_theme') }}"
                        title="{{ __('app.theme') }}">
                    <svg x-show="!darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button"
                            @click="open = !open"
                            class="p-1.5 rounded-lg hover:bg-accent-overlay transition"
                            aria-label="{{ __('app.language') }}">
                        <svg class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-44 bg-card border border-border rounded-xl shadow-2xl overflow-hidden"
                         style="display: none; z-index: 9999;">
                        <button @click="setLocale('en'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center justify-between"
                                :class="locale === 'en' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                            <span>English</span>
                            <svg x-show="locale === 'en'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        <button @click="setLocale('am'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center justify-between"
                                :class="locale === 'am' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                            <span>አማርኛ</span>
                            <svg x-show="locale === 'am'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <span class="text-sm opacity-80 hidden sm:inline ml-2">{{ auth()->user()?->name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-sm bg-accent-overlay hover:bg-accent-overlay-hover px-3 py-1.5 rounded transition">{{ __('app.logout') }}</button>
                </form>
            </div>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-3.5rem)]">
        @php
            $links = [
                ['route' => 'admin.dashboard', 'label' => __('app.dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', 'roles' => ['admin', 'editor']],
                ['route' => 'admin.members.index', 'label' => __('app.members'), 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z', 'roles' => ['admin', 'editor']],
                ['route' => 'admin.admins.index', 'label' => __('app.manage_admins'), 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'super_admin' => true],
                ['route' => 'admin.seasons.index', 'label' => __('app.seasons'), 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z', 'roles' => ['admin', 'editor']],
                ['route' => 'admin.themes.index', 'label' => __('app.themes'), 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'roles' => ['admin', 'editor']],
                ['route' => 'admin.daily.index', 'label' => __('app.daily_content'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'roles' => ['admin', 'editor', 'writer']],
                ['route' => 'admin.announcements.index', 'label' => __('app.announcements'), 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3.14a7.5 7.5 0 011.294 12.169A5.75 5.75 0 0112 18.5a5.75 5.75 0 01-6.564-4.817z', 'roles' => ['admin', 'editor', 'writer']],
                ['route' => 'admin.activities.index', 'label' => __('app.activities'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'roles' => ['admin', 'editor', 'writer']],
                ['route' => 'admin.translations.index', 'label' => __('app.translations'), 'icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129', 'roles' => ['admin', 'editor']],
            ];
        @endphp

        {{-- Mobile sidebar (overlay, < lg) --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-card shadow-xl transform transition-transform duration-200 border-r border-border pt-14 lg:hidden"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="py-4 h-full overflow-y-auto">
                <nav class="space-y-1 px-3">
                    @foreach ($links as $link)
                        @if(!empty($link['super_admin']) && !$currentAdmin?->isSuperAdmin()) @continue @endif
                        @if(!empty($link['roles']) && !$currentAdmin?->isSuperAdmin() && !in_array($currentAdmin?->role, $link['roles'], true)) @continue @endif
                        <a href="{{ route($link['route']) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs($link['route'] . '*') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" @click="sidebarOpen = false"
             class="fixed inset-0 bg-overlay z-30 lg:hidden" x-transition.opacity></div>

        {{-- Desktop sidebar (fixed, >= lg) --}}
        <aside class="hidden lg:block fixed top-14 left-0 h-[calc(100vh-3.5rem)] bg-card border-r border-border z-30 transition-all duration-300 ease-in-out overflow-hidden"
               :style="desktopSidebar ? 'width: 16rem' : 'width: 0; border-right-width: 0'">
            <div class="w-64 py-4 h-full overflow-y-auto">
                <nav class="space-y-1 px-3">
                    @foreach ($links as $link)
                        @if(!empty($link['super_admin']) && !$currentAdmin?->isSuperAdmin()) @continue @endif
                        @if(!empty($link['roles']) && !$currentAdmin?->isSuperAdmin() && !in_array($currentAdmin?->role, $link['roles'], true)) @continue @endif
                        <a href="{{ route($link['route']) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition whitespace-nowrap {{ request()->routeIs($link['route'] . '*') ? 'bg-accent text-on-accent' : 'text-secondary hover:bg-muted' }}">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $link['icon'] }}"/></svg>
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="flex-1 p-4 lg:p-6 min-w-0 overflow-x-hidden transition-all duration-300 ease-in-out"
              :class="desktopSidebar ? 'lg:ml-64' : 'lg:ml-0'">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="mb-4 p-3 bg-success-bg border border-success text-success rounded-lg text-sm"
                     x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-3 bg-error-bg border border-error text-error rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 bg-error-bg border border-error text-error rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</body>
</html>

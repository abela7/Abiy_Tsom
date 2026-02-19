<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      x-data="{
        darkMode: localStorage.getItem('theme') !== 'light',
        locale: '{{ app()->getLocale() }}',
        toggleTheme() {
          this.darkMode = !this.darkMode;
          const theme = this.darkMode ? 'dark' : 'light';
          localStorage.setItem('theme', theme);
          if (window.AbiyTsom?.api) { AbiyTsom.api('/api/member/settings', { theme }); }
        },
        setLocale(lang) {
          this.locale = lang;
          if (window.AbiyTsom?.api) { 
            AbiyTsom.api('/api/member/settings', { locale: lang }).then(() => {
              window.location.reload();
            });
          } else {
            window.location.reload();
          }
        }
      }"
      :class="{ 'dark': darkMode }"
      x-init="if (!localStorage.getItem('theme')) { localStorage.setItem('theme', 'dark'); darkMode = true; }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    <script>
        (function(){var t=localStorage.getItem('theme');if(t!=='light')document.documentElement.classList.add('dark');})();
    </script>
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    @include('partials.favicon')
    @php
        $ogTitle = $ogTitle ?? (trim($__env->yieldContent('og_title')) ?: null);
        $ogDescription = $ogDescription ?? (trim($__env->yieldContent('og_description')) ?: null);
    @endphp
    @include('partials.seo-meta')
    <title>@yield('title', __('app.app_name'))</title>

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-surface text-primary font-sans">

    {{-- Top nav: Welcome + theme toggle (member pages only) --}}
    @if(isset($currentMember) && request()->routeIs('member.*'))
    <header class="sticky top-0 z-40 bg-card border-b border-border safe-area-top overflow-visible">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between overflow-visible">
            @php $baptismName = trim((string) ($currentMember->baptism_name ?? '')); @endphp
            <h1 class="flex-1 min-w-0 pr-2 text-sm sm:text-base lg:text-lg font-bold text-primary leading-tight break-words">
                @if(app()->getLocale() === 'am')
                    @if($baptismName !== '')
                        <span class="text-accent">{{ $baptismName }}</span>
                        <span> እንኳን ደህና መጡ</span>
                    @else
                        <span>እንኳን ደህና መጡ</span>
                    @endif
                @else
                    <span>Welcome</span>
                    @if($baptismName !== '')
                        <span> </span><span class="text-accent">{{ $baptismName }}</span>
                    @endif
                @endif
            </h1>
            <div class="flex items-center gap-1 shrink-0">
                @if($currentMember->passcode_enabled ?? false)
                <a href="{{ route('member.passcode.lock') }}?token={{ e($currentMember->token) }}"
                   class="p-2 rounded-xl hover:bg-muted transition active:scale-95"
                   aria-label="{{ __('app.lock_app') }}">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </a>
                @endif
                <div class="relative overflow-visible" x-data="{ open: false }" @click.away="open = false">
                    <button type="button"
                            @click="open = !open"
                            class="p-2 rounded-xl hover:bg-muted transition active:scale-95 touch-manipulation"
                            :aria-label="'{{ __('app.language') }}'">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                         class="fixed right-2 mt-2 w-44 bg-card border border-border rounded-xl shadow-2xl overflow-hidden"
                         style="display: none; z-index: 9999; top: 52px;">
                        <button @click="setLocale('en'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center justify-between touch-manipulation"
                                :class="locale === 'en' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                            <span>English</span>
                            <svg x-show="locale === 'en'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        <button @click="setLocale('am'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center justify-between touch-manipulation"
                                :class="locale === 'am' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                            <span>አማርኛ</span>
                            <svg x-show="locale === 'am'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button"
                        @click="toggleTheme()"
                        class="p-2 rounded-xl hover:bg-muted transition active:scale-95"
                        :aria-label="darkMode ? '{{ __('app.theme_light') }}' : '{{ __('app.theme_dark') }}'">
                    {{-- Sun: show when dark (click to switch to light) --}}
                    <svg x-show="darkMode" x-cloak class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{-- Moon: show when light (click to switch to dark) --}}
                    <svg x-show="!darkMode" class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>
    @endif

    {{-- Main content area with bottom nav padding --}}
    <main class="pb-20 max-w-lg mx-auto">
        @yield('content')
    </main>

    {{-- Mobile bottom navigation (show when member is identified, even if admin is also logged in) --}}
    @if(isset($currentMember) && request()->routeIs('member.*'))
        @php $navToken = '?token=' . e($currentMember->token); @endphp
        <nav class="fixed bottom-0 inset-x-0 bg-card border-t border-border z-50 safe-area-bottom">
            <div class="max-w-lg mx-auto flex justify-around items-center h-16">
                <a href="{{ route('member.home') }}{{ $navToken }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.home') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>{{ __('app.nav_home') }}</span>
                </a>
                <a href="{{ route('member.calendar') }}{{ $navToken }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.calendar') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ __('app.nav_calendar') }}</span>
                </a>
                <a href="{{ route('member.progress') }}{{ $navToken }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.progress') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('app.nav_progress') }}</span>
                </a>
                <a href="{{ route('member.settings') }}{{ $navToken }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.settings') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ __('app.nav_settings') }}</span>
                </a>
            </div>
        </nav>
    @endif

    {{-- Member token management --}}
    <script>
        (function() {
            var token = localStorage.getItem('member_token');
            var fromUrl = new URLSearchParams(window.location.search).get('token');
            if (fromUrl) {
                token = fromUrl;
                localStorage.setItem('member_token', fromUrl);
            }
            if (token) {
                document.cookie = 'member_token=' + token + ';path=/;SameSite=Lax';
            }
        })();
        window.AbiyTsom = {
            get token() { return localStorage.getItem('member_token'); },
            set token(v) { if (v) localStorage.setItem('member_token', v); },
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            baseUrl: '{{ url('/') }}',

            async api(url, data = {}) {
                data.token = this.token;
                const response = await fetch(this.baseUrl + url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Member-Token': this.token || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                return response.json();
            },

            async get(url) {
                const separator = url.includes('?') ? '&' : '?';
                const tokenParam = this.token ? separator + 'token=' + encodeURIComponent(this.token) : '';
                const response = await fetch(this.baseUrl + url + tokenParam, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Member-Token': this.token || '',
                    },
                });
                return response.json();
            }
        };
    </script>
    @stack('scripts')
</body>
</html>

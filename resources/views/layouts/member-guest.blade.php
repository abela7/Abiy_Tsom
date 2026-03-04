<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
        theme: (function(){var t=localStorage.getItem('theme');return (t==='light'||t==='sepia'||t==='dark')?t:'light';})(),
        locale: '{{ app()->getLocale() }}',
        applyThemeClasses() {
          document.documentElement.classList.toggle('dark', this.theme === 'dark');
          document.documentElement.classList.toggle('theme-sepia', this.theme === 'sepia');
        },
        toggleTheme() {
          const order = ['light', 'sepia', 'dark'];
          const i = order.indexOf(this.theme);
          this.theme = order[(i + 1) % 3];
          localStorage.setItem('theme', this.theme);
          this.applyThemeClasses();
        },
        setLocale(lang) {
          this.locale = lang;
          document.dispatchEvent(new CustomEvent('locale-switching', { detail: { lang } }));
          const url = new URL(window.location.href);
          url.searchParams.set('lang', lang);
          window.location.href = url.toString();
        }
      }"
      :class="{ 'dark': theme === 'dark', 'theme-sepia': theme === 'sepia' }"
      x-init="
        if ({{ request()->routeIs('volunteer.invite.*') ? 'true' : 'false' }}) { theme = 'dark'; localStorage.setItem('theme', 'dark'); }
        else if (!localStorage.getItem('theme')) { localStorage.setItem('theme', 'light'); theme = 'light'; }
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.classList.toggle('theme-sepia', theme === 'sepia');
      ">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    @include('partials.favicon')
    @include('partials.seo-meta')
    <title>@yield('title', __('app.app_name'))</title>
    <script>
        (function(){
            var forceInviteDark = @json(request()->routeIs('volunteer.invite.*'));
            var t = localStorage.getItem('theme');
            if (forceInviteDark) document.documentElement.classList.add('dark');
            else if (t === 'dark') document.documentElement.classList.add('dark');
            else if (t === 'sepia') document.documentElement.classList.add('theme-sepia');
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if(config('services.google.analytics_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ config('services.google.analytics_id') }}',{anonymize_ip:true,allow_google_signals:false,allow_ad_personalization_signals:false});</script>
    @endif
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="min-h-screen flex flex-col justify-center">
        <div class="flex flex-col justify-center p-4 sm:p-6 lg:p-12 relative min-h-[100dvh] lg:min-h-0 bg-surface">
            {{-- Mobile: background image with soft gradient overlay --}}
            @if(!request()->routeIs('volunteer.invite.*'))
                <div class="fixed inset-0 -z-10 lg:hidden">
                    <img src="{{ asset('images/og-cover.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                    <div class="absolute inset-0 bg-gradient-to-b from-surface/70 via-surface/50 to-surface/90"></div>
                </div>
            @endif

            <div class="relative w-full max-w-sm mx-auto">
                {{-- Theme & Language toggles --}}
                <div class="absolute -top-2 right-0 flex gap-1 z-10">
                    @if(!request()->routeIs('volunteer.invite.*'))
                        <div class="relative overflow-visible" x-data="{ open: false }" @click.away="open = false">
                            <button type="button"
                                    @click="open = !open"
                                    class="p-2 rounded-xl bg-card/80 dark:bg-card/80 border border-border shadow-sm hover:bg-muted transition touch-manipulation"
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
                                 style="display: none; z-index: 9999; top: 44px;">
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
                    @endif
                    <button type="button"
                            @click="toggleTheme()"
                            class="p-2 rounded-xl bg-card/80 dark:bg-card/80 border border-border shadow-sm hover:bg-muted transition"
                            :aria-label="theme === 'light' ? '{{ __('app.theme_light') }}' : (theme === 'sepia' ? '{{ __('app.theme_sepia') }}' : '{{ __('app.theme_dark') }}')">
                        <svg x-show="theme === 'light'" x-cloak class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg x-show="theme === 'sepia'" x-cloak class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        <svg x-show="theme === 'dark'" x-cloak class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </div>

                @if(!request()->routeIs('volunteer.invite.*'))
                    {{-- Quote image & title --}}
                    <div class="text-center mb-8">
                        <img src="{{ asset('images/og-cover.png') }}" alt="{{ __('app.app_name') }}"
                             class="inline-block w-full max-w-[340px] sm:max-w-[400px] rounded-2xl object-contain shadow-xl shadow-black/10 mb-6 ring-2 ring-white/30 dark:ring-white/10">
                        <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight">{{ __('app.app_name') }}</h1>
                        <p class="text-sm text-muted-text mt-1 font-medium">{{ __('app.tagline') }}</p>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    {{-- Member session helpers --}}
    <script>
        window.AbiyTsom = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').content,
            baseUrl: '{{ url('/') }}',

            async api(url, data = {}) {
                const response = await fetch(this.baseUrl + url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });
                return response.json();
            },

            async get(url) {
                const response = await fetch(this.baseUrl + url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                    },
                });
                return response.json();
            }
        };
    </script>
    @stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ 
        darkMode: localStorage.getItem('theme') !== 'light',
        locale: '{{ app()->getLocale() }}',
        setLocale(lang) {
          this.locale = lang;
          window.location.href = '{{ route('member.welcome') }}?lang=' + lang;
        }
      }"
      x-effect="document.documentElement.classList.toggle('dark', darkMode)"
      :class="{ 'dark': darkMode }"
      x-init="if (!localStorage.getItem('theme')) { localStorage.setItem('theme', 'dark'); darkMode = true; }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    @include('partials.favicon')
    <title>@yield('title', __('app.app_name'))</title>
    <script>
        (function(){var t=localStorage.getItem('theme');if(t!=='light')document.documentElement.classList.add('dark');})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <div class="min-h-screen flex flex-col justify-center">
        <div class="flex flex-col justify-center p-4 sm:p-6 lg:p-12 relative min-h-[100dvh] lg:min-h-0 bg-surface">
            {{-- Mobile: background image with soft gradient overlay --}}
            <div class="fixed inset-0 -z-10 lg:hidden">
                <img src="{{ asset('images/login-quote.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-b from-surface/70 via-surface/50 to-surface/90"></div>
            </div>

            <div class="relative w-full max-w-sm mx-auto">
                {{-- Theme & Language toggles --}}
                <div class="absolute -top-2 right-0 flex gap-1 z-10">
                    <div class="relative" x-data="{ open: false }" @click.away="open = false">
                        <button type="button"
                                @click="open = !open"
                                class="p-2 rounded-xl bg-card/80 dark:bg-card/80 border border-border shadow-sm hover:bg-muted transition"
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
                             class="absolute right-0 mt-2 w-40 bg-card border border-border rounded-xl shadow-lg overflow-hidden"
                             style="display: none;">
                            <button @click="setLocale('en'); open = false"
                                    class="w-full px-4 py-2.5 text-left text-sm hover:bg-muted transition flex items-center justify-between"
                                    :class="locale === 'en' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                                <span>English</span>
                                <svg x-show="locale === 'en'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                            <button @click="setLocale('am'); open = false"
                                    class="w-full px-4 py-2.5 text-left text-sm hover:bg-muted transition flex items-center justify-between"
                                    :class="locale === 'am' ? 'bg-accent/10 text-accent font-medium' : 'text-primary'">
                                <span>አማርኛ</span>
                                <svg x-show="locale === 'am'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <button type="button"
                            @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                            class="p-2 rounded-xl bg-card/80 dark:bg-card/80 border border-border shadow-sm hover:bg-muted transition"
                            aria-label="{{ __('app.theme') }}">
                        <svg x-show="!darkMode" class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </button>
                </div>

                {{-- Quote image & title --}}
                <div class="text-center mb-8">
                    <img src="{{ asset('images/login-quote.jpg') }}" alt="{{ __('app.app_name') }}"
                         class="inline-block w-full max-w-[280px] sm:max-w-[340px] rounded-2xl object-contain shadow-xl shadow-black/10 mb-6 ring-2 ring-white/30 dark:ring-white/10">
                    <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight">{{ __('app.app_name') }}</h1>
                    <p class="text-sm text-muted-text mt-1 font-medium">{{ __('app.tagline') }}</p>
                </div>

                @yield('content')
            </div>
        </div>
    </div>

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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.favicon')
    <title>{{ __('app.admin_login') }} - {{ __('app.app_name') }}</title>
    <script>
        (function(){var t=localStorage.getItem('admin_theme');if(t==='dark')document.documentElement.classList.add('dark');})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased"
      x-data="{ darkMode: localStorage.getItem('admin_theme') === 'dark' }"
      x-effect="document.documentElement.classList.toggle('dark', darkMode)">
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- Left: Quote image (hidden on mobile, shown as background) --}}
        <div class="hidden lg:flex lg:w-[45%] xl:w-[50%] relative overflow-hidden shrink-0">
            <img src="{{ asset('images/login-quote.jpg') }}" alt=""
                 class="absolute inset-0 w-full h-full object-cover object-center">
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
            <div class="relative z-10 flex flex-col justify-end p-8 xl:p-12 text-white">
                <p class="text-sm font-medium opacity-90 tracking-wide">{{ __('app.app_name') }}</p>
                <p class="text-xs opacity-75 mt-1">{{ __('app.admin_login') }}</p>
            </div>
        </div>

        {{-- Right: Login form --}}
        <div class="flex-1 flex flex-col justify-center p-4 sm:p-6 lg:p-12 relative min-h-[100dvh] lg:min-h-0 bg-surface">
            {{-- Mobile: background image with soft gradient overlay --}}
            <div class="fixed inset-0 -z-10 lg:hidden">
                <img src="{{ asset('images/login-quote.jpg') }}" alt="" class="absolute inset-0 w-full h-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-b from-surface/70 via-surface/50 to-surface/90"></div>
            </div>

            <div class="relative w-full max-w-sm mx-auto">
                {{-- Theme toggle --}}
                <button type="button"
                        @click="darkMode = !darkMode; localStorage.setItem('admin_theme', darkMode ? 'dark' : 'light')"
                        class="absolute -top-2 right-0 p-2 rounded-xl bg-card/80 dark:bg-card/80 border border-border shadow-sm hover:bg-muted transition z-10"
                        aria-label="{{ __('app.theme') }}">
                    <svg x-show="!darkMode" class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>

                {{-- Quote image & title --}}
                <div class="text-center mb-8">
                    <img src="{{ asset('images/login-quote.jpg') }}" alt="{{ __('app.app_name') }}"
                         class="inline-block w-full max-w-[280px] sm:max-w-[340px] rounded-2xl object-contain shadow-xl shadow-black/10 mb-6 ring-2 ring-white/30 dark:ring-white/10">
                    <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight">{{ __('app.app_name') }}</h1>
                    <p class="text-sm text-muted-text mt-1 font-medium">{{ __('app.admin_login') }}</p>
                </div>

                {{-- Form card --}}
                <form method="POST" action="{{ route('admin.login.submit') }}"
                      class="bg-card rounded-2xl sm:rounded-3xl shadow-xl shadow-black/5 dark:shadow-black/20 p-6 sm:p-8 space-y-5 border border-border">
                    @csrf

                    @if($errors->any())
                        <div class="p-4 bg-error-bg border border-error/50 text-error rounded-xl text-sm font-medium">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-semibold text-secondary mb-2">{{ __('app.username') }}</label>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username"
                               class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary placeholder:text-muted-text
                                      focus:ring-2 focus:ring-accent focus:border-accent outline-none transition">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-secondary mb-2">{{ __('app.password') }}</label>
                        <input type="password" name="password" required autocomplete="current-password"
                               class="w-full px-4 py-3.5 border border-border rounded-xl bg-muted/50 dark:bg-muted/30 text-primary placeholder:text-muted-text
                                      focus:ring-2 focus:ring-accent focus:border-accent outline-none transition">
                    </div>

                    <label class="flex items-center gap-2.5 text-sm text-muted-text cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded border-border text-accent focus:ring-accent">
                        {{ __('app.remember_me') }}
                    </label>

                    <button type="submit"
                            class="w-full py-3.5 bg-accent text-on-accent rounded-xl font-bold text-base hover:bg-accent-hover
                                   active:scale-[0.98] transition shadow-lg shadow-accent/20">
                        {{ __('app.login') }}
                    </button>
                </form>

                <p class="text-center text-xs text-muted-text mt-6">{{ __('app.footer_branding', ['name' => __('app.app_name')]) }}</p>
            </div>
        </div>
    </div>
</body>
</html>

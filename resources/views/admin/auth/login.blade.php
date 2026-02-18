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
<body class="min-h-[100dvh] font-sans antialiased bg-surface"
      x-data="{ darkMode: localStorage.getItem('admin_theme') === 'dark' }"
      x-effect="document.documentElement.classList.toggle('dark', darkMode)">

    {{-- Full-screen wrapper --}}
    <div class="min-h-[100dvh] flex flex-col lg:flex-row">

        {{-- ══════ LEFT PANEL: hero image (desktop only) ══════ --}}
        <div class="hidden lg:flex lg:w-[48%] xl:w-[52%] relative overflow-hidden">
            <img src="{{ asset('images/og-cover.png') }}" alt=""
                 class="absolute inset-0 w-full h-full object-cover object-center"
                 style="background:#1a1207;">
            <div class="absolute inset-0 bg-gradient-to-tr from-black/60 via-black/30 to-transparent"></div>
            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-transparent to-black/50"></div>

            <div class="relative z-10 flex flex-col justify-between p-10 xl:p-14 w-full">
                <div>
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/10 text-white/90 text-xs font-medium tracking-wide">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-secondary animate-pulse"></span>
                        {{ __('app.app_name') }}
                    </span>
                </div>
                <div class="space-y-3">
                    <h2 class="text-3xl xl:text-4xl font-extrabold text-white leading-tight tracking-tight">
                        {{ __('app.app_name') }}
                    </h2>
                    <p class="text-sm text-white/70 max-w-xs leading-relaxed">
                        {{ __('app.admin_login') }}
                    </p>
                    <div class="w-12 h-0.5 rounded-full bg-accent-secondary/60"></div>
                </div>
            </div>
        </div>

        {{-- ══════ RIGHT PANEL: login form ══════ --}}
        <div class="flex-1 flex flex-col relative min-h-[100dvh] lg:min-h-0">

            {{-- Mobile background --}}
            <div class="fixed inset-0 -z-10 lg:hidden">
                <img src="{{ asset('images/og-cover.png') }}" alt=""
                     class="absolute inset-0 w-full h-full object-cover object-center">
                <div class="absolute inset-0 bg-gradient-to-b from-surface/80 via-surface/60 to-surface/95 dark:from-surface/90 dark:via-surface/70 dark:to-surface/98"></div>
            </div>

            {{-- Theme toggle (top-right) --}}
            <div class="flex justify-end p-4 sm:p-6 lg:p-8">
                <button type="button"
                        @click="darkMode = !darkMode; localStorage.setItem('admin_theme', darkMode ? 'dark' : 'light')"
                        class="p-2.5 rounded-xl bg-card/80 backdrop-blur-sm border border-border shadow-sm hover:bg-muted/80 active:scale-95 transition-all"
                        aria-label="{{ __('app.theme') }}">
                    <svg x-show="!darkMode" class="w-4.5 h-4.5 text-muted-text" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-4.5 h-4.5 text-accent-secondary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>

            {{-- Centered form area --}}
            <div class="flex-1 flex items-center justify-center px-5 pb-8 sm:px-8 lg:px-12">
                <div class="w-full max-w-[380px]">

                    {{-- Logo + branding --}}
                    <div class="text-center mb-8">
                        <div class="relative inline-block mb-5">
                            <img src="{{ asset('images/og-cover.png') }}" alt="{{ __('app.app_name') }}"
                                 class="w-full max-w-[260px] sm:max-w-[300px] rounded-2xl object-contain shadow-2xl shadow-black/15 dark:shadow-black/40 ring-1 ring-black/5 dark:ring-white/10">
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold text-primary tracking-tight">
                            {{ __('app.app_name') }}
                        </h1>
                        <p class="text-sm text-muted-text mt-1.5">{{ __('app.admin_login') }}</p>
                    </div>

                    {{-- Login form card --}}
                    <form method="POST" action="{{ route('admin.login.submit') }}"
                          class="bg-card/90 backdrop-blur-md rounded-2xl shadow-xl shadow-black/5 dark:shadow-black/25 border border-border p-5 sm:p-7 space-y-5"
                          x-data="{ showPwd: false }">
                        @csrf

                        {{-- Error alert --}}
                        @if($errors->any())
                            <div class="flex items-start gap-3 p-3.5 bg-error-bg border border-error/30 rounded-xl">
                                <svg class="w-5 h-5 text-error shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                </svg>
                                <p class="text-sm font-medium text-error">{{ $errors->first() }}</p>
                            </div>
                        @endif

                        {{-- Username --}}
                        <div class="space-y-1.5">
                            <label for="username" class="block text-xs font-semibold text-secondary uppercase tracking-wider">
                                {{ __('app.username') }}
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                    <svg class="w-4.5 h-4.5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                    </svg>
                                </span>
                                <input id="username" type="text" name="username" value="{{ old('username') }}"
                                       required autofocus autocomplete="username"
                                       class="w-full pl-11 pr-4 py-3 border border-border rounded-xl bg-surface/80 dark:bg-muted/40 text-primary text-sm placeholder:text-muted-text/70
                                              focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition-all">
                            </div>
                        </div>

                        {{-- Password --}}
                        <div class="space-y-1.5">
                            <label for="password" class="block text-xs font-semibold text-secondary uppercase tracking-wider">
                                {{ __('app.password') }}
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                                    <svg class="w-4.5 h-4.5 text-muted-text" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                    </svg>
                                </span>
                                <input id="password" :type="showPwd ? 'text' : 'password'" name="password"
                                       required autocomplete="current-password"
                                       class="w-full pl-11 pr-11 py-3 border border-border rounded-xl bg-surface/80 dark:bg-muted/40 text-primary text-sm placeholder:text-muted-text/70
                                              focus:ring-2 focus:ring-accent/40 focus:border-accent outline-none transition-all">
                                <button type="button" @click="showPwd = !showPwd"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-muted-text hover:text-secondary transition">
                                    <svg x-show="!showPwd" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <svg x-show="showPwd" class="w-4.5 h-4.5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" x-cloak>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Remember me --}}
                        <label class="flex items-center gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="remember"
                                   class="w-4 h-4 rounded border-border text-accent focus:ring-accent/40 focus:ring-offset-0 transition">
                            <span class="text-sm text-muted-text group-hover:text-secondary transition">{{ __('app.remember_me') }}</span>
                        </label>

                        {{-- Submit --}}
                        <button type="submit"
                                class="w-full py-3 bg-accent text-on-accent rounded-xl font-bold text-sm tracking-wide
                                       hover:bg-accent-hover active:scale-[0.98] transition-all duration-150
                                       shadow-lg shadow-accent/25 hover:shadow-xl hover:shadow-accent/30
                                       focus:outline-none focus:ring-2 focus:ring-accent/50 focus:ring-offset-2 focus:ring-offset-card">
                            {{ __('app.login') }}
                        </button>
                    </form>

                    {{-- Footer --}}
                    <p class="text-center text-[11px] text-muted-text/70 mt-6 leading-relaxed">
                        {{ __('app.footer_branding', ['name' => __('app.app_name')]) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

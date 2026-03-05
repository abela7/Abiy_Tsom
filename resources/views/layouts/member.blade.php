<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"
      x-data="{
        theme: (function(){var stored=localStorage.getItem('theme');var server='{{ $currentMember?->theme ?? 'sepia' }}';var t=(stored==='light'||stored==='sepia'||stored==='dark')?stored:((server==='light'||server==='sepia'||server==='dark')?server:'sepia');return t;})(),
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
          if (window.AbiyTsom?.api) { AbiyTsom.api('/api/member/settings', { theme: this.theme }); }
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
      :class="{ 'dark': theme === 'dark', 'theme-sepia': theme === 'sepia' }"
      x-init="
        if (!localStorage.getItem('theme')) { localStorage.setItem('theme', theme); }
        document.documentElement.classList.toggle('dark', theme === 'dark');
        document.documentElement.classList.toggle('theme-sepia', theme === 'sepia');
        window.addEventListener('theme-changed', (e) => { if (e.detail && e.detail.theme) { theme = e.detail.theme; document.documentElement.classList.toggle('dark', theme === 'dark'); document.documentElement.classList.toggle('theme-sepia', theme === 'sepia'); } });
      ">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    <script>
        (function(){var stored=localStorage.getItem('theme');var server='{{ $currentMember?->theme ?? 'sepia' }}';var t=(stored==='light'||stored==='sepia'||stored==='dark')?stored:((server==='light'||server==='sepia'||server==='dark')?server:'sepia');if(t==='dark')document.documentElement.classList.add('dark');else if(t==='sepia')document.documentElement.classList.add('theme-sepia');})();
    </script>
    <meta name="mobile-web-app-capable" content="yes">
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

    @if(config('services.google.analytics_id'))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google.analytics_id') }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ config('services.google.analytics_id') }}',{anonymize_ip:true,allow_google_signals:false,allow_ad_personalization_signals:false});</script>
    @endif
</head>
<body class="min-h-screen bg-surface text-primary font-sans">

    {{-- Top nav: Welcome + theme toggle (member pages only) --}}
    @if(isset($currentMember) && request()->routeIs('member.*'))
    <header class="sticky top-0 z-40 bg-card border-b border-border safe-area-top overflow-visible">
        <div class="max-w-lg mx-auto px-4 py-3 flex items-center justify-between overflow-visible">
            @php $baptismName = trim((string) ($currentMember->baptism_name ?? '')); @endphp
            <h1 class="flex-1 min-w-0 pr-2 font-bold text-primary leading-tight whitespace-nowrap overflow-hidden text-ellipsis"
                style="font-size: clamp(0.95rem, 4.6vw, 1.125rem);">
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
            <div class="flex items-center gap-1.5 shrink-0">
                @if($currentMember->passcode_enabled ?? false)
                <a href="{{ route('member.passcode.lock') }}"
                   class="w-9 h-9 rounded-full bg-muted/70 border border-border/50 flex items-center justify-center hover:bg-muted transition active:scale-95"
                   aria-label="{{ __('app.lock_app') }}">
                    <svg class="w-4.5 h-4.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </a>
                @endif
                <div class="relative overflow-visible" x-data="{ open: false }" @click.away="open = false" data-tour="language">
                    <button type="button"
                            @click="open = !open"
                            class="w-9 h-9 rounded-full bg-muted/70 border border-border/50 flex items-center justify-center hover:bg-muted transition active:scale-95 touch-manipulation"
                            :aria-label="'{{ __('app.language') }}'">
                        {{-- Show the OTHER language's flag as a hint to switch --}}
                        <span x-show="locale === 'am'" x-cloak class="text-base leading-none">🇬🇧</span>
                        <span x-show="locale === 'en'" x-cloak class="text-base leading-none">🇪🇹</span>
                    </button>
                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         @click.away="open = false"
                         class="absolute right-0 mt-2 w-48 bg-card border border-border rounded-xl shadow-2xl overflow-hidden"
                         style="display: none; z-index: 9999;">
                        <button @click="setLocale('en'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center gap-3 touch-manipulation"
                                :class="locale === 'en' ? 'bg-accent/10 text-accent font-semibold' : 'text-primary'">
                            <span class="text-lg">🇬🇧</span>
                            <span>English</span>
                            <svg x-show="locale === 'en'" class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                        <button @click="setLocale('am'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition flex items-center gap-3 touch-manipulation"
                                :class="locale === 'am' ? 'bg-accent/10 text-accent font-semibold' : 'text-primary'">
                            <span class="text-lg">🇪🇹</span>
                            <span>አማርኛ</span>
                            <svg x-show="locale === 'am'" class="w-4 h-4 ml-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button"
                        @click="toggleTheme()"
                        class="w-9 h-9 rounded-full bg-muted/70 border border-border/50 flex items-center justify-center hover:bg-muted transition active:scale-95"
                        :aria-label="theme === 'light' ? '{{ __('app.theme_light') }}' : (theme === 'sepia' ? '{{ __('app.theme_sepia') }}' : '{{ __('app.theme_dark') }}')"
                        data-tour="theme">
                    {{-- Sun: light theme --}}
                    <svg x-show="theme === 'light'" x-cloak class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{-- Sepia: warm book-like icon --}}
                    <svg x-show="theme === 'sepia'" x-cloak class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    {{-- Moon: dark theme --}}
                    <svg x-show="theme === 'dark'" x-cloak class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <nav class="fixed bottom-0 inset-x-0 bg-card border-t border-border z-50 safe-area-bottom">
            <div class="max-w-lg mx-auto flex justify-around items-center h-16">
                <a href="{{ route('member.home') }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.home') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <span>{{ __('app.nav_home') }}</span>
                </a>
                <a href="{{ route('member.calendar') }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.calendar') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span>{{ __('app.nav_calendar') }}</span>
                </a>
                <a href="{{ route('member.progress') }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.progress') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    <span>{{ __('app.nav_progress') }}</span>
                </a>
                <a href="{{ route('member.settings') }}"
                   class="flex flex-col items-center gap-1 px-3 py-2 text-xs {{ request()->routeIs('member.settings') ? 'text-accent' : 'text-muted-text' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>{{ __('app.nav_settings') }}</span>
                </a>
                <button type="button" @click="$dispatch('open-feedback')"
                        class="flex flex-col items-center gap-1 px-3 py-2 text-xs text-muted-text">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ __('app.feedback_page_title') }}</span>
                </button>
            </div>
        </nav>
    @endif

    {{-- Fundraising popup — only on home or day page, once per day --}}
    @if(isset($currentMember) && request()->routeIs('member.home', 'member.day'))
        @include('member.partials.fundraising-popup')
    @endif

    {{-- Tour content (locale-aware) --}}
    @if(isset($currentMember) && request()->routeIs('member.*'))
    @php
        $tourContent = [
            'next' => __('app.tour_next'),
            'prev' => __('app.tour_prev'),
            'done' => __('app.tour_done'),
            'skip' => __('app.tour_skip'),
            'progressText' => __('app.tour_progress'),
            'home' => [
                'welcome'   => ['title' => __('app.tour_welcome_title'),    'desc' => __('app.tour_welcome_desc')],
                'language'  => ['title' => __('app.tour_language_title'),   'desc' => __('app.tour_language_desc')],
                'theme'     => ['title' => __('app.tour_theme_title'),      'desc' => __('app.tour_theme_desc')],
                'countdown' => ['title' => __('app.tour_home_countdown_title'), 'desc' => __('app.tour_home_countdown_desc')],
                'viewToday' => ['title' => __('app.tour_view_today_title'), 'desc' => __('app.tour_view_today_desc')],
            ],
            'calendar' => [
                'legend' => ['title' => __('app.tour_cal_legend_title'), 'desc' => __('app.tour_cal_legend_desc')],
                'week'   => ['title' => __('app.tour_cal_week_title'),   'desc' => __('app.tour_cal_week_desc')],
                'today'  => ['title' => __('app.tour_cal_today_title'),  'desc' => __('app.tour_cal_today_desc')],
            ],
            'day' => [
                'header'     => ['title' => __('app.tour_day_header_title'),     'desc' => __('app.tour_day_header_desc')],
                'bible'      => ['title' => __('app.tour_day_bible_title'),      'desc' => __('app.tour_day_bible_desc')],
                'mezmur'     => ['title' => __('app.tour_day_mezmur_title'),     'desc' => __('app.tour_day_mezmur_desc')],
                'sinksar'    => ['title' => __('app.tour_day_sinksar_title'),    'desc' => __('app.tour_day_sinksar_desc')],
                'book'       => ['title' => __('app.tour_day_book_title'),       'desc' => __('app.tour_day_book_desc')],
                'references' => ['title' => __('app.tour_day_references_title'), 'desc' => __('app.tour_day_references_desc')],
                'checklist'  => ['title' => __('app.tour_day_checklist_title'),  'desc' => __('app.tour_day_checklist_desc')],
                'custom'     => ['title' => __('app.tour_day_custom_title'),     'desc' => __('app.tour_day_custom_desc')],
                'privacy'    => ['title' => __('app.tour_day_privacy_title'),    'desc' => __('app.tour_day_privacy_desc')],
            ],
            'settings' => [
                'whatsapp'  => ['title' => __('app.tour_settings_whatsapp_title'),  'desc' => __('app.tour_settings_whatsapp_desc')],
                'telegram'  => ['title' => __('app.tour_settings_telegram_title'),  'desc' => __('app.tour_settings_telegram_desc')],
                'custom'    => ['title' => __('app.tour_settings_custom_title'),    'desc' => __('app.tour_settings_custom_desc')],
                'passcode'  => ['title' => __('app.tour_settings_passcode_title'),  'desc' => __('app.tour_settings_passcode_desc')],
                'done'     => ['title' => __('app.tour_settings_done_title'),     'desc' => __('app.tour_settings_done_desc')],
            ],
        ];
    @endphp
    <script>
        window.AbiyTsomTourContent = @json($tourContent);
        window.AbiyTsomTourCompleted = {{ ($currentMember->tour_completed_at ?? null) ? 'true' : 'false' }};
    </script>
    @endif

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
    {{-- Feedback modal (swipe-to-dismiss bottom sheet) --}}
    @if(isset($currentMember) && request()->routeIs('member.*'))
    <div x-data="{
            open: false,
            name: '',
            email: '',
            message: '',
            website: '',
            submitting: false,
            submitted: false,
            rateLimited: false,
            errors: {},
            dragY: 0,
            dragging: false,
            startY: 0,
            get canSubmit() { return this.name.trim().length > 0 && this.message.trim().length > 0; },
            submit() {
                if (!this.canSubmit || this.submitting) return;
                this.submitting = true;
                this.errors = {};
                this.rateLimited = false;
                var self = this;
                fetch(@js(route('feedback.store')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': AbiyTsom.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ name: self.name, email: self.email, message: self.message, website: self.website }),
                }).then(function(r) {
                    if (r.status === 429) { self.rateLimited = true; self.submitting = false; return; }
                    return r.json();
                }).then(function(d) {
                    if (!d) return;
                    if (d.errors) { self.errors = d.errors; self.submitting = false; return; }
                    if (d.success) { self.submitted = true; self.submitting = false; }
                }).catch(function() { self.submitting = false; });
            },
            reset() { this.name = ''; this.email = ''; this.message = ''; this.website = ''; this.submitted = false; this.errors = {}; this.rateLimited = false; this.submitting = false; },
            close() { this.open = false; this.dragY = 0; var self = this; setTimeout(function() { if (self.submitted) self.reset(); }, 300); },
            onTouchStart(e) {
                this.startY = e.touches[0].clientY;
                this.dragging = true;
            },
            onTouchMove(e) {
                if (!this.dragging) return;
                var dy = e.touches[0].clientY - this.startY;
                this.dragY = Math.max(0, dy);
            },
            onTouchEnd() {
                if (!this.dragging) return;
                this.dragging = false;
                if (this.dragY > 120) { this.close(); }
                else { this.dragY = 0; }
            }
         }"
         @open-feedback.window="open = true"
         x-show="open"
         x-cloak
         class="fixed inset-0 z-[100]"
         @keydown.escape.window="if(open) close()">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm"
             x-show="open"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             :style="dragY > 0 ? 'opacity:' + Math.max(0, 1 - dragY / 300) : ''"
             @click="close()"></div>

        {{-- Modal panel --}}
        <div class="absolute inset-x-0 bottom-0 max-h-[90vh] flex flex-col"
             x-ref="fbPanel"
             x-show="open"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             :style="dragY > 0 ? 'transform:translateY(' + dragY + 'px);transition:none' : ''">
            <div class="bg-card rounded-t-2xl border-t border-border shadow-xl overflow-y-auto safe-area-bottom">
                {{-- Drag handle --}}
                <div class="flex justify-center pt-3 pb-1 cursor-grab active:cursor-grabbing touch-manipulation"
                     @touchstart="onTouchStart($event)"
                     @touchmove.prevent="onTouchMove($event)"
                     @touchend="onTouchEnd()"
                     @mousedown.prevent="startY = $event.clientY; dragging = true;
                         var mm = function(e) { if (!dragging) return; dragY = Math.max(0, e.clientY - startY); };
                         var mu = function() { dragging = false; document.removeEventListener('mousemove', mm); document.removeEventListener('mouseup', mu); if (dragY > 120) { close(); } else { dragY = 0; } };
                         document.addEventListener('mousemove', mm); document.addEventListener('mouseup', mu);">
                    <div class="w-10 h-1 rounded-full bg-border"></div>
                </div>

                <div class="px-5 pb-6">
                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-primary">{{ __('app.feedback_page_title') }}</h2>
                        <button type="button" @click="close()" class="p-1.5 rounded-lg hover:bg-muted transition">
                            <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Form --}}
                    <div x-show="!submitted">
                        <p class="text-sm text-muted-text mb-4">{{ __('app.feedback_subtitle') }}</p>

                        <div class="space-y-3">
                            {{-- Name --}}
                            <div>
                                <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.feedback_name') }} <span class="text-red-500">*</span></label>
                                <input type="text" x-model="name" maxlength="255" placeholder="{{ __('app.feedback_name_ph') }}"
                                       class="w-full h-11 px-4 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                <template x-if="errors.name"><p class="text-xs text-red-500 mt-1" x-text="errors.name[0]"></p></template>
                            </div>

                            {{-- Email --}}
                            <div>
                                <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.feedback_email') }} <span class="text-muted-text/60 normal-case tracking-normal font-normal">{{ __('app.feedback_email_optional') }}</span></label>
                                <input type="email" x-model="email" maxlength="255" placeholder="{{ __('app.feedback_email_ph') }}"
                                       class="w-full h-11 px-4 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                <template x-if="errors.email"><p class="text-xs text-red-500 mt-1" x-text="errors.email[0]"></p></template>
                            </div>

                            {{-- Message --}}
                            <div>
                                <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.feedback_message') }} <span class="text-red-500">*</span></label>
                                <textarea x-model="message" rows="3" maxlength="2000" placeholder="{{ __('app.feedback_message_ph') }}"
                                          class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"></textarea>
                                <template x-if="errors.message"><p class="text-xs text-red-500 mt-1" x-text="errors.message[0]"></p></template>
                            </div>

                            {{-- Honeypot --}}
                            <div class="absolute -left-[9999px]" aria-hidden="true"><input type="text" x-model="website" tabindex="-1" autocomplete="off"></div>

                            {{-- Submit --}}
                            <button type="button" @click="submit()"
                                    :disabled="!canSubmit || submitting"
                                    :class="canSubmit && !submitting ? 'bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97]' : 'bg-muted text-muted-text cursor-not-allowed'"
                                    class="w-full h-11 rounded-xl font-bold text-sm transition touch-manipulation flex items-center justify-center gap-2">
                                <template x-if="submitting">
                                    <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/></svg>
                                </template>
                                <template x-if="!submitting">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                </template>
                                {{ __('app.feedback_send') }}
                            </button>
                            <template x-if="rateLimited"><p class="text-xs text-red-500 text-center">{{ __('app.feedback_rate_limited') }}</p></template>
                        </div>
                    </div>

                    {{-- Success --}}
                    <div x-show="submitted" x-cloak x-transition>
                        <div class="text-center py-4 space-y-4">
                            <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-primary">{{ __('app.feedback_success_title') }}</h3>
                            <p class="text-sm text-muted-text">{{ __('app.feedback_success_body') }}</p>
                            <button type="button" @click="close()" class="w-full h-11 rounded-xl bg-accent text-on-accent font-bold text-sm hover:bg-accent-hover active:scale-[0.97] transition touch-manipulation">
                                {{ __('app.close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @stack('scripts')
</body>
</html>

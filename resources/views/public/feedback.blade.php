<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('theme') !== 'light',
      }"
      x-effect="document.documentElement.classList.toggle('dark', darkMode)"
      :class="{ 'dark': darkMode }"
      x-init="if (!localStorage.getItem('theme')) { localStorage.setItem('theme', 'dark'); darkMode = true; }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0a6286">
    @include('partials.favicon')
    <title>{{ __('app.feedback_page_title') }} — {{ __('app.app_name') }}</title>
    <script>(function(){var t=localStorage.getItem('theme');if(t!=='light')document.documentElement.classList.add('dark');})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-surface font-sans antialiased">

<div x-data="feedbackApp()" class="flex flex-col min-h-[100dvh]">

    {{-- Header --}}
    <header class="sticky top-0 z-40 bg-card/95 backdrop-blur-lg border-b border-border safe-top">
        <div class="flex items-center justify-between px-4 h-14 max-w-lg mx-auto">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('home') }}" class="p-1.5 -ml-1.5 rounded-lg hover:bg-muted transition touch-manipulation shrink-0">
                    <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-base font-bold text-primary truncate">{{ __('app.feedback_page_title') }}</h1>
            </div>
            <button type="button" @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" class="p-2 rounded-xl hover:bg-muted transition touch-manipulation">
                <svg x-show="!darkMode" class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </button>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto">
        <div class="max-w-lg mx-auto px-4 py-6 pb-8">

            {{-- Form --}}
            <div x-show="!submitted" x-transition>
                <div class="text-center mb-6">
                    <div class="mx-auto w-14 h-14 rounded-full bg-accent/10 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                    <p class="text-sm text-muted-text leading-relaxed">{{ __('app.feedback_subtitle') }}</p>
                </div>

                <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 space-y-4">

                        {{-- Name --}}
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.feedback_name') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="name" maxlength="255"
                                   placeholder="{{ __('app.feedback_name_ph') }}"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                            <template x-if="errors.name">
                                <p class="text-xs text-red-500 mt-1" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        {{-- Email --}}
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.feedback_email') }}
                                <span class="text-muted-text/60 normal-case tracking-normal font-normal">{{ __('app.feedback_email_optional') }}</span>
                            </label>
                            <input type="email" x-model="email" maxlength="255"
                                   placeholder="{{ __('app.feedback_email_ph') }}"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                            <template x-if="errors.email">
                                <p class="text-xs text-red-500 mt-1" x-text="errors.email[0]"></p>
                            </template>
                        </div>

                        {{-- Message --}}
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.feedback_message') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea x-model="message" rows="4" maxlength="2000"
                                      placeholder="{{ __('app.feedback_message_ph') }}"
                                      class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"></textarea>
                            <template x-if="errors.message">
                                <p class="text-xs text-red-500 mt-1" x-text="errors.message[0]"></p>
                            </template>
                        </div>

                        {{-- Honeypot (hidden from real users) --}}
                        <div class="absolute -left-[9999px]" aria-hidden="true">
                            <input type="text" x-model="website" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- Submit --}}
                        <button type="button" @click="submit()"
                                :disabled="!canSubmit || submitting"
                                :class="canSubmit && !submitting ? 'bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97]' : 'bg-muted text-muted-text cursor-not-allowed'"
                                class="w-full h-12 rounded-xl font-bold text-base transition touch-manipulation flex items-center justify-center gap-2">
                            <template x-if="submitting">
                                <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                                </svg>
                            </template>
                            <template x-if="!submitting">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            </template>
                            {{ __('app.feedback_send') }}
                        </button>

                        {{-- Rate limit error --}}
                        <template x-if="rateLimited">
                            <p class="text-xs text-red-500 text-center">{{ __('app.feedback_rate_limited') }}</p>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Success --}}
            <div x-show="submitted" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <div class="mt-8 text-center space-y-5">
                    <div class="mx-auto w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-primary">{{ __('app.feedback_success_title') }}</h2>
                    <p class="text-sm text-muted-text leading-relaxed max-w-xs mx-auto">{{ __('app.feedback_success_body') }}</p>
                    <div class="flex flex-col gap-2.5 max-w-xs mx-auto">
                        <button type="button" @click="reset()"
                                class="w-full h-12 rounded-xl border border-border text-sm font-semibold text-secondary hover:bg-muted active:scale-[0.97] transition touch-manipulation flex items-center justify-center gap-2">
                            {{ __('app.feedback_send_another') }}
                        </button>
                        <a href="{{ route('home') }}"
                           class="w-full h-12 rounded-xl bg-accent text-on-accent font-bold text-sm hover:bg-accent-hover active:scale-[0.97] transition touch-manipulation flex items-center justify-center gap-2">
                            {{ __('app.nav_home') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('feedbackApp', function () {
        return {
            name: '',
            email: '',
            message: '',
            website: '',
            submitting: false,
            submitted: false,
            rateLimited: false,
            errors: {},

            get canSubmit() {
                return this.name.trim().length > 0 && this.message.trim().length > 0;
            },

            submit: function () {
                if (!this.canSubmit || this.submitting) return;
                this.submitting = true;
                this.errors = {};
                this.rateLimited = false;

                var self = this;
                var token = document.querySelector('meta[name="csrf-token"]').content;

                fetch(@js(route('feedback.store')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        name: self.name,
                        email: self.email,
                        message: self.message,
                        website: self.website,
                    }),
                })
                .then(function (r) {
                    if (r.status === 429) {
                        self.rateLimited = true;
                        self.submitting = false;
                        return;
                    }
                    return r.json();
                })
                .then(function (d) {
                    if (!d) return;
                    if (d.errors) {
                        self.errors = d.errors;
                        self.submitting = false;
                        return;
                    }
                    if (d.success) {
                        self.submitted = true;
                        self.submitting = false;
                    }
                })
                .catch(function () {
                    self.submitting = false;
                });
            },

            reset: function () {
                this.name = '';
                this.email = '';
                this.message = '';
                this.website = '';
                this.submitted = false;
                this.errors = {};
                this.rateLimited = false;
            },
        };
    });
});
</script>
</body>
</html>

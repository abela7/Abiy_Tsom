<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
        darkMode: localStorage.getItem('theme') !== 'light',
        locale: '{{ app()->getLocale() }}',
        setLocale(lang) {
          this.locale = lang;
          const url = new URL(window.location.href);
          url.searchParams.set('lang', lang);
          window.location.href = url.toString();
        }
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
    @include('partials.seo-meta')
    <title>{{ __('app.suggest_page_title') }} — {{ __('app.app_name') }}</title>
    <script>
        (function(){var t=localStorage.getItem('theme');if(t!=='light')document.documentElement.classList.add('dark');})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-surface font-sans antialiased">

<div
    x-data="suggestForm()"
    class="flex flex-col min-h-[100dvh]"
>
    {{-- ── Sticky header ───────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-40 bg-card/95 backdrop-blur-lg border-b border-border safe-top">
        <div class="flex items-center justify-between px-4 h-14 max-w-2xl mx-auto">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('home') }}" class="p-1.5 -ml-1.5 rounded-lg hover:bg-muted transition touch-manipulation shrink-0">
                    <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-base font-bold text-primary truncate">{{ __('app.suggest_page_title') }}</h1>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                {{-- Language toggle --}}
                <div class="relative" x-data="{ open: false }" @click.away="open = false">
                    <button type="button" @click="open = !open"
                            class="p-2 rounded-xl hover:bg-muted transition touch-manipulation">
                        <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </button>
                    <div x-show="open" x-transition x-cloak @click.away="open = false"
                         class="absolute right-0 mt-1 w-40 bg-card border border-border rounded-xl shadow-2xl overflow-hidden z-50">
                        <button @click="setLocale('en'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition touch-manipulation"
                                :class="locale === 'en' ? 'text-accent font-semibold' : 'text-primary'">
                            English
                        </button>
                        <button @click="setLocale('am'); open = false"
                                class="w-full px-4 py-3 text-left text-sm hover:bg-muted transition touch-manipulation"
                                :class="locale === 'am' ? 'text-accent font-semibold' : 'text-primary'">
                            አማርኛ
                        </button>
                    </div>
                </div>
                {{-- Dark mode toggle --}}
                <button type="button"
                        @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')"
                        class="p-2 rounded-xl hover:bg-muted transition touch-manipulation">
                    <svg x-show="!darkMode" class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    {{-- ── Main scrollable area ────────────────────────────────────────────── --}}
    <main class="flex-1 overflow-y-auto">
        <div class="max-w-2xl mx-auto px-4 py-5 pb-32 space-y-4">

            {{-- ── Success State ────────────────────────────── --}}
            <div x-show="submitted" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <div class="bg-card rounded-2xl shadow-lg border border-border overflow-hidden">
                    <div class="px-5 py-10 sm:px-8 text-center space-y-4">
                        <div class="mx-auto w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-black text-primary">{{ __('app.suggest_success_title') }}</h2>
                        <p class="text-sm text-muted-text leading-relaxed max-w-xs mx-auto" x-text="successMessage"></p>
                        <button type="button" @click="reset()"
                                class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-on-accent rounded-xl font-bold text-sm hover:bg-accent-hover active:scale-[0.97] transition touch-manipulation">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ __('app.suggest_another') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Form ────────────────────────────────────── --}}
            <form x-show="!submitted" method="POST" action="{{ route('suggest.store') }}" @submit="submitting = true" class="space-y-4">
                @csrf

                {{-- Intro card --}}
                <div class="bg-card rounded-2xl border border-border shadow-sm px-4 py-4 sm:px-5 space-y-3">
                    <p class="text-sm text-muted-text leading-relaxed">
                        {{ __('app.suggest_page_subtitle') }}
                    </p>

                    @if($authUser)
                        <div class="flex items-center justify-between gap-2 p-2.5 rounded-xl bg-accent/5 border border-accent/20">
                            <span class="text-xs font-medium text-accent truncate">
                                {{ __('app.suggest_logged_in_as', ['name' => $authUser->name]) }}
                            </span>
                            <a href="{{ route('admin.suggestions.my') }}" class="text-xs font-semibold text-accent hover:underline shrink-0">
                                {{ __('app.suggest_my_suggestions') }}
                            </a>
                        </div>
                    @endif

                    {{-- Validation errors --}}
                    @if($errors->any())
                        <div class="p-3 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-xs text-red-700 dark:text-red-400 flex items-start gap-1.5">
                                        <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Name + Language row --}}
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                {{ __('app.suggest_submitter_label') }}
                            </label>
                            <input type="text" name="submitter_name"
                                   value="{{ old('submitter_name', $authUser?->name ?? '') }}"
                                   placeholder="{{ __('app.suggest_submitter_ph') }}"
                                   maxlength="100"
                                   @if($authUser) readonly @endif
                                   class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition {{ $authUser ? 'bg-muted/40' : '' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                {{ __('app.suggest_language_label') }}
                            </label>
                            <div class="flex h-11 rounded-xl border border-border overflow-hidden">
                                <button type="button" @click="language = 'en'"
                                        :class="language === 'en' ? 'bg-accent text-on-accent font-semibold' : 'bg-surface text-muted-text hover:bg-muted'"
                                        class="flex-1 text-sm text-center transition touch-manipulation">
                                    {{ __('app.suggest_language_en') }}
                                </button>
                                <button type="button" @click="language = 'am'"
                                        :class="language === 'am' ? 'bg-accent text-on-accent font-semibold' : 'bg-surface text-muted-text hover:bg-muted'"
                                        class="flex-1 text-sm text-center transition touch-manipulation border-l border-border">
                                    {{ __('app.suggest_language_am') }}
                                </button>
                            </div>
                            <input type="hidden" name="language" :value="language">
                        </div>
                    </div>
                </div>

                {{-- ── Suggestion Items ────────────────────────── --}}
                <template x-for="(item, idx) in items" :key="item.id">
                    <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0">

                        {{-- Item header --}}
                        <div class="flex items-center justify-between px-4 py-2.5 bg-muted/40 border-b border-border">
                            <span class="text-xs font-bold text-muted-text uppercase tracking-widest"
                                  x-text="'{{ __('app.suggest_item_n', ['n' => '']) }}' + (idx + 1)"></span>
                            <button type="button" x-show="items.length > 1" @click="removeItem(idx)"
                                    class="text-xs font-medium text-error hover:underline transition touch-manipulation">
                                {{ __('app.suggest_remove_item') }}
                            </button>
                        </div>

                        <div class="px-4 py-3 sm:px-5 space-y-3">

                            {{-- Type selector (horizontal scroll on small) --}}
                            <div>
                                <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                    {{ __('app.suggest_type_label') }} <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-1.5 overflow-x-auto pb-1 -mx-1 px-1 snap-x snap-mandatory scrollbar-none">
                                    <template x-for="t in types" :key="t.value">
                                        <button type="button" @click="item.type = t.value"
                                                :class="item.type === t.value
                                                    ? 'bg-accent text-on-accent border-accent shadow-sm'
                                                    : 'bg-surface border-border text-muted-text hover:border-accent/40 hover:text-primary'"
                                                class="flex items-center gap-1.5 px-3 py-2 rounded-xl border text-xs font-medium whitespace-nowrap transition touch-manipulation snap-start shrink-0">
                                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.icon"/>
                                            </svg>
                                            <span x-text="t.label"></span>
                                        </button>
                                    </template>
                                </div>
                                <input type="hidden" :name="'items[' + idx + '][type]'" :value="item.type">
                            </div>

                            {{-- Dynamic fields based on type --}}
                            <div x-show="item.type" x-transition class="space-y-3">
                                {{-- Title --}}
                                <div>
                                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                        {{ __('app.suggest_title_label') }}
                                    </label>
                                    <input type="text" :name="'items[' + idx + '][title]'"
                                           x-model="item.title"
                                           :placeholder="placeholderFor(item.type)"
                                           maxlength="255"
                                           class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                </div>

                                {{-- Reference (bible only) --}}
                                <div x-show="item.type === 'bible'" x-cloak>
                                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                        {{ __('app.suggest_reference_label') }}
                                    </label>
                                    <input type="text"
                                           :name="item.type === 'bible' ? 'items[' + idx + '][reference]' : ''"
                                           :disabled="item.type !== 'bible'"
                                           x-model="item.reference"
                                           placeholder="{{ __('app.suggest_reference_ph') }}"
                                           maxlength="500"
                                           class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                </div>

                                {{-- Author (mezmur / book) --}}
                                <div x-show="item.type === 'mezmur' || item.type === 'book'" x-cloak>
                                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                        {{ __('app.suggest_author_label') }}
                                    </label>
                                    <input type="text"
                                           :name="(item.type === 'mezmur' || item.type === 'book') ? 'items[' + idx + '][author]' : ''"
                                           :disabled="item.type !== 'mezmur' && item.type !== 'book'"
                                           x-model="item.author"
                                           placeholder="{{ __('app.suggest_author_ph') }}"
                                           maxlength="255"
                                           class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                </div>

                                {{-- URL (mezmur / book / reference) — type="text" to avoid browser URL constraint validation --}}
                                <div x-show="item.type === 'mezmur' || item.type === 'book' || item.type === 'reference'" x-cloak>
                                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                        {{ __('app.suggest_url_label') }}
                                    </label>
                                    <input type="text"
                                           :name="(item.type === 'mezmur' || item.type === 'book' || item.type === 'reference') ? 'items[' + idx + '][reference]' : ''"
                                           :disabled="item.type !== 'mezmur' && item.type !== 'book' && item.type !== 'reference'"
                                           x-model="item.reference"
                                           placeholder="{{ __('app.suggest_url_ph') }}"
                                           maxlength="500"
                                           class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                                </div>

                                {{-- Description --}}
                                <div>
                                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                                        {{ __('app.suggest_detail_label') }}
                                    </label>
                                    <textarea :name="'items[' + idx + '][content_detail]'"
                                              x-model="item.content_detail"
                                              rows="2"
                                              maxlength="5000"
                                              placeholder="{{ __('app.suggest_detail_ph') }}"
                                              class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"></textarea>
                                </div>
                            </div>

                            {{-- Prompt when no type selected --}}
                            <div x-show="!item.type" class="py-4 text-center text-xs text-muted-text">
                                {{ __('app.suggest_type_placeholder') }}
                            </div>
                        </div>
                    </div>
                </template>

                {{-- ── Add another button ──────────────────────── --}}
                <button type="button" @click="addItem()"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-2xl border-2 border-dashed border-border text-sm font-semibold text-muted-text hover:border-accent/40 hover:text-accent active:scale-[0.98] transition touch-manipulation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('app.suggest_add_item') }}
                </button>

                {{-- ── General notes ───────────────────────────── --}}
                <div class="bg-card rounded-2xl border border-border shadow-sm px-4 py-3 sm:px-5">
                    <label class="block text-[11px] font-bold text-muted-text uppercase tracking-widest mb-1">
                        {{ __('app.suggest_general_notes') }}
                    </label>
                    <textarea name="notes" rows="2" maxlength="2000"
                              placeholder="{{ __('app.suggest_general_notes_ph') }}"
                              class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"></textarea>
                </div>
            </form>
        </div>
    </main>

    {{-- ── Sticky bottom submit bar ────────────────────────────────────────── --}}
    <div x-show="!submitted" x-cloak
         class="fixed bottom-0 inset-x-0 z-40 bg-card/95 backdrop-blur-lg border-t border-border safe-bottom">
        <div class="max-w-2xl mx-auto px-4 py-3">
            <button type="submit" form=""
                    @click="$el.closest('body').querySelector('form')?.requestSubmit()"
                    :disabled="!canSubmit || submitting"
                    :class="canSubmit && !submitting ? 'bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97]' : 'bg-muted text-muted-text cursor-not-allowed'"
                    class="w-full h-12 rounded-xl font-bold text-sm transition touch-manipulation flex items-center justify-center gap-2">
                <template x-if="submitting">
                    <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                        <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                    </svg>
                </template>
                <template x-if="!submitting">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </template>
                <span x-text="submitLabel"></span>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('suggestForm', function () {
        var successCount = {{ session('success', 0) }};
        return {
            language: '{{ old('language', 'en') }}',
            submitted: successCount > 0,
            submitting: false,
            nextId: 2,
            items: [{ id: 1, type: '', title: '', reference: '', author: '', content_detail: '' }],

            types: [
                { value: 'bible',     label: @js(__('app.suggest_type_bible')),     icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253' },
                { value: 'mezmur',    label: @js(__('app.suggest_type_mezmur')),    icon: 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3' },
                { value: 'sinksar',   label: @js(__('app.suggest_type_sinksar')),   icon: 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z' },
                { value: 'book',      label: @js(__('app.suggest_type_book')),      icon: 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z' },
                { value: 'reference', label: @js(__('app.suggest_type_reference')), icon: 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1' },
            ],

            placeholders: {
                bible:     @js(__('app.suggest_title_bible')),
                mezmur:    @js(__('app.suggest_title_mezmur')),
                sinksar:   @js(__('app.suggest_title_sinksar')),
                book:      @js(__('app.suggest_title_book')),
                reference: @js(__('app.suggest_title_reference')),
            },

            get successMessage() {
                if (successCount === 1) return @js(__('app.suggest_success_body'));
                return @js(__('app.suggest_success_body_plural', ['count' => '__COUNT__'])).replace('__COUNT__', successCount);
            },

            get canSubmit() {
                return this.items.some(function (i) { return i.type !== ''; });
            },

            get submitLabel() {
                var filled = this.items.filter(function (i) { return i.type !== ''; }).length;
                if (filled <= 1) return @js(__('app.suggest_submit'));
                return @js(__('app.suggest_submit_count', ['count' => '__N__'])).replace('__N__', filled);
            },

            placeholderFor: function (type) {
                return this.placeholders[type] || @js(__('app.suggest_title_label'));
            },

            addItem: function () {
                this.items.push({ id: this.nextId++, type: '', title: '', reference: '', author: '', content_detail: '' });
                var self = this;
                this.$nextTick(function () {
                    var cards = self.$el.querySelectorAll('[x-transition\\:enter]');
                    if (cards.length) cards[cards.length - 1].scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },

            removeItem: function (idx) {
                this.items.splice(idx, 1);
            },

            reset: function () {
                this.submitted = false;
                this.submitting = false;
                this.items = [{ id: this.nextId++, type: '', title: '', reference: '', author: '', content_detail: '' }];
            },
        };
    });
});
</script>
</body>
</html>

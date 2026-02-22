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
    @include('partials.seo-meta')
    <title>{{ __('app.suggest_page_title') }} — {{ __('app.app_name') }}</title>
    <script>(function(){var t=localStorage.getItem('theme');if(t!=='light')document.documentElement.classList.add('dark');})();</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-[100dvh] bg-surface font-sans antialiased">

<div x-data="suggestApp()" class="flex flex-col min-h-[100dvh]">

    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-40 bg-card/95 backdrop-blur-lg border-b border-border safe-top">
        <div class="flex items-center justify-between px-4 h-14 max-w-lg mx-auto">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('home') }}" class="p-1.5 -ml-1.5 rounded-lg hover:bg-muted transition touch-manipulation shrink-0">
                    <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <h1 class="text-base font-bold text-primary truncate">{{ __('app.suggest_page_title') }}</h1>
            </div>
            <div class="flex items-center gap-0.5 shrink-0">
                {{-- Cart badge --}}
                <button type="button" x-show="cart.length > 0 && step !== 'review'" x-cloak
                        @click="goStep('review')"
                        class="relative p-2 rounded-xl hover:bg-muted transition touch-manipulation">
                    <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] rounded-full bg-accent text-on-accent text-[10px] font-black flex items-center justify-center px-1" x-text="cart.length"></span>
                </button>
                @if($authUser)
                    <a href="{{ route('admin.suggestions.my') }}" class="p-2 rounded-xl hover:bg-muted transition touch-manipulation" title="{{ __('app.suggest_my_suggestions') }}">
                        <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                @endif
                <button type="button" @click="darkMode = !darkMode; localStorage.setItem('theme', darkMode ? 'dark' : 'light')" class="p-2 rounded-xl hover:bg-muted transition touch-manipulation">
                    <svg x-show="!darkMode" class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="darkMode" class="w-5 h-5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-cloak><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main class="flex-1 overflow-y-auto">
        <div class="max-w-lg mx-auto px-4 py-5 pb-8">

            {{-- ════════════════════════════════════════════════════════════════ --}}
            {{-- STEP: Pick language for this item                               --}}
            {{-- ════════════════════════════════════════════════════════════════ --}}
            <div x-show="step === 'lang'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-8 mt-4">
                    <h2 class="text-2xl font-black text-primary leading-tight">{{ __('app.suggest_page_subtitle') }}</h2>
                    <p class="text-sm text-muted-text mt-2">{{ __('app.suggest_step1_hint') }}</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" @click="draft.language = 'en'; goStep('type')"
                            class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 border-border bg-card hover:border-accent/40 active:scale-[0.96] transition touch-manipulation">
                        <span class="text-3xl">🇬🇧</span>
                        <span class="text-sm font-bold text-primary">English</span>
                    </button>
                    <button type="button" @click="draft.language = 'am'; goStep('type')"
                            class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 border-border bg-card hover:border-accent/40 active:scale-[0.96] transition touch-manipulation">
                        <span class="text-3xl">🇪🇹</span>
                        <span class="text-sm font-bold text-primary">አማርኛ</span>
                    </button>
                </div>

                {{-- Recent submissions from localStorage --}}
                <div x-show="history.length > 0" x-cloak class="mt-8">
                    <h3 class="text-xs font-bold text-muted-text uppercase tracking-widest mb-3">{{ __('app.suggest_your_recent') }}</h3>
                    <div class="space-y-2">
                        <template x-for="h in history" :key="h.time">
                            <div class="flex items-center gap-3 px-3.5 py-3 bg-card rounded-xl border border-border">
                                <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconFor(h.type)"/>
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-primary truncate" x-text="h.title"></p>
                                    <p class="text-[11px] text-muted-text" x-text="h.typeLabel + ' · ' + h.lang.toUpperCase() + ' · ' + timeAgo(h.time)"></p>
                                </div>
                                <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold uppercase shrink-0">{{ __('app.suggest_status_pending') }}</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════════════ --}}
            {{-- STEP: Pick content type                                         --}}
            {{-- ════════════════════════════════════════════════════════════════ --}}
            <div x-show="step === 'type'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">
                <div class="text-center mb-6 mt-2">
                    <h2 class="text-xl font-black text-primary">{{ __('app.suggest_type_placeholder') }}</h2>
                    <p class="text-xs text-muted-text mt-1" x-text="draft.language === 'am' ? 'አማርኛ' : 'English'"></p>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <template x-for="(t, idx) in types" :key="t.value">
                        <button type="button" @click="draft.type = t.value; goStep('fill')"
                                :class="[
                                    'flex flex-col items-center gap-2 p-5 rounded-2xl border-2 border-border bg-card hover:border-accent/50 hover:bg-accent/5 active:scale-[0.96] transition touch-manipulation',
                                    (types.length % 2 === 1 && idx === types.length - 1) ? 'col-span-2' : ''
                                ]">
                            <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center">
                                <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="t.icon"/>
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-primary" x-text="t.label"></span>
                        </button>
                    </template>
                </div>
                <button type="button" @click="goStep('lang')" class="w-full mt-5 py-2.5 text-sm font-medium text-muted-text hover:text-primary transition touch-manipulation text-center">
                    &larr; {{ __('app.suggest_language_label') }}
                </button>
            </div>

            {{-- ════════════════════════════════════════════════════════════════ --}}
            {{-- STEP: Fill in details for one item                              --}}
            {{-- ════════════════════════════════════════════════════════════════ --}}
            <div x-show="step === 'fill'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                {{-- Type badge + back --}}
                <div class="flex items-center justify-between mb-4">
                    <button type="button" @click="goStep('type')" class="flex items-center gap-1.5 text-sm font-medium text-muted-text hover:text-primary transition touch-manipulation">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        {{ __('app.back') }}
                    </button>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-1 rounded-lg bg-accent/10 text-accent text-xs font-bold" x-text="labelFor(draft.type)"></span>
                        <span class="text-xs font-bold text-muted-text uppercase" x-text="draft.language"></span>
                    </div>
                </div>

                <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 space-y-4">

                        {{-- Title --}}
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.suggest_title_label') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="draft.title" x-ref="titleInput"
                                   :placeholder="placeholderFor(draft.type)"
                                   maxlength="255"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                        </div>

                        {{-- Bible: verse range --}}
                        <div x-show="draft.type === 'bible'">
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.suggest_reference_label') }}
                            </label>
                            <input type="text" x-model="draft.reference"
                                   placeholder="{{ __('app.suggest_reference_ph') }}"
                                   maxlength="500"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                        </div>

                        {{-- Mezmur / Book: author --}}
                        <div x-show="draft.type === 'mezmur' || draft.type === 'book'">
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.suggest_author_label') }}
                            </label>
                            <input type="text" x-model="draft.author"
                                   placeholder="{{ __('app.suggest_author_ph') }}"
                                   maxlength="255"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                        </div>

                        {{-- URL / Link (mezmur, book, reference) --}}
                        <div x-show="draft.type === 'mezmur' || draft.type === 'book' || draft.type === 'reference'">
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.suggest_url_label') }}
                            </label>
                            <input type="text" x-model="draft.url"
                                   placeholder="{{ __('app.suggest_url_ph') }}"
                                   maxlength="500"
                                   class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                                {{ __('app.suggest_detail_label') }}
                            </label>
                            <textarea x-model="draft.detail" rows="2" maxlength="5000"
                                      placeholder="{{ __('app.suggest_detail_ph') }}"
                                      class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-base placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"></textarea>
                        </div>

                        {{-- Add to list --}}
                        <button type="button" @click="addToCart()"
                                :disabled="!draft.title.trim()"
                                :class="draft.title.trim() ? 'bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97]' : 'bg-muted text-muted-text cursor-not-allowed'"
                                class="w-full h-12 rounded-xl font-bold text-base transition touch-manipulation flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span x-text="cart.length === 0 ? @js(__('app.suggest_submit')) : @js(__('app.suggest_add_item'))"></span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ════════════════════════════════════════════════════════════════ --}}
            {{-- STEP: Review cart & submit                                      --}}
            {{-- ════════════════════════════════════════════════════════════════ --}}
            <div x-show="step === 'review'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0">

                {{-- Validation errors --}}
                @if($errors->any())
                    <div class="p-3 mb-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                        <ul class="space-y-1">
                            @foreach($errors->all() as $error)
                                <li class="text-xs text-red-700 dark:text-red-400">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-primary">{{ __('app.suggest_your_list') }}</h2>
                    <span class="text-xs font-bold text-muted-text" x-text="cart.length + ' item' + (cart.length !== 1 ? 's' : '')"></span>
                </div>

                {{-- Cart items --}}
                <div class="space-y-2 mb-4">
                    <template x-for="(item, idx) in cart" :key="idx">
                        <div class="flex items-center gap-3 px-3.5 py-3 bg-card rounded-xl border border-border">
                            <div class="w-9 h-9 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconFor(item.type)"/>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-primary truncate" x-text="item.title"></p>
                                <p class="text-[11px] text-muted-text" x-text="labelFor(item.type) + ' · ' + (item.language === 'am' ? 'አማርኛ' : 'EN')"></p>
                            </div>
                            <button type="button" @click="cart.splice(idx, 1)" class="p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition touch-manipulation shrink-0">
                                <svg class="w-4 h-4 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Add more --}}
                <button type="button" @click="goStep('lang')"
                        class="w-full flex items-center justify-center gap-2 py-3 rounded-xl border-2 border-dashed border-border text-sm font-semibold text-muted-text hover:border-accent/40 hover:text-accent active:scale-[0.98] transition touch-manipulation mb-4">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('app.suggest_add_more') }}
                </button>

                {{-- Name (non-logged-in users) --}}
                @if(!$authUser)
                    <div class="bg-card rounded-xl border border-border px-4 py-3 mb-4">
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_submitter_label') }}
                        </label>
                        <input type="text" x-model="name"
                               placeholder="{{ __('app.suggest_submitter_ph') }}"
                               maxlength="100"
                               class="w-full h-11 px-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                    </div>
                @endif

                {{-- Submit all --}}
                <form method="POST" action="{{ route('suggest.store') }}" x-ref="submitForm" @submit="onSubmit()">
                    @csrf
                    <input type="hidden" name="submitter_name" :value="name">
                    {{-- Dynamically inject cart items as hidden fields --}}
                    <template x-for="(item, idx) in cart" :key="'f'+idx">
                        <div>
                            <input type="hidden" :name="'items['+idx+'][type]'" :value="item.type">
                            <input type="hidden" :name="'items['+idx+'][language]'" :value="item.language">
                            <input type="hidden" :name="'items['+idx+'][title]'" :value="item.title">
                            <input type="hidden" :name="'items['+idx+'][reference]'" :value="item.reference">
                            <input type="hidden" :name="'items['+idx+'][author]'" :value="item.author">
                            <input type="hidden" :name="'items['+idx+'][content_detail]'" :value="item.detail">
                        </div>
                    </template>

                    <button type="submit" :disabled="cart.length === 0 || submitting"
                            :class="cart.length > 0 && !submitting ? 'bg-accent text-on-accent hover:bg-accent-hover active:scale-[0.97]' : 'bg-muted text-muted-text cursor-not-allowed'"
                            class="w-full h-12 rounded-xl font-bold text-base transition touch-manipulation flex items-center justify-center gap-2">
                        <template x-if="submitting">
                            <svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                            </svg>
                        </template>
                        <template x-if="!submitting">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <span x-text="cart.length <= 1 ? @js(__('app.suggest_submit')) : @js(__('app.suggest_submit_count', ['count' => '__N__'])).replace('__N__', cart.length)"></span>
                    </button>
                </form>
            </div>

            {{-- ════════════════════════════════════════════════════════════════ --}}
            {{-- STEP: Success                                                   --}}
            {{-- ════════════════════════════════════════════════════════════════ --}}
            <div x-show="step === 'done'" x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100">
                <div class="mt-8 text-center space-y-5">
                    <div class="mx-auto w-20 h-20 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                        <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-primary">{{ __('app.suggest_success_title') }}</h2>
                    <p class="text-sm text-muted-text leading-relaxed max-w-xs mx-auto" x-text="successMsg"></p>
                    <div class="flex flex-col gap-2.5 max-w-xs mx-auto">
                        <button type="button" @click="resetAll()"
                                class="w-full h-12 rounded-xl bg-accent text-on-accent font-bold text-sm hover:bg-accent-hover active:scale-[0.97] transition touch-manipulation flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('app.suggest_another') }}
                        </button>
                        @if($authUser)
                            <a href="{{ route('admin.suggestions.my') }}"
                               class="w-full h-12 rounded-xl border border-border text-sm font-semibold text-secondary hover:bg-muted active:scale-[0.97] transition touch-manipulation flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ __('app.suggest_my_suggestions') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('suggestApp', function () {
        var successCount = {{ session('success', 0) }};
        var stored = [];
        try { stored = JSON.parse(localStorage.getItem('suggest_history') || '[]'); } catch(e) {}
        return {
            step: successCount > 0 ? 'done' : 'lang',
            name: @js($authUser?->name ?? ''),
            submitting: false,
            history: stored,
            successCount: successCount,

            draft: { language: 'en', type: '', title: '', reference: '', author: '', url: '', detail: '' },
            cart: [],

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

            get successMsg() {
                if (this.successCount === 1) return @js(__('app.suggest_success_body'));
                return @js(__('app.suggest_success_body_plural', ['count' => '__N__'])).replace('__N__', this.successCount);
            },

            labelFor: function (type) {
                var found = this.types.find(function (t) { return t.value === type; });
                return found ? found.label : '';
            },

            placeholderFor: function (type) {
                return this.placeholders[type] || @js(__('app.suggest_title_label'));
            },

            iconFor: function (type) {
                var found = this.types.find(function (t) { return t.value === type; });
                return found ? found.icon : '';
            },

            goStep: function (s) {
                this.step = s;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                if (s === 'fill') {
                    var self = this;
                    this.$nextTick(function () {
                        if (self.$refs.titleInput) self.$refs.titleInput.focus();
                    });
                }
            },

            addToCart: function () {
                if (!this.draft.title.trim()) return;
                var ref = this.draft.type === 'bible' ? this.draft.reference : this.draft.url;
                this.cart.push({
                    type: this.draft.type,
                    language: this.draft.language,
                    title: this.draft.title,
                    reference: ref,
                    author: this.draft.author,
                    detail: this.draft.detail,
                });
                this.draft = { language: this.draft.language, type: '', title: '', reference: '', author: '', url: '', detail: '' };
                this.goStep('review');
            },

            onSubmit: function () {
                this.saveToHistory();
                this.submitting = true;
            },

            saveToHistory: function () {
                var self = this;
                this.cart.forEach(function (item) {
                    self.history.unshift({
                        type: item.type,
                        typeLabel: self.labelFor(item.type),
                        title: item.title,
                        lang: item.language,
                        time: Date.now(),
                    });
                });
                if (this.history.length > 20) this.history = this.history.slice(0, 20);
                try { localStorage.setItem('suggest_history', JSON.stringify(this.history)); } catch(e) {}
            },

            resetAll: function () {
                this.cart = [];
                this.draft = { language: 'en', type: '', title: '', reference: '', author: '', url: '', detail: '' };
                this.submitting = false;
                this.goStep('lang');
            },

            timeAgo: function (ts) {
                var diff = Math.floor((Date.now() - ts) / 1000);
                if (diff < 60) return 'just now';
                if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
                return Math.floor(diff / 86400) + 'd ago';
            },
        };
    });
});
</script>
</body>
</html>

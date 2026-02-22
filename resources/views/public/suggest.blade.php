@extends('layouts.member-guest')

@section('title', __('app.suggest_page_title') . ' — ' . __('app.app_name'))

@section('content')
<div
    x-data="{
        type: '{{ old('type', '') }}',
        language: '{{ old('language', 'en') }}',
        submitted: {{ session('success') ? 'true' : 'false' }},

        get titlePlaceholder() {
            const map = {
                bible:     '{{ __('app.suggest_title_bible') }}',
                mezmur:    '{{ __('app.suggest_title_mezmur') }}',
                sinksar:   '{{ __('app.suggest_title_sinksar') }}',
                book:      '{{ __('app.suggest_title_book') }}',
                reference: '{{ __('app.suggest_title_reference') }}',
            };
            return map[this.type] || '{{ __('app.suggest_title_label') }}';
        }
    }"
    class="space-y-4"
>
    {{-- ── Success State ─────────────────────────────── --}}
    <div x-show="submitted" x-cloak class="bg-card rounded-2xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden">
        <div class="px-5 py-8 sm:px-7 text-center space-y-4">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-black text-primary">{{ __('app.suggest_success_title') }}</h2>
            <p class="text-sm text-muted-text leading-relaxed">{{ __('app.suggest_success_body') }}</p>
            <button
                type="button"
                @click="submitted = false; type = ''; language = 'en'"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-accent text-on-accent rounded-xl font-semibold text-sm hover:bg-accent-hover transition touch-manipulation"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('app.suggest_another') }}
            </button>
        </div>
    </div>

    {{-- ── Form Card ──────────────────────────────────── --}}
    <div x-show="!submitted" class="bg-card rounded-2xl sm:rounded-3xl shadow-2xl shadow-black/10 dark:shadow-black/30 border border-border overflow-hidden">
        <div class="px-5 py-6 sm:px-7 sm:py-7 space-y-5">

            {{-- Header --}}
            <div>
                <p class="text-[11px] font-bold text-muted-text uppercase tracking-widest">
                    {{ __('app.app_name') }}
                </p>
                <h1 class="text-xl sm:text-2xl font-black text-primary mt-1 leading-tight">
                    {{ __('app.suggest_page_title') }}
                </h1>
                <p class="text-sm text-muted-text mt-1 leading-relaxed">
                    {{ __('app.suggest_page_subtitle') }}
                </p>
            </div>

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

            <form method="POST" action="{{ route('suggest.store') }}" class="space-y-5">
                @csrf

                {{-- ── Row 1: Content Type ─────────────────────── --}}
                <div>
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                        {{ __('app.suggest_type_label') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach([
                            'bible'     => ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'label' => __('app.suggest_type_bible')],
                            'mezmur'    => ['icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3', 'label' => __('app.suggest_type_mezmur')],
                            'sinksar'   => ['icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z', 'label' => __('app.suggest_type_sinksar')],
                            'book'      => ['icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z', 'label' => __('app.suggest_type_book')],
                            'reference' => ['icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1', 'label' => __('app.suggest_type_reference')],
                        ] as $value => $cfg)
                            <button
                                type="button"
                                @click="type = '{{ $value }}'"
                                :class="type === '{{ $value }}'
                                    ? 'bg-accent/10 border-accent text-accent font-semibold'
                                    : 'border-border text-muted-text hover:border-accent/40 hover:text-primary'"
                                class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl border text-xs text-center transition touch-manipulation"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $cfg['icon'] }}"/>
                                </svg>
                                <span>{{ $cfg['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    {{-- Hidden input that carries the value --}}
                    <input type="hidden" name="type" :value="type">
                </div>

                {{-- ── Content Language ────────────────────────── --}}
                <div>
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                        {{ __('app.suggest_language_label') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="language = 'en'"
                            :class="language === 'en'
                                ? 'bg-accent/10 border-accent text-accent font-semibold'
                                : 'border-border text-muted-text hover:border-accent/40'"
                            class="flex-1 py-2.5 px-4 rounded-xl border text-sm text-center transition touch-manipulation"
                        >
                            {{ __('app.suggest_language_en') }}
                        </button>
                        <button
                            type="button"
                            @click="language = 'am'"
                            :class="language === 'am'
                                ? 'bg-accent/10 border-accent text-accent font-semibold'
                                : 'border-border text-muted-text hover:border-accent/40'"
                            class="flex-1 py-2.5 px-4 rounded-xl border text-sm text-center transition touch-manipulation"
                        >
                            {{ __('app.suggest_language_am') }}
                        </button>
                    </div>
                    <input type="hidden" name="language" :value="language">
                </div>

                {{-- ── Fields (visible once a type is picked) ───── --}}
                <div x-show="type !== ''" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">

                    {{-- Submitter name --}}
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_submitter_label') }}
                        </label>
                        <input
                            type="text"
                            name="submitter_name"
                            value="{{ old('submitter_name') }}"
                            placeholder="{{ __('app.suggest_submitter_ph') }}"
                            maxlength="100"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition"
                        >
                    </div>

                    {{-- Title / Name (dynamic placeholder) --}}
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_title_label') }}
                        </label>
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title') }}"
                            :placeholder="titlePlaceholder"
                            maxlength="255"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition"
                        >
                    </div>

                    {{-- Bible-only: Verse range --}}
                    <div x-show="type === 'bible'" x-cloak>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_reference_label') }}
                        </label>
                        <input
                            type="text"
                            name="reference"
                            value="{{ old('reference') }}"
                            placeholder="{{ __('app.suggest_reference_ph') }}"
                            maxlength="500"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition"
                        >
                    </div>

                    {{-- Mezmur / Book / Reference: Author or URL --}}
                    <div x-show="type === 'mezmur' || type === 'book'" x-cloak>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_author_label') }}
                        </label>
                        <input
                            type="text"
                            name="author"
                            value="{{ old('author') }}"
                            placeholder="{{ __('app.suggest_author_ph') }}"
                            maxlength="255"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition"
                        >
                    </div>

                    {{-- Mezmur / Book / Reference: URL --}}
                    <div x-show="type === 'mezmur' || type === 'book' || type === 'reference'" x-cloak>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_url_label') }}
                        </label>
                        <input
                            type="url"
                            name="reference"
                            value="{{ old('reference') }}"
                            placeholder="{{ __('app.suggest_url_ph') }}"
                            maxlength="500"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition"
                        >
                    </div>

                    {{-- Description / Detail --}}
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_detail_label') }}
                        </label>
                        <textarea
                            name="content_detail"
                            rows="3"
                            maxlength="5000"
                            placeholder="{{ __('app.suggest_detail_ph') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"
                        >{{ old('content_detail') }}</textarea>
                    </div>

                    {{-- Notes --}}
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1.5">
                            {{ __('app.suggest_notes_label') }}
                        </label>
                        <textarea
                            name="notes"
                            rows="2"
                            maxlength="2000"
                            placeholder="{{ __('app.suggest_notes_ph') }}"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/60 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-none"
                        >{{ old('notes') }}</textarea>
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full py-3 px-5 bg-accent text-on-accent rounded-xl font-bold text-sm hover:bg-accent-hover active:scale-[0.98] transition touch-manipulation flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        {{ __('app.suggest_submit') }}
                    </button>
                </div>

                {{-- Prompt to pick a type first --}}
                <div x-show="type === ''" class="py-6 text-center text-sm text-muted-text">
                    <svg class="w-8 h-8 mx-auto mb-2 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/>
                    </svg>
                    {{ __('app.suggest_type_placeholder') }}
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

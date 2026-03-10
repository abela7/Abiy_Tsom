@extends('layouts.admin')

@section('title', __('app.advanced_suggest_title'))

@section('content')
<div x-data="advancedSuggest()" class="max-w-2xl mx-auto">

    {{-- ── Header ────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-primary">{{ __('app.advanced_suggest_title') }}</h1>
            <p class="text-sm text-muted-text mt-0.5">{{ __('app.advanced_suggest_subtitle') }}</p>
        </div>
        <a href="{{ route('admin.suggestions.my') }}"
           class="text-sm font-medium text-muted-text hover:text-primary transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            {{ __('app.suggest_my_suggestions') }}
        </a>
    </div>

    {{-- ── Progress bar ───────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 mb-6">
        <template x-for="(s, i) in allSteps" :key="s">
            <div class="flex items-center gap-2" :class="i < allSteps.length - 1 ? 'flex-1' : ''">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition"
                     :class="stepIndex > i ? 'bg-accent text-on-accent' : (stepIndex === i ? 'bg-accent/20 text-accent ring-2 ring-accent' : 'bg-muted text-muted-text')">
                    <template x-if="stepIndex > i">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="stepIndex <= i">
                        <span x-text="i + 1"></span>
                    </template>
                </div>
                <div x-show="i < allSteps.length - 1" class="flex-1 h-0.5 bg-border rounded-full">
                    <div class="h-full bg-accent rounded-full transition-all duration-300"
                         :style="stepIndex > i ? 'width:100%' : 'width:0%'"></div>
                </div>
            </div>
        </template>
    </div>

    {{-- ── Card ───────────────────────────────────────────────────────────── --}}
    <div class="bg-card border border-border rounded-2xl shadow-sm overflow-hidden">

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_area                                                  --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_area'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_area') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-1 gap-2">
                @php
                    $areas = [
                        ['value' => 'synaxarium_celebration', 'label' => __('app.telegram_suggest_area_synaxarium_celebration'), 'icon' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'],
                        ['value' => 'bible_reading',          'label' => __('app.telegram_suggest_area_bible_reading'), 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['value' => 'lectionary',             'label' => __('app.telegram_suggest_area_lectionary'), 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['value' => 'mezmur',                 'label' => __('app.telegram_suggest_area_mezmur'), 'icon' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3'],
                        ['value' => 'synaxarium',             'label' => __('app.telegram_suggest_area_sinksar'), 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                        ['value' => 'spiritual_book',         'label' => __('app.telegram_suggest_area_spiritual_book'), 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
                        ['value' => 'reference_resource',     'label' => __('app.telegram_suggest_area_reference_resource'), 'icon' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'],
                        ['value' => 'daily_message',          'label' => __('app.telegram_suggest_area_daily_message'), 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                    ];
                @endphp
                @foreach($areas as $area)
                    <button type="button"
                            @click="setArea('{{ $area['value'] }}')"
                            :class="form.content_area === '{{ $area['value'] }}' ? 'border-accent bg-accent/5 text-accent' : 'border-border hover:border-accent/40 text-primary'"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition text-sm font-medium text-left">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $area['icon'] }}"/></svg>
                        {{ $area['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_scope (synaxarium / synaxarium_celebration)           --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_scope'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_scope') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-2 gap-3">
                <button type="button" @click="form.entry_scope = 'yearly'; nextStep()"
                        :class="form.entry_scope === 'yearly' ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 transition">
                    <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-sm font-semibold text-primary">{{ __('app.telegram_suggest_scope_yearly') }}</span>
                </button>
                <button type="button" @click="form.entry_scope = 'monthly'; nextStep()"
                        :class="form.entry_scope === 'monthly' ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="flex flex-col items-center gap-2 p-5 rounded-xl border-2 transition">
                    <svg class="w-7 h-7 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span class="text-sm font-semibold text-primary">{{ __('app.telegram_suggest_scope_monthly') }}</span>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_month                                                 --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_month'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_month') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-2 gap-2">
                @php
                    $months = [
                        1 => 'Meskerem / መስከረም', 2 => 'Tikimt / ጥቅምት', 3 => 'Hidar / ኅዳር',
                        4 => 'Tahsas / ታኅሣሥ', 5 => 'Tir / ጥር', 6 => 'Yekatit / የካቲት',
                        7 => 'Megabit / መጋቢት', 8 => 'Miyazia / ሚያዝያ', 9 => 'Ginbot / ግንቦት',
                        10 => 'Sene / ሰኔ', 11 => 'Hamle / ሐምሌ', 12 => 'Nehase / ነሐሴ',
                        13 => 'Pagumen / ጳጉሜን',
                    ];
                @endphp
                @foreach($months as $num => $label)
                    <button type="button"
                            @click="form.ethiopian_month = {{ $num }}; nextStep()"
                            :class="form.ethiopian_month === {{ $num }} ? 'border-accent bg-accent/5 text-accent' : 'border-border hover:border-accent/40 text-primary'"
                            class="px-3 py-2.5 rounded-xl border-2 text-xs font-medium transition text-left">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_day                                                   --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_day'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_day') }}</h2>
                <p class="text-xs text-muted-text" x-text="monthName(form.ethiopian_month)"></p>
            </div>
            <div class="px-5 pb-5 grid grid-cols-5 gap-2">
                <template x-for="d in maxDaysForMonth()" :key="d">
                    <button type="button"
                            @click="form.ethiopian_day = d; nextStep()"
                            :class="form.ethiopian_day === d ? 'bg-accent text-on-accent' : 'bg-muted hover:bg-accent/10 text-primary'"
                            class="h-10 rounded-xl text-sm font-semibold transition">
                        <span x-text="d"></span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_lectionary_section                                    --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_lectionary_section'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_lectionary_section') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-1 gap-2">
                @php
                    $sections = [
                        'title_description' => __('app.telegram_suggest_lectionary_section_title_description'),
                        'pauline'           => __('app.telegram_suggest_lectionary_section_pauline'),
                        'catholic'          => __('app.telegram_suggest_lectionary_section_catholic'),
                        'acts'              => __('app.telegram_suggest_lectionary_section_acts'),
                        'mesbak'            => __('app.telegram_suggest_lectionary_section_mesbak'),
                        'gospel'            => __('app.telegram_suggest_lectionary_section_gospel'),
                        'qiddase'           => __('app.telegram_suggest_lectionary_section_qiddase'),
                    ];
                @endphp
                @foreach($sections as $key => $label)
                    <button type="button"
                            @click="form.lectionary_section = '{{ $key }}'; nextStep()"
                            :class="form.lectionary_section === '{{ $key }}' ? 'border-accent bg-accent/5 text-accent' : 'border-border hover:border-accent/40 text-primary'"
                            class="px-4 py-3 rounded-xl border-2 text-sm font-medium transition text-left">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_resource_type                                         --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_resource_type'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_resource_type') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-3 gap-3">
                @php
                    $resourceTypes = [
                        'video'   => ['label' => __('app.telegram_suggest_resource_type_video'),   'icon' => 'M15 10l4.553-2.069A1 1 0 0121 8.882v6.236a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z'],
                        'website' => ['label' => __('app.telegram_suggest_resource_type_website'), 'icon' => 'M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a15.3 15.3 0 010 18M12 3a15.3 15.3 0 000 18'],
                        'file'    => ['label' => __('app.telegram_suggest_resource_type_file'),    'icon' => 'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ];
                @endphp
                @foreach($resourceTypes as $key => $rt)
                    <button type="button"
                            @click="form.resource_type = '{{ $key }}'; nextStep()"
                            :class="form.resource_type === '{{ $key }}' ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                            class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $rt['icon'] }}"/></svg>
                        <span class="text-xs font-semibold text-primary">{{ $rt['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_first_language                                        --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_first_language'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_first_language') }}</h2>
                <p class="text-xs text-muted-text">{{ __('app.advanced_suggest_bilingual_hint') }}</p>
            </div>
            <div class="px-5 pb-5 grid grid-cols-2 gap-3">
                <button type="button"
                        @click="form.first_language = 'en'; currentLang = 'en'; nextStep()"
                        :class="form.first_language === 'en' ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition">
                    <span class="text-3xl">🇬🇧</span>
                    <span class="text-sm font-bold text-primary">English</span>
                </button>
                <button type="button"
                        @click="form.first_language = 'am'; currentLang = 'am'; nextStep()"
                        :class="form.first_language === 'am' ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="flex flex-col items-center gap-3 p-6 rounded-2xl border-2 transition">
                    <span class="text-3xl">🇪🇹</span>
                    <span class="text-sm font-bold text-primary">አማርኛ</span>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: content fields (dynamic, handles all bilingual text steps)  --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="isFieldStep(step)" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-4">
                <div class="flex items-center justify-between mb-1">
                    <h2 class="text-base font-bold text-primary" x-text="fieldStepLabel()"></h2>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-lg"
                          :class="currentLang === 'am' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400'"
                          x-text="currentLang === 'am' ? '🇪🇹 አማርኛ' : '🇬🇧 English'">
                    </span>
                </div>
                <p class="text-xs text-muted-text" x-show="isOptionalField(step)">{{ __('app.advanced_suggest_optional_hint') }}</p>
            </div>
            <div class="px-5 pb-5">
                {{-- Single-line fields --}}
                <template x-if="step === 'enter_title'">
                    <input type="text" x-model="form['title_' + currentLang]" x-ref="fieldInput" @keydown.enter="nextStep()" maxlength="500" :placeholder="fieldStepPlaceholder()" class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                </template>
                <template x-if="step === 'enter_url'">
                    <input type="text" x-model="form['url_' + currentLang]" x-ref="fieldInput" @keydown.enter="nextStep()" maxlength="1000" placeholder="https://" class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                </template>
                <template x-if="step === 'enter_reference'">
                    <input type="text" x-model="form['reference_' + currentLang]" x-ref="fieldInput" @keydown.enter="nextStep()" maxlength="500" :placeholder="fieldStepPlaceholder()" class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                </template>
                <template x-if="step === 'enter_sort_order'">
                    <input type="number" x-model="form.sort_order" x-ref="fieldInput" @keydown.enter="nextStep()" min="1" max="999" placeholder="1" class="w-full h-12 px-4 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                </template>
                {{-- Multi-line fields --}}
                <template x-if="step === 'enter_content_detail'">
                    <textarea x-model="form['content_detail_' + currentLang]" x-ref="fieldInput" rows="5" maxlength="10000" :placeholder="fieldStepPlaceholder()" class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-y"></textarea>
                </template>
                <template x-if="step === 'enter_lyrics'">
                    <textarea x-model="form['lyrics_' + currentLang]" x-ref="fieldInput" rows="5" maxlength="10000" :placeholder="fieldStepPlaceholder()" class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-y"></textarea>
                </template>
                <template x-if="step === 'enter_text'">
                    <textarea x-model="form['text_' + currentLang]" x-ref="fieldInput" rows="5" maxlength="10000" :placeholder="fieldStepPlaceholder()" class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-y"></textarea>
                </template>
                <template x-if="step === 'enter_summary'">
                    <textarea x-model="form['summary_' + currentLang]" x-ref="fieldInput" rows="3" maxlength="5000" :placeholder="fieldStepPlaceholder()" class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-primary text-sm placeholder:text-muted-text/50 focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-y"></textarea>
                </template>
                {{-- Bible reading: reference + summary + text extra fields --}}
                <template x-if="form.content_area === 'bible_reading' && step === 'enter_reference'">
                    <div class="mt-3 space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.telegram_suggest_enter_bible_summary') }}</label>
                            <input type="text" x-model="form['summary_' + currentLang]" maxlength="500"
                                   class="w-full h-11 px-4 rounded-xl border border-border bg-surface text-sm text-primary focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-muted-text uppercase tracking-widest mb-1">{{ __('app.telegram_suggest_enter_bible_text') }}</label>
                            <textarea x-model="form['text_' + currentLang]" rows="3" maxlength="10000"
                                      class="w-full px-4 py-3 rounded-xl border border-border bg-surface text-sm text-primary focus:outline-none focus:ring-2 focus:ring-accent/40 focus:border-accent transition resize-y"></textarea>
                        </div>
                    </div>
                </template>
                <div class="mt-4 flex items-center gap-2">
                    <button type="button" @click="nextStep()"
                            :class="isOptionalField(step) ? 'border border-border text-muted-text hover:text-primary hover:border-accent/40' : 'bg-accent text-on-accent hover:bg-accent-hover'"
                            class="flex-1 h-11 rounded-xl text-sm font-semibold transition active:scale-[0.97]"
                            x-text="isOptionalField(step) ? @js(__('app.advanced_suggest_skip_or_continue')) : @js(__('app.advanced_suggest_next'))">
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: offer_other_language                                         --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'offer_other_language'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-4">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.advanced_suggest_other_lang_title') }}</h2>
                <p class="text-sm text-muted-text" x-text="otherLangPrompt()"></p>
            </div>
            <div class="px-5 pb-5 flex flex-col gap-2">
                <button type="button" @click="switchToOtherLanguage()"
                        class="w-full h-12 rounded-xl bg-accent text-on-accent font-semibold text-sm hover:bg-accent-hover transition active:scale-[0.97]"
                        x-text="otherLangAddLabel()">
                </button>
                <button type="button" @click="goStep('preview')"
                        class="w-full h-11 rounded-xl border border-border text-sm font-medium text-muted-text hover:text-primary hover:border-accent/40 transition active:scale-[0.97]">
                    {{ __('app.telegram_suggest_skip_other_lang') }}
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: choose_main (synaxarium_celebration only)                    --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'choose_main'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-2">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.telegram_suggest_choose_main_celebration') }}</h2>
            </div>
            <div class="px-5 pb-5 grid grid-cols-2 gap-3">
                <button type="button" @click="form.is_main = 1; nextStep()"
                        :class="form.is_main === 1 ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="py-4 rounded-xl border-2 text-sm font-semibold text-primary transition">
                    {{ __('app.yes') }}
                </button>
                <button type="button" @click="form.is_main = 0; nextStep()"
                        :class="form.is_main === 0 ? 'border-accent bg-accent/5' : 'border-border hover:border-accent/40'"
                        class="py-4 rounded-xl border-2 text-sm font-semibold text-primary transition">
                    {{ __('app.no') }}
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════ --}}
        {{-- STEP: preview                                                      --}}
        {{-- ══════════════════════════════════════════════════════════════════ --}}
        <div x-show="step === 'preview'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-3" x-transition:enter-end="opacity-100 translate-x-0">
            <div class="px-5 pt-5 pb-3">
                <h2 class="text-base font-bold text-primary mb-1">{{ __('app.advanced_suggest_preview_title') }}</h2>
                <p class="text-xs text-muted-text">{{ __('app.advanced_suggest_preview_hint') }}</p>
            </div>
            <div class="px-5 pb-5 space-y-3">
                {{-- Area / date summary --}}
                <div class="bg-surface rounded-xl border border-border px-4 py-3 space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-text font-medium">{{ __('app.advanced_suggest_field_area') }}</span>
                        <span class="text-primary font-semibold" x-text="areaLabel()"></span>
                    </div>
                    <template x-if="form.ethiopian_month && form.ethiopian_day">
                        <div class="flex justify-between">
                            <span class="text-muted-text font-medium">{{ __('app.advanced_suggest_field_date') }}</span>
                            <span class="text-primary font-semibold" x-text="monthName(form.ethiopian_month) + ' ' + form.ethiopian_day"></span>
                        </div>
                    </template>
                    <template x-if="form.entry_scope">
                        <div class="flex justify-between">
                            <span class="text-muted-text font-medium">{{ __('app.advanced_suggest_field_scope') }}</span>
                            <span class="text-primary font-semibold" x-text="form.entry_scope === 'yearly' ? @js(__('app.telegram_suggest_scope_yearly')) : @js(__('app.telegram_suggest_scope_monthly'))"></span>
                        </div>
                    </template>
                    <template x-if="form.lectionary_section">
                        <div class="flex justify-between">
                            <span class="text-muted-text font-medium">{{ __('app.advanced_suggest_field_section') }}</span>
                            <span class="text-primary font-semibold" x-text="lectionarySectionLabel()"></span>
                        </div>
                    </template>
                    <template x-if="form.resource_type">
                        <div class="flex justify-between">
                            <span class="text-muted-text font-medium">{{ __('app.advanced_suggest_field_resource_type') }}</span>
                            <span class="text-primary font-semibold" x-text="resourceTypeLabel()"></span>
                        </div>
                    </template>
                </div>

                {{-- Bilingual content fields --}}
                <template x-for="lang in ['en', 'am']" :key="lang">
                    <div x-show="hasAnyLangContent(lang)" class="bg-surface rounded-xl border border-border px-4 py-3 space-y-1.5">
                        <div class="text-xs font-bold text-muted-text uppercase tracking-widest mb-2"
                             x-text="lang === 'en' ? '🇬🇧 English' : '🇪🇹 አማርኛ'"></div>
                        <template x-for="field in ['title', 'reference', 'url', 'content_detail', 'lyrics', 'text', 'summary']" :key="field">
                            <div x-show="form[field + '_' + lang]" class="text-sm">
                                <span class="text-muted-text font-medium capitalize" x-text="fieldLabel(field)"></span>:
                                <span class="text-primary ml-1" x-text="truncate(form[field + '_' + lang], 120)"></span>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Submit --}}
                <form method="POST" action="{{ route('admin.advanced-suggestions.store') }}" x-ref="submitForm" @submit="submitting = true">
                    @csrf
                    <input type="hidden" name="content_area" :value="form.content_area">
                    <input type="hidden" name="ethiopian_month" :value="form.ethiopian_month || ''">
                    <input type="hidden" name="ethiopian_day" :value="form.ethiopian_day || ''">
                    <input type="hidden" name="entry_scope" :value="form.entry_scope || ''">
                    <input type="hidden" name="first_language" :value="form.first_language || ''">
                    <input type="hidden" name="lectionary_section" :value="form.lectionary_section || ''">
                    <input type="hidden" name="resource_type" :value="form.resource_type || ''">
                    <input type="hidden" name="is_main" :value="form.is_main !== null ? form.is_main : ''">
                    <input type="hidden" name="sort_order" :value="form.sort_order || ''">
                    <template x-for="lang in ['en', 'am']" :key="'f'+lang">
                        <div>
                            <template x-for="field in ['title', 'reference', 'url', 'content_detail', 'lyrics', 'text', 'summary']" :key="field">
                                <input type="hidden" :name="field + '_' + lang" :value="form[field + '_' + lang] || ''">
                            </template>
                        </div>
                    </template>

                    <div class="mt-4 flex flex-col gap-2">
                        <button type="submit" :disabled="submitting"
                                :class="!submitting ? 'bg-accent text-on-accent hover:bg-accent-hover' : 'bg-muted text-muted-text cursor-not-allowed'"
                                class="w-full h-12 rounded-xl font-bold text-sm transition active:scale-[0.97] flex items-center justify-center gap-2">
                            <template x-if="submitting">
                                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/>
                                    <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="3" stroke-linecap="round" class="opacity-75"/>
                                </svg>
                            </template>
                            <svg x-show="!submitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('app.advanced_suggest_submit') }}
                        </button>
                        <button type="button" @click="goStep(previousStep())"
                                class="w-full h-10 rounded-xl border border-border text-sm font-medium text-muted-text hover:text-primary hover:border-accent/40 transition">
                            ← {{ __('app.back') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Back / Navigation bar (shown on non-entry steps except choose_area) --}}
        <div x-show="step !== 'choose_area' && step !== 'preview' && !isFieldStep(step) && step !== 'offer_other_language' && step !== 'choose_main'" x-cloak
             class="border-t border-border px-5 py-3 flex justify-between">
            <button type="button" @click="prevStep()"
                    class="text-sm font-medium text-muted-text hover:text-primary transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('app.back') }}
            </button>
            <button type="button" @click="resetAll()"
                    class="text-sm font-medium text-error hover:opacity-70 transition">
                {{ __('app.cancel') }}
            </button>
        </div>

        {{-- Back bar for field steps --}}
        <div x-show="isFieldStep(step)" x-cloak
             class="border-t border-border px-5 py-3 flex justify-between">
            <button type="button" @click="prevStep()"
                    class="text-sm font-medium text-muted-text hover:text-primary transition flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('app.back') }}
            </button>
            <button type="button" @click="resetAll()"
                    class="text-sm font-medium text-error hover:opacity-70 transition">
                {{ __('app.cancel') }}
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('advancedSuggest', function () {
        // Step definitions per content area
        var FLOWS = {
            bible_reading:          ['choose_area', 'choose_month', 'choose_day', 'choose_first_language', 'enter_reference', 'offer_other_language', 'preview'],
            mezmur:                 ['choose_area', 'choose_month', 'choose_day', 'choose_first_language', 'enter_title', 'enter_url', 'enter_content_detail', 'enter_lyrics', 'offer_other_language', 'preview'],
            synaxarium:             ['choose_area', 'choose_month', 'choose_day', 'choose_first_language', 'enter_title', 'enter_url', 'enter_text', 'enter_content_detail', 'offer_other_language', 'preview'],
            synaxarium_celebration: ['choose_area', 'choose_scope', 'choose_month', 'choose_day', 'choose_first_language', 'enter_title', 'enter_content_detail', 'offer_other_language', 'choose_main', 'enter_sort_order', 'preview'],
            lectionary:             ['choose_area', 'choose_month', 'choose_day', 'choose_lectionary_section', 'choose_first_language', 'enter_title', 'enter_content_detail', 'offer_other_language', 'preview'],
            spiritual_book:         ['choose_area', 'choose_month', 'choose_day', 'choose_first_language', 'enter_title', 'enter_url', 'enter_content_detail', 'offer_other_language', 'preview'],
            reference_resource:     ['choose_area', 'choose_month', 'choose_day', 'choose_resource_type', 'choose_first_language', 'enter_title', 'enter_url', 'enter_content_detail', 'offer_other_language', 'preview'],
            daily_message:          ['choose_area', 'choose_month', 'choose_day', 'choose_first_language', 'enter_title', 'enter_content_detail', 'offer_other_language', 'preview'],
        };

        // synaxarium_celebration with scope=monthly skips choose_month
        function flowFor(form) {
            var base = FLOWS[form.content_area] || ['choose_area'];
            if (form.content_area === 'synaxarium_celebration' && form.entry_scope === 'monthly') {
                return base.filter(function (s) { return s !== 'choose_month'; });
            }
            return base;
        }

        var FIELD_STEPS = ['enter_title', 'enter_url', 'enter_reference', 'enter_content_detail', 'enter_lyrics', 'enter_text', 'enter_summary', 'enter_sort_order'];
        var MULTILINE_FIELDS = ['enter_content_detail', 'enter_lyrics', 'enter_text', 'enter_summary'];
        var OPTIONAL_FIELDS  = ['enter_url', 'enter_lyrics', 'enter_text', 'enter_summary', 'enter_sort_order', 'enter_content_detail'];

        var AREA_LABELS = {
            synaxarium_celebration: @js(__('app.telegram_suggest_area_synaxarium_celebration')),
            bible_reading:          @js(__('app.telegram_suggest_area_bible_reading')),
            lectionary:             @js(__('app.telegram_suggest_area_lectionary')),
            mezmur:                 @js(__('app.telegram_suggest_area_mezmur')),
            synaxarium:             @js(__('app.telegram_suggest_area_sinksar')),
            spiritual_book:         @js(__('app.telegram_suggest_area_spiritual_book')),
            reference_resource:     @js(__('app.telegram_suggest_area_reference_resource')),
            daily_message:          @js(__('app.telegram_suggest_area_daily_message')),
        };

        var SECTION_LABELS = {
            title_description: @js(__('app.telegram_suggest_lectionary_section_title_description')),
            pauline:           @js(__('app.telegram_suggest_lectionary_section_pauline')),
            catholic:          @js(__('app.telegram_suggest_lectionary_section_catholic')),
            acts:              @js(__('app.telegram_suggest_lectionary_section_acts')),
            mesbak:            @js(__('app.telegram_suggest_lectionary_section_mesbak')),
            gospel:            @js(__('app.telegram_suggest_lectionary_section_gospel')),
            qiddase:           @js(__('app.telegram_suggest_lectionary_section_qiddase')),
        };

        var RESOURCE_TYPE_LABELS = {
            video:   @js(__('app.telegram_suggest_resource_type_video')),
            website: @js(__('app.telegram_suggest_resource_type_website')),
            file:    @js(__('app.telegram_suggest_resource_type_file')),
        };

        var MONTH_NAMES = {
            1: 'Meskerem / መስከረም', 2: 'Tikimt / ጥቅምት', 3: 'Hidar / ኅዳር',
            4: 'Tahsas / ታኅሣሥ', 5: 'Tir / ጥር', 6: 'Yekatit / የካቲት',
            7: 'Megabit / መጋቢት', 8: 'Miyazia / ሚያዝያ', 9: 'Ginbot / ግንቦት',
            10: 'Sene / ሰኔ', 11: 'Hamle / ሐምሌ', 12: 'Nehase / ነሐሴ',
            13: 'Pagumen / ጳጉሜን',
        };

        var FIELD_LABELS_MAP = {
            // area-specific title prompts
            synaxarium:             { enter_title: @js(__('app.telegram_suggest_enter_sinksar_title')),   enter_text: @js(__('app.telegram_suggest_enter_sinksar_text')),        enter_url: @js(__('app.telegram_suggest_enter_sinksar_link')),       enter_content_detail: @js(__('app.telegram_suggest_enter_saint_description'))  },
            synaxarium_celebration: { enter_title: @js(__('app.telegram_suggest_enter_saint_name')),      enter_content_detail: @js(__('app.telegram_suggest_enter_celebration_description')), enter_sort_order: @js(__('app.telegram_suggest_enter_sort_order')) },
            mezmur:                 { enter_title: @js(__('app.telegram_suggest_enter_mezmur_title')),    enter_url: @js(__('app.telegram_suggest_enter_mezmur_link')),          enter_content_detail: @js(__('app.telegram_suggest_enter_mezmur_notes')),    enter_lyrics: @js(__('app.telegram_suggest_enter_mezmur_lyrics')) },
            spiritual_book:         { enter_title: @js(__('app.telegram_suggest_enter_spiritual_book_title')), enter_url: @js(__('app.telegram_suggest_enter_spiritual_book_link')), enter_content_detail: @js(__('app.telegram_suggest_enter_spiritual_book_notes')) },
            reference_resource:     { enter_title: @js(__('app.telegram_suggest_enter_resource_title')), enter_url: @js(__('app.telegram_suggest_enter_resource_link')),       enter_content_detail: @js(__('app.telegram_suggest_enter_resource_notes')) },
            daily_message:          { enter_title: @js(__('app.telegram_suggest_enter_daily_message_title')), enter_content_detail: @js(__('app.telegram_suggest_enter_daily_message_body')) },
            lectionary:             { enter_title: @js(__('app.telegram_suggest_enter_lectionary_title')), enter_content_detail: @js(__('app.telegram_suggest_enter_lectionary_text')) },
            bible_reading:          { enter_reference: @js(__('app.telegram_suggest_enter_bible_reference')), enter_content_detail: @js(__('app.telegram_suggest_enter_bible_reading_notes')) },
        };

        var DEFAULT_FIELD_LABELS = {
            enter_title:          @js(__('app.telegram_suggest_enter_title')),
            enter_url:            @js(__('app.telegram_suggest_enter_url')),
            enter_reference:      @js(__('app.telegram_suggest_enter_bible_reference')),
            enter_content_detail: @js(__('app.telegram_suggest_enter_detail')),
            enter_lyrics:         @js(__('app.telegram_suggest_enter_mezmur_lyrics')),
            enter_text:           @js(__('app.telegram_suggest_enter_bible_text')),
            enter_summary:        @js(__('app.telegram_suggest_enter_bible_summary')),
            enter_sort_order:     @js(__('app.telegram_suggest_enter_sort_order')),
        };

        var FIELD_TO_FORM_KEY = {
            enter_title:          'title',
            enter_url:            'url',
            enter_reference:      'reference',
            enter_content_detail: 'content_detail',
            enter_lyrics:         'lyrics',
            enter_text:           'text',
            enter_summary:        'summary',
            enter_sort_order:     'sort_order',
        };

        var FIELD_PLACEHOLDERS = {
            enter_title:          '',
            enter_url:            'https://',
            enter_reference:      @js(__('app.suggest_reference_ph')),
            enter_content_detail: '',
            enter_lyrics:         '',
            enter_text:           '',
            enter_summary:        '',
            enter_sort_order:     '1',
        };

        return {
            step: 'choose_area',
            currentLang: 'en',
            langPhase: 1,
            submitting: false,

            form: {
                content_area: '',
                ethiopian_month: null,
                ethiopian_day: null,
                entry_scope: null,
                first_language: null,
                lectionary_section: null,
                resource_type: null,
                is_main: null,
                sort_order: null,
                // Bilingual
                title_en: '', title_am: '',
                url_en: '', url_am: '',
                reference_en: '', reference_am: '',
                content_detail_en: '', content_detail_am: '',
                lyrics_en: '', lyrics_am: '',
                text_en: '', text_am: '',
                summary_en: '', summary_am: '',
            },

            get allSteps() {
                var flow = flowFor(this.form);
                // Deduplicate into logical groups for the progress indicator
                var seen = {};
                var out = [];
                flow.forEach(function (s) {
                    if (!seen[s]) { seen[s] = true; out.push(s); }
                });
                return out;
            },

            get stepIndex() {
                return this.allSteps.indexOf(this.step);
            },

            setArea: function (area) {
                this.form.content_area = area;
                this.nextStep();
            },

            nextStep: function () {
                var flow = flowFor(this.form);
                var idx = flow.indexOf(this.step);
                if (idx !== -1 && idx < flow.length - 1) {
                    this.goStep(flow[idx + 1]);
                }
            },

            prevStep: function () {
                this.goStep(this.previousStep());
            },

            previousStep: function () {
                var flow = flowFor(this.form);
                var idx = flow.indexOf(this.step);
                if (idx > 0) return flow[idx - 1];
                return 'choose_area';
            },

            goStep: function (s) {
                this.step = s;
                window.scrollTo({ top: 0, behavior: 'smooth' });
                if (this.isFieldStep(s)) {
                    var self = this;
                    this.$nextTick(function () {
                        if (self.$refs.fieldInput) self.$refs.fieldInput.focus();
                    });
                }
            },

            isFieldStep: function (s) {
                return FIELD_STEPS.indexOf(s) !== -1;
            },

            isMultilineField: function (s) {
                return MULTILINE_FIELDS.indexOf(s) !== -1;
            },

            isOptionalField: function (s) {
                var area = this.form.content_area;
                if (s === 'enter_content_detail') {
                    return ['synaxarium', 'synaxarium_celebration', 'mezmur', 'spiritual_book', 'reference_resource', 'bible_reading', 'lectionary'].indexOf(area) !== -1;
                }
                return OPTIONAL_FIELDS.indexOf(s) !== -1;
            },

            fieldStepLabel: function () {
                var area = this.form.content_area;
                var areaMap = FIELD_LABELS_MAP[area] || {};
                return areaMap[this.step] || DEFAULT_FIELD_LABELS[this.step] || this.step;
            },

            fieldStepPlaceholder: function () {
                return FIELD_PLACEHOLDERS[this.step] || '';
            },

            monthName: function (m) {
                return MONTH_NAMES[m] || '';
            },

            maxDaysForMonth: function () {
                var m = this.form.ethiopian_month;
                var max = m === 13 ? 6 : 30;
                var arr = [];
                for (var i = 1; i <= max; i++) arr.push(i);
                return arr;
            },

            areaLabel: function () {
                return AREA_LABELS[this.form.content_area] || this.form.content_area;
            },

            lectionarySectionLabel: function () {
                return SECTION_LABELS[this.form.lectionary_section] || this.form.lectionary_section;
            },

            resourceTypeLabel: function () {
                return RESOURCE_TYPE_LABELS[this.form.resource_type] || this.form.resource_type;
            },

            otherLangPrompt: function () {
                var other = this.form.first_language === 'en' ? '🇪🇹 አማርኛ' : '🇬🇧 English';
                return @js(__('app.advanced_suggest_other_lang_prompt')).replace(':lang', other);
            },

            otherLangAddLabel: function () {
                var other = this.form.first_language === 'en' ? '🇪🇹 አማርኛ' : '🇬🇧 English';
                return @js(__('app.telegram_suggest_add_other_lang')).replace(':lang', other);
            },

            switchToOtherLanguage: function () {
                this.langPhase = 2;
                this.currentLang = this.form.first_language === 'en' ? 'am' : 'en';
                // Go back to first field step for this area
                var flow = flowFor(this.form);
                var firstField = flow.find(function (s) { return FIELD_STEPS.indexOf(s) !== -1; });
                this.goStep(firstField || 'preview');
            },

            hasAnyLangContent: function (lang) {
                var form = this.form;
                return ['title', 'reference', 'url', 'content_detail', 'lyrics', 'text', 'summary'].some(function (f) {
                    return !!form[f + '_' + lang];
                });
            },

            fieldLabel: function (field) {
                var labels = {
                    title: @js(__('app.suggest_title_label')),
                    reference: @js(__('app.suggest_reference_label')),
                    url: 'URL',
                    content_detail: @js(__('app.suggest_detail_label')),
                    lyrics: @js(__('app.suggest_type_mezmur') + ' Lyrics'),
                    text: 'Text',
                    summary: 'Summary',
                };
                return labels[field] || field;
            },

            truncate: function (str, max) {
                if (!str) return '';
                return str.length > max ? str.substring(0, max) + '…' : str;
            },

            resetAll: function () {
                this.step = 'choose_area';
                this.langPhase = 1;
                this.currentLang = 'en';
                this.submitting = false;
                this.form = {
                    content_area: '', ethiopian_month: null, ethiopian_day: null,
                    entry_scope: null, first_language: null, lectionary_section: null,
                    resource_type: null, is_main: null, sort_order: null,
                    title_en: '', title_am: '', url_en: '', url_am: '',
                    reference_en: '', reference_am: '', content_detail_en: '', content_detail_am: '',
                    lyrics_en: '', lyrics_am: '', text_en: '', text_am: '',
                    summary_en: '', summary_am: '',
                };
            },
        };
    });
});
</script>
@endsection

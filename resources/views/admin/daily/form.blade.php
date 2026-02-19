@extends('layouts.admin')

@php
    $locale = app()->getLocale();

    $isEdit = isset($daily) && $daily->exists;
    $totalSteps = 7;
    $currentStep = max(1, min($totalSteps, (int) ($initialStep ?? 1)));

    $initialMezmurs = old(
        'mezmurs',
        $isEdit
            ? $daily->mezmurs
                ->map(fn ($m) => [
                    'title_en' => $m->title_en ?? '',
                    'title_am' => $m->title_am ?? '',
                    'url_en' => $m->url_en ?? $m->url ?? '',
                    'url_am' => $m->url_am ?? $m->url ?? '',
                    'description_en' => $m->description_en ?? '',
                    'description_am' => $m->description_am ?? '',
                ])
                ->values()
                ->toArray()
            : [[
                'title_en' => '',
                'title_am' => '',
                'url_en' => '',
                'url_am' => '',
                'description_en' => '',
                'description_am' => '',
            ]]
    );

    $initialReferences = old(
        'references',
        $isEdit
                ? $daily->references
                ->map(fn ($r) => [
                    'name_en' => $r->name_en ?? '',
                    'name_am' => $r->name_am ?? '',
                    'url_en' => $r->url_en ?? $r->url ?? '',
                    'url_am' => $r->url_am ?? $r->url ?? '',
                    'type' => $r->type ?? 'website',
                ])
                ->values()
                ->toArray()
            : [[
                'name_en' => '',
                'name_am' => '',
                'url_en' => '',
                'url_am' => '',
                'type' => 'website',
            ]]
    );

    $initialBooks = old(
        'books',
        $isEdit && method_exists($daily, 'books')
            ? $daily->books
                ->map(fn ($b) => [
                    'title_en' => $b->title_en ?? '',
                    'title_am' => $b->title_am ?? '',
                    'url_en' => $b->url_en ?? $b->url ?? '',
                    'url_am' => $b->url_am ?? $b->url ?? '',
                    'description_en' => $b->description_en ?? '',
                    'description_am' => $b->description_am ?? '',
                ])
                ->values()
                ->toArray()
            : []
    );

    $recentBooks = $recentBooks ?? [];

    $themesByWeek = $themes
        ->keyBy('week_number')
        ->map(fn ($theme) => [
            'id' => $theme->id,
            'name_en' => $theme->name_en,
            'name_am' => $theme->name_am,
        ])
        ->toArray();

    $wizardState = [
        'lent_season_id' => old('lent_season_id', $daily->lent_season_id ?? $season?->id),
        'weekly_theme_id' => old('weekly_theme_id', $daily->weekly_theme_id ?? ''),
        'day_number' => old('day_number', $daily->day_number ?? ''),
        'date' => old('date', $isEdit && $daily->date ? $daily->date->format('Y-m-d') : ''),
        'day_title_am' => old('day_title_am', $daily->day_title_am ?? ''),
        'day_title_en' => old('day_title_en', $daily->day_title_en ?? ''),
        'bible_reference_am' => old('bible_reference_am', $daily->bible_reference_am ?? ''),
        'bible_reference_en' => old('bible_reference_en', $daily->bible_reference_en ?? ''),
        'bible_summary_am' => old('bible_summary_am', $daily->bible_summary_am ?? ''),
        'bible_summary_en' => old('bible_summary_en', $daily->bible_summary_en ?? ''),
        'bible_text_am' => old('bible_text_am', $daily->bible_text_am ?? ''),
        'bible_text_en' => old('bible_text_en', $daily->bible_text_en ?? ''),
        'sinksar_title_am' => old('sinksar_title_am', $daily->sinksar_title_am ?? ''),
        'sinksar_title_en' => old('sinksar_title_en', $daily->sinksar_title_en ?? ''),
        'sinksar_url_en' => old('sinksar_url_en', $daily->sinksar_url_en ?? $daily->sinksar_url ?? ''),
        'sinksar_url_am' => old('sinksar_url_am', $daily->sinksar_url_am ?? $daily->sinksar_url ?? ''),
        'sinksar_description_am' => old('sinksar_description_am', $daily->sinksar_description_am ?? ''),
        'sinksar_description_en' => old('sinksar_description_en', $daily->sinksar_description_en ?? ''),
        'books' => $initialBooks,
        'reflection_am' => old('reflection_am', $daily->reflection_am ?? ''),
        'reflection_en' => old('reflection_en', $daily->reflection_en ?? ''),
        'is_published' => (bool) old('is_published', $daily->is_published ?? false),
        'mezmurs' => $initialMezmurs,
        'references' => $initialReferences,
    ];

    $stepLabels = [
        1 => __('app.step_day_info'),
        2 => __('app.step_bible_reading'),
        3 => __('app.step_mezmur'),
        4 => __('app.step_sinksar'),
        5 => __('app.step_spiritual_book'),
        6 => __('app.step_reflection_refs'),
        7 => __('app.step_review_publish'),
    ];
@endphp

@section('title', $isEdit ? __('app.edit_day', ['day' => $daily->day_number]) : __('app.create_daily_content'))

@section('content')
    @if (! $season)
        <div class="max-w-2xl mx-auto bg-card rounded-2xl shadow-sm border border-border p-5">
            <p class="text-muted-text">
                {{ __('app.no_active_season') }}
                <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">
                    {{ __('app.create_one_first') }}
                </a>
            </p>
        </div>
    @else
        <div
            class="max-w-2xl mx-auto space-y-3 -mx-4 sm:mx-auto pb-4"
            x-data="dailyWizard({
                currentStep: @js($currentStep),
                totalSteps: @js($totalSteps),
                isEdit: @js($isEdit),
                dailyId: @js($isEdit ? (int) $daily->id : null),
                seasonStart: @js($season?->start_date?->format('Y-m-d') ?? ''),
                dayRangesByWeek: @js($dayRangesByWeek ?? []),
                themesByWeek: @js($themesByWeek),
                locale: @js($locale),
                stepLabels: @js($stepLabels),
                recentBooks: @js($recentBooks),
                state: @js($wizardState),
                urls: {
                    create: @js(route('admin.daily.store')),
                    patchTemplate: @js(route('admin.daily.patch', ['daily' => '__DAILY_ID__'])),
                    editTemplate: @js(route('admin.daily.edit', ['daily' => '__DAILY_ID__'])),
                    index: @js(route('admin.daily.index')),
                    copyFromTemplate: @js(route('admin.daily.copy_from', ['day_number' => '__DAY__']))
                },
                daysWithContent: @js($daysWithContent ?? []),
                messages: {
                    stepTemplate: @js(__('app.step_x_of_y', ['current' => ':current', 'total' => ':total'])),
                    next: @js(__('app.next')),
                    back: @js(__('app.back')),
                    saving: @js(__('app.saving')),
                    saved: @js(__('app.saved')),
                    finish: @js(__('app.finish')),
                    failed: @js(__('app.failed')),
                    copyDay: @js(__('app.copy_day')),
                    copyFromDay: @js(__('app.copy_from_day')),
                    copyDayHint: @js(__('app.copy_from_day_hint')),
                    copying: @js(__('app.copying')),
                    copySuccess: @js(__('app.copy_day_success'))
                }
            })"
            x-init="init()"
        >
            {{-- Wizard header --}}
            <div class="bg-card sm:rounded-2xl shadow-sm border-b sm:border border-border px-4 py-4 sm:p-5">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h1 class="text-base sm:text-xl font-bold text-primary leading-tight">
                        {{ $isEdit ? __('app.edit_day', ['day' => $daily->day_number]) : __('app.create_daily_content') }}
                    </h1>
                    <span class="text-xs text-muted-text font-medium whitespace-nowrap" x-text="stepText()"></span>
                </div>

                {{-- Step progress bar (mobile-optimized) --}}
                <div class="flex items-center justify-between gap-1 mb-3">
                    <template x-for="stepNumber in totalSteps" :key="'step-indicator-' + stepNumber">
                        <div class="flex items-center gap-1 flex-1">
                            <button
                                type="button"
                                @click="jumpToStep(stepNumber)"
                                :disabled="!isStepUnlocked(stepNumber) || isSaving"
                                class="w-7 h-7 sm:w-8 sm:h-8 shrink-0 rounded-full text-[10px] sm:text-xs font-bold border-2 transition touch-manipulation disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="step === stepNumber
                                    ? 'bg-accent text-on-accent border-accent shadow-sm'
                                    : (stepNumber < step
                                        ? 'bg-accent/20 text-accent border-accent/40'
                                        : (isStepUnlocked(stepNumber)
                                            ? 'bg-muted text-secondary border-border'
                                            : 'bg-muted/30 text-muted-text border-border/50'))"
                                :aria-label="stepLabel(stepNumber)"
                            >
                                <span x-text="stepNumber"></span>
                            </button>
                            <div
                                x-show="stepNumber < totalSteps"
                                class="flex-1 h-0.5 rounded-full"
                                :class="stepNumber < step ? 'bg-accent' : 'bg-border'"
                            ></div>
                        </div>
                    </template>
                </div>

                <p class="text-sm font-medium text-accent" x-text="stepLabel(step)"></p>
            </div>

            {{-- Step content --}}
            <div class="bg-card sm:rounded-2xl shadow-sm sm:border border-border px-4 py-5 sm:p-6 space-y-5">
                <div x-show="errorMessage" x-cloak class="p-3 rounded-xl border border-error bg-error-bg text-error text-sm" x-text="errorMessage"></div>

                {{-- Step 1: Day info --}}
                <section x-show="step === 1" x-cloak class="space-y-5">
                    <input type="hidden" x-model="form.lent_season_id">

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.day_number_label') }}</label>
                            <input
                                type="number"
                                inputmode="numeric"
                                min="1"
                                max="55"
                                x-model="form.day_number"
                                @input="syncFromDayNumber()"
                                class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.date_label') }}</label>
                            <input
                                type="date"
                                x-model="form.date"
                                class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.weekly_theme_label') }}</label>
                            <select
                                x-model="form.weekly_theme_id"
                                class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"
                            >
                            <option value="">{{ __('app.select_placeholder') }}</option>
                            @foreach($themes as $theme)
                                @php($range = \App\Services\AbiyTsomStructure::getDayRangeForWeek($theme->week_number))
                                <option value="{{ $theme->id }}">
                                    {{ __('app.week_label') }} {{ $theme->week_number }}
                                    - {{ $locale === 'en' ? ($theme->name_en ?: $theme->name_am ?: '-') : ($theme->name_am ?: $theme->name_en ?: '-') }}
                                    ({{ $range[0] }}-{{ $range[1] }})
                                </option>
                            @endforeach
                        </select>
                        </div>
                    </div>

                    <div x-show="resolvedInfo" x-cloak class="flex flex-wrap items-center gap-2 px-4 py-3 rounded-xl bg-accent/10 border border-accent/20 text-sm">
                        <span class="font-semibold text-accent">{{ __('app.week_label') }}:</span>
                        <span class="text-secondary" x-text="resolvedInfo ? resolvedInfo.week : ''"></span>
                        <span class="text-muted-text">|</span>
                        <span class="font-semibold text-accent">{{ __('app.day_label') }}:</span>
                        <span class="text-secondary" x-text="resolvedInfo ? resolvedInfo.dayOfWeek : ''"></span>
                    </div>

                    {{-- Copy from day (mobile-first) --}}
                    @if(!empty($daysWithContent ?? []))
                    <div class="p-4 rounded-xl border-2 border-dashed border-border bg-muted/20 space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.copy_from_day') }}</label>
                        <p class="text-xs text-muted-text" x-text="messages.copyDayHint"></p>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <select
                                x-model="copySourceDay"
                                class="flex-1 min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition touch-manipulation"
                            >
                                <option value="">{{ __('app.select_placeholder') }}</option>
                                @foreach($daysWithContent as $opt)
                                    <option value="{{ $opt['day_number'] }}">{{ $opt['label'] }}</option>
                                @endforeach
                            </select>
                            <button
                                type="button"
                                @click="copyFromDay()"
                                :disabled="!copySourceDay || isCopying"
                                class="min-h-12 px-5 py-3 rounded-xl bg-accent-secondary text-on-accent text-base font-medium hover:bg-accent-secondary/90 transition touch-manipulation disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                            >
                                <span x-show="!isCopying" x-text="messages.copyDay"></span>
                                <span x-show="isCopying" x-cloak x-text="messages.copying"></span>
                            </button>
                        </div>
                        <p x-show="copyNotice" x-cloak class="text-sm text-success font-medium" x-text="copyNotice"></p>
                    </div>
                    @endif

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.day_title_optional') }}</label>
                        <input
                            type="text"
                            x-model="form.day_title_am"
                            placeholder="{{ __('app.amharic_default') }}"
                            class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"
                        >
                        <input
                            type="text"
                            x-model="form.day_title_en"
                            placeholder="{{ __('app.english_fallback') }}"
                            class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"
                        >
                    </div>
                </section>

                {{-- Step 2: Bible reading --}}
                <section x-show="step === 2" x-cloak class="space-y-5">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.reference_placeholder') }}</label>
                        <input type="text" x-model="form.bible_reference_am" placeholder="{{ __('app.amharic') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <input type="text" x-model="form.bible_reference_en" placeholder="{{ __('app.english') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.summary_label') }}</label>
                        <textarea x-model="form.bible_summary_am" rows="3" placeholder="{{ __('app.amharic') }}" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        <textarea x-model="form.bible_summary_en" rows="3" placeholder="{{ __('app.english') }}" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.bible_text_am_label') }}</label>
                        <textarea x-model="form.bible_text_am" rows="6" placeholder="{{ __('app.bible_text_am_placeholder') }}" class="w-full min-h-[8rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>

                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.bible_text_en_label') }}</label>
                        <textarea x-model="form.bible_text_en" rows="6" placeholder="{{ __('app.bible_text_en_placeholder') }}" class="w-full min-h-[8rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>
                </section>

                {{-- Step 3: Mezmur --}}
                <section x-show="step === 3" x-cloak class="space-y-4">
                    <p class="text-xs text-muted-text px-1">{{ __('app.add_mezmur_hint') }}</p>
                    <template x-for="(mezmur, index) in form.mezmurs" :key="'mezmur-' + index">
                        <div class="p-4 rounded-xl bg-accent-secondary/5 border border-accent-secondary/20 space-y-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-semibold text-accent-secondary" x-text="'{{ __('app.mezmur_label') }} ' + (index + 1)"></span>
                                <button
                                    type="button"
                                    @click="removeMezmur(index)"
                                    class="min-h-10 min-w-10 px-3 py-2 text-xs text-muted-text hover:text-error hover:bg-error/10 transition rounded-lg touch-manipulation"
                                    :disabled="form.mezmurs.length === 1"
                                >
                                    {{ __('app.remove') }}
                                </button>
                            </div>

                            <div class="space-y-2 rounded-lg bg-white/40 border border-accent-secondary/20 p-3">
                                <p class="text-xs font-semibold text-accent-secondary">{{ __('app.amharic') }}</p>
                                <input type="text" x-model="mezmur.title_am" placeholder="{{ __('app.name_amharic_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                <textarea x-model="mezmur.description_am" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.amharic') }})" class="w-full min-h-[4rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                                <input type="url" x-model="mezmur.url_am" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.amharic') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                            </div>

                            <div class="space-y-2 rounded-lg bg-white/40 border border-accent-secondary/20 p-3">
                                <p class="text-xs font-semibold text-accent-secondary">{{ __('app.english') }}</p>
                                <input type="text" x-model="mezmur.title_en" placeholder="{{ __('app.name_english_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                <textarea x-model="mezmur.description_en" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.english') }})" class="w-full min-h-[4rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                                <input type="url" x-model="mezmur.url_en" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.english') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addMezmur()" class="w-full min-h-12 py-3 border-2 border-dashed border-accent-secondary/40 rounded-xl text-sm font-medium text-accent-secondary hover:bg-accent-secondary/10 transition touch-manipulation">
                        + {{ __('app.mezmur_label') }}
                    </button>
                </section>

                {{-- Step 4: Sinksar --}}
                <section x-show="step === 4" x-cloak class="space-y-5">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.title_label') }}</label>
                        <input type="text" x-model="form.sinksar_title_am" placeholder="{{ __('app.amharic') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <input type="text" x-model="form.sinksar_title_en" placeholder="{{ __('app.english') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.url_video_label') }}</label>
                        <input type="url" x-model="form.sinksar_url_am" placeholder="{{ __('app.youtube_url_placeholder') }} ({{ __('app.amharic') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <input type="url" x-model="form.sinksar_url_en" placeholder="{{ __('app.youtube_url_placeholder') }} ({{ __('app.english') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                    </div>
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.description_label') }}</label>
                        <textarea x-model="form.sinksar_description_am" rows="3" placeholder="{{ __('app.amharic') }}" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        <textarea x-model="form.sinksar_description_en" rows="3" placeholder="{{ __('app.english') }}" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>
                </section>

                {{-- Step 5: Spiritual book (multiple, re-use from previous days) --}}
                {{-- Feature: Multiple books per day + recommendations from previous days --}}
                <section x-show="step === 5" x-cloak class="space-y-5">
                    <p class="text-xs text-muted-text px-1">{{ __('app.add_spiritual_book_hint') }}</p>

                    {{-- Recommendations from previous days --}}
                    @if(!empty($recentBooks))
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.recommend_from_previous') }}</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto overscroll-contain touch-manipulation">
                            @foreach($recentBooks as $rb)
                            <button type="button"
                                    @click="addBookFromRecommendation(@js($rb))"
                                    class="text-left p-3 rounded-xl border border-border bg-muted/30 hover:bg-accent/10 hover:border-accent/40 transition text-sm touch-manipulation">
                                @php($recommendedBookTitle = $locale === 'en'
                                    ? (($rb['title_en'] ?? '') ?: ($rb['title_am'] ?? '-'))
                                    : (($rb['title_am'] ?? '') ?: ($rb['title_en'] ?? '-')))
                                <span class="font-medium text-primary line-clamp-2">{{ $recommendedBookTitle }}</span>
                                <span class="text-xs text-muted-text mt-1 block">{{ __('app.day_label') }} {{ $rb['day_number'] ?? '-' }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Current books list --}}
                    <div class="space-y-4">
                        <template x-for="(book, index) in form.books" :key="'book-' + index">
                            <div class="p-4 rounded-xl bg-book/5 border border-book/20 space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-book" x-text="'{{ __('app.spiritual_book') }} ' + (index + 1)"></span>
                                    <button
                                        type="button"
                                        @click="removeBook(index)"
                                        class="min-h-10 min-w-10 px-3 py-2 text-xs text-muted-text hover:text-error hover:bg-error/10 transition rounded-lg touch-manipulation"
                                    >
                                        {{ __('app.remove') }}
                                    </button>
                                </div>
                                <div class="space-y-2 rounded-lg bg-white/40 border border-book/20 p-3">
                                    <p class="text-xs font-semibold text-book">{{ __('app.amharic') }}</p>
                                    <input type="text" x-model="book.title_am" placeholder="{{ __('app.name_amharic_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                    <textarea x-model="book.description_am" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.amharic') }})" class="w-full min-h-[4rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                                    <input type="url" x-model="book.url_am" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.amharic') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                </div>

                                <div class="space-y-2 rounded-lg bg-white/40 border border-book/20 p-3">
                                    <p class="text-xs font-semibold text-book">{{ __('app.english') }}</p>
                                    <input type="text" x-model="book.title_en" placeholder="{{ __('app.name_english_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                    <textarea x-model="book.description_en" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.english') }})" class="w-full min-h-[4rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                                    <input type="url" x-model="book.url_en" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.english') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addBook()" class="w-full min-h-12 py-3 border-2 border-dashed border-book/40 rounded-xl text-sm font-medium text-book hover:bg-book/10 transition touch-manipulation">
                            + {{ __('app.add_spiritual_book') }}
                        </button>
                    </div>
                </section>

                {{-- Step 6: Reflection and references --}}
                <section x-show="step === 6" x-cloak class="space-y-5">
                    <div class="space-y-3">
                        <label class="block text-sm font-medium text-secondary">{{ __('app.reflection_label') }}</label>
                        <textarea x-model="form.reflection_am" rows="4" placeholder="{{ __('app.amharic_default') }}" class="w-full min-h-[6rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        <textarea x-model="form.reflection_en" rows="4" placeholder="{{ __('app.english_fallback') }}" class="w-full min-h-[6rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>

                    <div class="space-y-4">
                        <p class="text-sm font-medium text-secondary">{{ __('app.references_legend') }}</p>
                        <template x-for="(reference, index) in form.references" :key="'reference-' + index">
                            <div class="p-4 rounded-xl bg-muted/50 border border-border space-y-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold text-muted-text">{{ __('app.reference_name') }}</span>
                                    <button
                                        type="button"
                                        @click="removeReference(index)"
                                        class="min-h-10 min-w-10 px-3 py-2 text-xs text-muted-text hover:text-error hover:bg-error/10 transition rounded-lg touch-manipulation"
                                        :disabled="form.references.length === 1"
                                    >
                                        {{ __('app.remove') }}
                                    </button>
                                </div>
                                <div class="space-y-2 rounded-lg bg-muted border border-border p-3">
                                    <p class="text-xs font-semibold text-muted-text">{{ __('app.amharic') }}</p>
                                    <input type="text" x-model="reference.name_am" placeholder="{{ __('app.name_amharic_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                    <input type="url" x-model="reference.url_am" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.amharic') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                </div>
                                <div class="space-y-2 rounded-lg bg-muted border border-border p-3">
                                    <p class="text-xs font-semibold text-muted-text">{{ __('app.english') }}</p>
                                    <input type="text" x-model="reference.name_en" placeholder="{{ __('app.name_english_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                    <input type="url" x-model="reference.url_en" placeholder="{{ __('app.url_placeholder') }} ({{ __('app.english') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-secondary mb-1">{{ __('app.reference_type_label') }}</label>
                                    <select x-model="reference.type" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                                        <option value="video">{{ __('app.reference_type_video') }}</option>
                                        <option value="website">{{ __('app.reference_type_website') }}</option>
                                        <option value="file">{{ __('app.reference_type_file') }}</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                        <button type="button" @click="addReference()" class="w-full min-h-12 py-3 border-2 border-dashed border-border rounded-xl text-sm font-medium text-muted-text hover:text-accent hover:border-accent transition touch-manipulation">
                            + {{ __('app.add_reference') }}
                        </button>
                    </div>
                </section>

                {{-- Step 7: Review and publish --}}
                <section x-show="step === 7" x-cloak class="space-y-5">
                    <h2 class="text-base sm:text-lg font-semibold text-primary">{{ __('app.review_and_publish') }}</h2>

                    <div class="space-y-3">
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.day_label') }}</p>
                            <p class="text-base font-medium text-primary" x-text="form.day_number || '-'"></p>
                        </div>
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.date_label') }}</p>
                            <p class="text-base font-medium text-primary" x-text="form.date || '-'"></p>
                        </div>
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.weekly_theme_label') }}</p>
                            <p class="text-base font-medium text-primary" x-text="selectedThemeName()"></p>
                        </div>
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.bible_reading_label') }}</p>
                            <p class="text-base font-medium text-primary" x-text="(locale === 'en' ? (form.bible_reference_en || form.bible_reference_am) : (form.bible_reference_am || form.bible_reference_en)) || '-'"></p>
                        </div>
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.mezmur_label') }}</p>
                            <p class="text-base font-medium text-primary" x-text="activeMezmurCount()"></p>
                        </div>
                        <div class="p-4 rounded-xl border border-border bg-muted/40">
                            <p class="text-xs text-muted-text mb-1">{{ __('app.spiritual_book') }}</p>
                            <p class="text-base font-medium text-primary" x-text="activeBookCount()"></p>
                        </div>
                    </div>

                    <label class="flex items-center gap-4 p-4 rounded-xl border border-border bg-accent/5 touch-manipulation">
                        <input type="checkbox" x-model="form.is_published" class="w-5 h-5 rounded border-border text-accent focus:ring-accent">
                        <span class="text-base font-medium text-secondary">{{ __('app.publish_label') }}</span>
                    </label>
                </section>
            </div>

            {{-- Sticky bottom action bar --}}
            <div class="sticky bottom-0 z-30 -mx-4 sm:mx-0">
                <div class="bg-card/95 backdrop-blur-md border-t sm:border border-border shadow-lg sm:rounded-2xl px-4 py-3 sm:p-3 safe-bottom">
                    {{-- Save status --}}
                    <div class="text-center text-xs mb-2 min-h-[1rem]">
                        <span x-show="isSaving" x-cloak class="text-muted-text animate-pulse">{{ __('app.saving') }}...</span>
                        <span x-show="!isSaving && saveNotice" x-cloak class="text-success font-medium" x-text="saveNotice"></span>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            @click="backStep()"
                            :disabled="step === 1 || isSaving"
                            class="flex-1 min-h-12 px-4 py-3 rounded-xl bg-muted text-secondary text-base font-medium hover:bg-border transition touch-manipulation disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            {{ __('app.back') }}
                        </button>

                        <button
                            type="button"
                            @click="nextStep()"
                            :disabled="!canProceed() || isSaving"
                            class="flex-[2] min-h-12 px-5 py-3 rounded-xl bg-accent text-on-accent text-base font-semibold hover:bg-accent-hover transition touch-manipulation disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            <span x-text="nextButtonText()"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function dailyWizard(config) {
                return {
                    step: Number(config.currentStep || 1),
                    totalSteps: Number(config.totalSteps || 7),
                    maxUnlockedStep: config.isEdit ? Number(config.totalSteps || 7) : Number(config.currentStep || 1),
                    isEdit: Boolean(config.isEdit),
                    dailyId: config.dailyId ? Number(config.dailyId) : null,
                    isSaving: false,
                    saveNotice: '',
                    errorMessage: '',
                    seasonStart: config.seasonStart || '',
                    dayRangesByWeek: config.dayRangesByWeek || {},
                    themesByWeek: config.themesByWeek || {},
                    stepLabels: config.stepLabels || {},
                    recentBooks: Array.isArray(config.recentBooks) ? config.recentBooks : [],
                    urls: config.urls || {},
                    messages: config.messages || {},
                    daysWithContent: Array.isArray(config.daysWithContent) ? config.daysWithContent : [],
                    locale: config.locale || 'am',
                    resolvedInfo: null,
                    form: config.state || {},
                    copySourceDay: '',
                    isCopying: false,
                    copyNotice: '',

                    init() {
                        if (!Array.isArray(this.form.mezmurs) || this.form.mezmurs.length === 0) {
                            this.form.mezmurs = [{ title_en: '', title_am: '', url_en: '', url_am: '', description_en: '', description_am: '' }];
                        }
                        if (!Array.isArray(this.form.references) || this.form.references.length === 0) {
                            this.form.references = [{ name_en: '', name_am: '', url_en: '', url_am: '', type: 'website' }];
                        }
                        if (!Array.isArray(this.form.books)) {
                            this.form.books = [];
                        }
                        this.syncFromDayNumber();
                    },

                    stepText() {
                        return (this.messages.stepTemplate || 'Step :current of :total')
                            .replace(':current', String(this.step))
                            .replace(':total', String(this.totalSteps));
                    },

                    stepLabel(stepNumber) {
                        return this.stepLabels[String(stepNumber)] || this.stepLabels[stepNumber] || '';
                    },

                    isStepUnlocked(stepNumber) {
                        return stepNumber <= this.maxUnlockedStep;
                    },

                    jumpToStep(stepNumber) {
                        if (!this.isStepUnlocked(stepNumber) || this.isSaving) {
                            return;
                        }
                        this.step = stepNumber;
                        this.errorMessage = '';
                    },

                    addMezmur() {
                        this.form.mezmurs.push({
                            title_en: '',
                            title_am: '',
                            url_en: '',
                            url_am: '',
                            description_en: '',
                            description_am: '',
                        });
                    },

                    removeMezmur(index) {
                        if (this.form.mezmurs.length === 1) {
                            this.form.mezmurs[0] = {
                                title_en: '',
                                title_am: '',
                                url_en: '',
                                url_am: '',
                                description_en: '',
                                description_am: '',
                            };
                            return;
                        }
                        this.form.mezmurs.splice(index, 1);
                    },

                    addReference() {
                        this.form.references.push({
                            name_en: '',
                            name_am: '',
                            url_en: '',
                            url_am: '',
                            type: 'website',
                        });
                    },

                    removeReference(index) {
                        if (this.form.references.length === 1) {
                            this.form.references[0] = { name_en: '', name_am: '', url_en: '', url_am: '', type: 'website' };
                            return;
                        }
                        this.form.references.splice(index, 1);
                    },

                    addBook() {
                        this.form.books.push({
                            title_en: '',
                            title_am: '',
                            url_en: '',
                            url_am: '',
                            description_en: '',
                            description_am: '',
                        });
                    },

                    removeBook(index) {
                        this.form.books.splice(index, 1);
                    },

                    addBookFromRecommendation(bookData) {
                        this.form.books.push({
                            title_en: bookData.title_en || '',
                            title_am: bookData.title_am || '',
                            url_en: bookData.url_en || bookData.url || '',
                            url_am: bookData.url_am || bookData.url || '',
                            description_en: bookData.description_en || '',
                            description_am: bookData.description_am || '',
                        });
                    },

                    async copyFromDay() {
                        const day = Number(this.copySourceDay || 0);
                        if (!day || day < 1 || day > 55 || this.isCopying) {
                            return;
                        }
                        this.isCopying = true;
                        this.copyNotice = '';
                        this.errorMessage = '';
                        const url = (this.urls.copyFromTemplate || '').replace('__DAY__', String(day));
                        try {
                            const response = await fetch(url, {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json' },
                            });
                            const json = await response.json();
                            if (!response.ok || !json?.success) {
                                throw new Error(json?.message || this.messages.failed || 'Failed');
                            }
                            const data = json.data || {};
                            this.form.day_title_en = data.day_title_en ?? '';
                            this.form.day_title_am = data.day_title_am ?? '';
                            this.form.bible_reference_en = data.bible_reference_en ?? '';
                            this.form.bible_reference_am = data.bible_reference_am ?? '';
                            this.form.bible_summary_en = data.bible_summary_en ?? '';
                            this.form.bible_summary_am = data.bible_summary_am ?? '';
                            this.form.bible_text_en = data.bible_text_en ?? '';
                            this.form.bible_text_am = data.bible_text_am ?? '';
                            this.form.sinksar_title_en = data.sinksar_title_en ?? '';
                            this.form.sinksar_title_am = data.sinksar_title_am ?? '';
                            this.form.sinksar_url_en = data.sinksar_url_en ?? data.sinksar_url ?? '';
                            this.form.sinksar_url_am = data.sinksar_url_am ?? data.sinksar_url ?? '';
                            this.form.sinksar_description_en = data.sinksar_description_en ?? '';
                            this.form.sinksar_description_am = data.sinksar_description_am ?? '';
                            this.form.reflection_en = data.reflection_en ?? '';
                            this.form.reflection_am = data.reflection_am ?? '';
                            this.form.mezmurs = Array.isArray(data.mezmurs) && data.mezmurs.length > 0
                                ? data.mezmurs.map((m) => ({
                                    title_en: m.title_en ?? '',
                                    title_am: m.title_am ?? '',
                                    url_en: m.url_en ?? m.url ?? '',
                                    url_am: m.url_am ?? m.url ?? '',
                                    description_en: m.description_en ?? '',
                                    description_am: m.description_am ?? '',
                                }))
                                : [{ title_en: '', title_am: '', url_en: '', url_am: '', description_en: '', description_am: '' }];
                            this.form.references = Array.isArray(data.references) && data.references.length > 0
                                ? data.references.map((r) => ({
                                    name_en: r.name_en ?? '',
                                    name_am: r.name_am ?? '',
                                    url_en: r.url_en ?? r.url ?? '',
                                    url_am: r.url_am ?? r.url ?? '',
                                    type: r.type ?? 'website',
                                }))
                                : [{ name_en: '', name_am: '', url_en: '', url_am: '', type: 'website' }];
                            this.form.books = Array.isArray(data.books) ? data.books.map((b) => ({
                                title_en: b.title_en ?? '',
                                title_am: b.title_am ?? '',
                                url_en: b.url_en ?? b.url ?? '',
                                url_am: b.url_am ?? b.url ?? '',
                                description_en: b.description_en ?? '',
                                description_am: b.description_am ?? '',
                            })) : [];
                            this.copyNotice = this.messages.copySuccess || 'Day copied.';
                            this.maxUnlockedStep = this.totalSteps;
                        } catch (err) {
                            this.errorMessage = err.message || this.messages.failed || 'Failed';
                        } finally {
                            this.isCopying = false;
                        }
                    },

                    canProceed() {
                        if (this.step !== 1) {
                            return true;
                        }

                        return Boolean(
                            this.form.lent_season_id &&
                            this.form.weekly_theme_id &&
                            this.form.day_number &&
                            this.form.date
                        );
                    },

                    nextButtonText() {
                        if (this.step === this.totalSteps) {
                            return this.messages.finish || 'Finish';
                        }
                        return this.messages.next || 'Next';
                    },

                    backStep() {
                        if (this.step > 1 && !this.isSaving) {
                            this.step -= 1;
                        }
                    },

                    async nextStep() {
                        if (this.isSaving || !this.canProceed()) {
                            return;
                        }

                        if (this.step === this.totalSteps) {
                            await this.saveStep({ finish: true });
                            return;
                        }

                        await this.saveStep({ advance: true });
                    },

                    syncFromDayNumber() {
                        const day = Number(this.form.day_number || 0);
                        if (!day || day < 1 || day > 55 || !this.seasonStart) {
                            this.resolvedInfo = null;
                            return;
                        }

                        const seasonParts = this.seasonStart.split('-').map(Number);
                        if (seasonParts.length === 3) {
                            const dateObj = new Date(seasonParts[0], seasonParts[1] - 1, seasonParts[2]);
                            dateObj.setDate(dateObj.getDate() + day - 1);
                            const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                            const date = String(dateObj.getDate()).padStart(2, '0');
                            this.form.date = dateObj.getFullYear() + '-' + month + '-' + date;
                        }

                        let resolved = null;
                        Object.entries(this.dayRangesByWeek || {}).forEach(([week, range]) => {
                            if (!Array.isArray(range) || range.length !== 2 || resolved) {
                                return;
                            }
                            const start = Number(range[0]);
                            const end = Number(range[1]);
                            if (day >= start && day <= end) {
                                const theme = this.themesByWeek[week];
                                if (theme?.id) {
                                    this.form.weekly_theme_id = String(theme.id);
                                }
                                resolved = {
                                    week: Number(week),
                                    dayOfWeek: day - start + 1,
                                };
                            }
                        });

                        this.resolvedInfo = resolved;
                    },

                    selectedThemeName() {
                        const selectedId = Number(this.form.weekly_theme_id || 0);
                        const themeEntry = Object.values(this.themesByWeek || {}).find((theme) => Number(theme.id) === selectedId);
                        const enName = themeEntry?.name_en || '';
                        const amName = themeEntry?.name_am || '';
                        return (this.locale === 'en' ? enName : amName) || enName || amName || '-';
                    },

                    activeMezmurCount() {
                        const active = (this.form.mezmurs || []).filter((item) => {
                            return Boolean((item.title_en || '').trim() || (item.title_am || '').trim());
                        });
                        return String(active.length);
                    },

                    activeBookCount() {
                        const active = (this.form.books || []).filter((item) => {
                            return Boolean((item.title_en || '').trim() || (item.title_am || '').trim());
                        });
                        return String(active.length);
                    },

                    serializeStepPayload() {
                        const step = this.step;
                        const payload = { step };

                        if (step === 1) {
                            payload.lent_season_id = this.form.lent_season_id;
                            payload.weekly_theme_id = this.form.weekly_theme_id;
                            payload.day_number = this.form.day_number;
                            payload.date = this.form.date;
                            payload.day_title_am = this.form.day_title_am;
                            payload.day_title_en = this.form.day_title_en;
                        } else if (step === 2) {
                            payload.bible_reference_am = this.form.bible_reference_am;
                            payload.bible_reference_en = this.form.bible_reference_en;
                            payload.bible_summary_am = this.form.bible_summary_am;
                            payload.bible_summary_en = this.form.bible_summary_en;
                            payload.bible_text_am = this.form.bible_text_am;
                            payload.bible_text_en = this.form.bible_text_en;
                        } else if (step === 3) {
                            payload.mezmurs = (this.form.mezmurs || []).map((item) => ({
                                title_en: item.title_en || '',
                                title_am: item.title_am || '',
                                url_en: item.url_en || '',
                                url_am: item.url_am || '',
                                description_en: item.description_en || '',
                                description_am: item.description_am || '',
                            }));
                        } else if (step === 4) {
                            payload.sinksar_title_am = this.form.sinksar_title_am;
                            payload.sinksar_title_en = this.form.sinksar_title_en;
                            payload.sinksar_url_en = this.form.sinksar_url_en;
                            payload.sinksar_url_am = this.form.sinksar_url_am;
                            payload.sinksar_description_am = this.form.sinksar_description_am;
                            payload.sinksar_description_en = this.form.sinksar_description_en;
                        } else if (step === 5) {
                            payload.books = (this.form.books || []).map((item) => ({
                                title_en: item.title_en || '',
                                title_am: item.title_am || '',
                                url_en: item.url_en || '',
                                url_am: item.url_am || '',
                                description_en: item.description_en || '',
                                description_am: item.description_am || '',
                            }));
                        } else if (step === 6) {
                            payload.reflection_am = this.form.reflection_am;
                            payload.reflection_en = this.form.reflection_en;
                            payload.references = (this.form.references || []).map((item) => ({
                                name_en: item.name_en || '',
                                name_am: item.name_am || '',
                                url_en: item.url_en || '',
                                url_am: item.url_am || '',
                                type: item.type || 'website',
                            }));
                        } else if (step === 7) {
                            payload.is_published = Boolean(this.form.is_published);
                        }

                        return payload;
                    },

                    async apiRequest(url, method, payload) {
                        const response = await fetch(url, {
                            method,
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify(payload),
                        });

                        let data = {};
                        try {
                            data = await response.json();
                        } catch (_error) {
                            data = {};
                        }

                        if (!response.ok) {
                            const validationMessage = data?.errors ? this.extractValidationMessage(data.errors) : '';
                            throw new Error(validationMessage || data?.message || this.messages.failed || 'Failed');
                        }

                        return data;
                    },

                    extractValidationMessage(errors) {
                        const values = Object.values(errors || {});
                        if (!values.length) {
                            return '';
                        }
                        if (Array.isArray(values[0]) && values[0].length) {
                            return values[0][0];
                        }
                        return '';
                    },

                    async saveStep({ advance = false, finish = false } = {}) {
                        this.isSaving = true;
                        this.errorMessage = '';
                        this.saveNotice = this.messages.saving || 'Saving...';

                        try {
                            const payload = this.serializeStepPayload();
                            if (this.step === 1 && !this.isEdit) {
                                const createData = await this.apiRequest(this.urls.create, 'POST', payload);
                                this.isEdit = true;
                                this.dailyId = Number(createData.daily_id);
                                if (createData.edit_url) {
                                    window.history.replaceState({}, '', createData.edit_url);
                                } else if (this.urls.editTemplate) {
                                    window.history.replaceState({}, '', this.urls.editTemplate.replace('__DAILY_ID__', String(this.dailyId)));
                                }
                            } else {
                                const patchUrl = this.urls.patchTemplate.replace('__DAILY_ID__', String(this.dailyId));
                                await this.apiRequest(patchUrl, 'PATCH', payload);
                            }

                            this.saveNotice = this.messages.saved || 'Saved';

                            if (advance) {
                                this.step = Math.min(this.step + 1, this.totalSteps);
                                this.maxUnlockedStep = Math.max(this.maxUnlockedStep, this.step);
                            }

                            if (finish) {
                                window.location.href = this.urls.index;
                            }
                        } catch (error) {
                            this.errorMessage = error.message || this.messages.failed || 'Failed';
                            this.saveNotice = '';
                        } finally {
                            this.isSaving = false;
                        }
                    },
                };
            }
        </script>
    @endif
@endsection

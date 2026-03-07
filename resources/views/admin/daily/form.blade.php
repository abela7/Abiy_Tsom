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
                    'uploadingPdf' => false,
                ])
                ->values()
                ->toArray()
            : []
    );

    $recentBooks   = $recentBooks ?? [];
    $recentMezmurs = $recentMezmurs ?? [];

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
        'sinksar_text_am' => old('sinksar_text_am', $daily->sinksar_text_am ?? ''),
        'sinksar_text_en' => old('sinksar_text_en', $daily->sinksar_text_en ?? ''),
        'sinksar_description_am' => old('sinksar_description_am', $daily->sinksar_description_am ?? ''),
        'sinksar_description_en' => old('sinksar_description_en', $daily->sinksar_description_en ?? ''),
        'books' => $initialBooks,
        'reflection_am' => old('reflection_am', $daily->reflection_am ?? ''),
        'reflection_en' => old('reflection_en', $daily->reflection_en ?? ''),
        'reflection_title_am' => old('reflection_title_am', $daily->reflection_title_am ?? ''),
        'reflection_title_en' => old('reflection_title_en', $daily->reflection_title_en ?? ''),
        'is_published' => (bool) old('is_published', $daily->is_published ?? false),
        'mezmurs' => $initialMezmurs,
        'references' => $initialReferences,
        'sinksar_images' => $isEdit
            ? ($daily->sinksarImages ?? collect())->map(fn ($img) => [
                'path' => $img->image_path,
                'url' => $img->imageUrl(),
                'caption_en' => $img->caption_en ?? '',
                'caption_am' => $img->caption_am ?? '',
            ])->values()->toArray()
            : [],
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
                recentMezmurs: @js($recentMezmurs),
                state: @js($wizardState),
                urls: {
                    create: @js(route('admin.daily.store')),
                    patchTemplate: @js(route('admin.daily.patch', ['daily' => '__DAILY_ID__'])),
                    editTemplate: @js(route('admin.daily.edit', ['daily' => '__DAILY_ID__'])),
                    index: @js(route('admin.daily.index')),
                    copyFromTemplate: @js(route('admin.daily.copy_from', ['day_number' => '__DAY__'])),
                    uploadBookPdf: @js(route('admin.daily.upload_book_pdf')),
                    uploadSinksarImage: @js(route('admin.daily.upload_sinksar_image')),
                    deleteSinksarImage: @js(route('admin.daily.delete_sinksar_image'))
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
                                class="w-8 h-8 sm:w-9 sm:h-9 shrink-0 rounded-full border-2 flex items-center justify-center transition touch-manipulation disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="step === stepNumber
                                    ? 'bg-accent text-on-accent border-accent shadow-sm'
                                    : (stepNumber < step
                                        ? 'bg-accent/20 text-accent border-accent/40'
                                        : (isStepUnlocked(stepNumber)
                                            ? 'bg-muted text-secondary border-border'
                                            : 'bg-muted/30 text-muted-text border-border/50'))"
                                :aria-label="stepLabel(stepNumber)"
                            >
                                <span x-html="stepIcons[stepNumber - 1]" class="flex items-center justify-center pointer-events-none"></span>
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

                    {{-- Previous mezmurs accordion --}}
                    @if(!empty($recentMezmurs))
                    <div x-data="{ open: false }" class="rounded-xl border border-accent-secondary/30 overflow-hidden">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center justify-between px-4 py-3 bg-accent-secondary/5 hover:bg-accent-secondary/10 transition touch-manipulation select-none">
                            <span class="text-sm font-semibold text-accent-secondary">
                                {{ __('app.recommend_from_previous') }}
                                <span class="ml-1.5 text-xs font-normal opacity-70">({{ count($recentMezmurs) }})</span>
                            </span>
                            <svg :class="open ? 'rotate-180' : ''" class="w-4 h-4 text-accent-secondary transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="divide-y divide-border/50">
                            @foreach($recentMezmurs as $rm)
                            @php($rmTitle = $locale === 'en' ? (($rm['title_en'] ?? '') ?: ($rm['title_am'] ?? '-')) : (($rm['title_am'] ?? '') ?: ($rm['title_en'] ?? '-')))
                            @php($rmUrl = $locale === 'en' ? (($rm['url_en'] ?? '') ?: ($rm['url_am'] ?? '') ?: ($rm['url'] ?? '')) : (($rm['url_am'] ?? '') ?: ($rm['url_en'] ?? '') ?: ($rm['url'] ?? '')))
                            <div class="flex items-center gap-3 px-4 py-3 bg-card hover:bg-muted/40 transition">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-primary truncate">{{ $rmTitle }}</p>
                                    @if($rmUrl)
                                    <p class="text-xs text-muted-text truncate mt-0.5">{{ $rmUrl }}</p>
                                    @endif
                                    <p class="text-xs text-muted-text/60 mt-0.5">{{ __('app.day_label') }} {{ $rm['day_number'] ?? '-' }}</p>
                                </div>
                                <button type="button"
                                        @click="addMezmurFromRecommendation(@js($rm))"
                                        class="shrink-0 px-3 py-1.5 text-xs font-semibold text-accent-secondary border border-accent-secondary/40 rounded-lg hover:bg-accent-secondary/10 transition touch-manipulation">
                                    + Use
                                </button>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

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
                    <div class="space-y-4 rounded-xl border border-sinksar/30 bg-sinksar/5 p-3">
                        <p class="text-xs font-semibold text-sinksar mb-2">{{ __('app.amharic') }}</p>
                        <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.title_label') }}</label>
                        <input type="text" x-model="form.sinksar_title_am" placeholder="{{ __('app.name_amharic_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.url_video_label') }}</label>
                        <input type="url" x-model="form.sinksar_url_am" placeholder="{{ __('app.youtube_url_placeholder') }} ({{ __('app.amharic') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.sinksar_text_label') }}</label>
                        <textarea x-model="form.sinksar_text_am" rows="6" placeholder="{{ __('app.sinksar_text_label') }} ({{ __('app.amharic') }})" class="w-full min-h-[10rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.sinksar_description_label') }}</label>
                        <textarea x-model="form.sinksar_description_am" rows="3" placeholder="{{ __('app.sinksar_description_label') }} ({{ __('app.amharic') }})" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>

                    <div class="space-y-4 rounded-xl border border-sinksar/30 bg-sinksar/5 p-3">
                        <p class="text-xs font-semibold text-sinksar mb-2">{{ __('app.english') }}</p>
                        <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.title_label') }}</label>
                        <input type="text" x-model="form.sinksar_title_en" placeholder="{{ __('app.name_english_label') }}" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.url_video_label') }}</label>
                        <input type="url" x-model="form.sinksar_url_en" placeholder="{{ __('app.youtube_url_placeholder') }} ({{ __('app.english') }})" class="w-full min-h-12 px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.sinksar_text_label') }}</label>
                        <textarea x-model="form.sinksar_text_en" rows="6" placeholder="{{ __('app.sinksar_text_label') }} ({{ __('app.english') }})" class="w-full min-h-[10rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        <label class="block text-sm font-medium text-secondary mt-3 mb-1.5">{{ __('app.sinksar_description_label') }}</label>
                        <textarea x-model="form.sinksar_description_en" rows="3" placeholder="{{ __('app.sinksar_description_label') }} ({{ __('app.english') }})" class="w-full min-h-[5rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                    </div>

                    {{-- Saint Images Upload --}}
                    <div class="space-y-4 rounded-xl border border-sinksar/30 bg-sinksar/5 p-3">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-semibold text-sinksar">{{ __('app.sinksar_images_label') }}</p>
                            <span class="text-[10px] text-muted-text" x-text="(form.sinksar_images || []).length + '/5'"></span>
                        </div>
                        <p class="text-xs text-muted-text">{{ __('app.sinksar_images_hint') }}</p>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <template x-for="(img, idx) in (form.sinksar_images || [])" :key="idx">
                                <div class="relative group rounded-xl overflow-hidden border border-border bg-muted/30">
                                    <div class="aspect-[4/3]">
                                        <img :src="img.url" class="w-full h-full object-cover" alt="">
                                    </div>
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition flex items-center justify-center aspect-[4/3]">
                                        <button type="button"
                                                @click="removeSinksarImage(idx)"
                                                class="opacity-0 group-hover:opacity-100 p-2 rounded-full bg-red-600 text-white transition touch-manipulation">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="p-2 bg-card border-t border-border">
                                        <input type="text" x-model="img.caption_am" placeholder="{{ __('app.sinksar_image_caption') }} ({{ __('app.amharic') }})"
                                               class="w-full text-xs px-2 py-1.5 border border-border rounded-lg bg-muted/30 mb-1">
                                        <input type="text" x-model="img.caption_en" placeholder="{{ __('app.sinksar_image_caption') }} ({{ __('app.english') }})"
                                               class="w-full text-xs px-2 py-1.5 border border-border rounded-lg bg-muted/30">
                                    </div>
                                </div>
                            </template>

                            <div x-show="(form.sinksar_images || []).length < 5"
                                 class="rounded-xl border-2 border-dashed border-sinksar/30 hover:border-sinksar/60 bg-sinksar/5 flex items-center justify-center cursor-pointer transition group touch-manipulation">
                                <label class="flex flex-col items-center gap-2 cursor-pointer p-4 aspect-[4/3] justify-center w-full">
                                    <svg class="w-8 h-8 text-sinksar/50 group-hover:text-sinksar transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    <span class="text-xs font-semibold text-sinksar/70 text-center" x-text="sinksarImageUploading ? '{{ __('app.loading') }}...' : '{{ __('app.sinksar_upload_image') }}'"></span>
                                    <input type="file"
                                           accept="image/jpeg,image/png,image/webp"
                                           class="hidden"
                                           :disabled="sinksarImageUploading"
                                           @change="uploadSinksarImage($event)">
                                </label>
                            </div>
                        </div>
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
                                    <div class="space-y-2">
                                        <label class="text-xs font-semibold text-book">{{ __('app.upload_book_pdf') }}</label>
                                        <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                                            <label class="inline-flex items-center justify-center px-3 py-2 text-xs sm:text-sm min-h-10 rounded-lg border border-book/40 bg-white hover:bg-book/10 transition cursor-pointer touch-manipulation">
                                                <span x-text="book.uploadingPdf ? '{{ __('app.loading') }}...' : '{{ __('app.upload_book_pdf') }}'"></span>
                                                <input
                                                    type="file"
                                                    accept="application/pdf"
                                                    class="hidden"
                                                    :disabled="book.uploadingPdf"
                                                    @change="uploadBookPdf(index, $event)"
                                                >
                                            </label>
                                                <span class="text-xs text-muted-text" x-text="isBookPdfUrl(book.url_en || book.url_am || '') ? '{{ __('app.pdf_uploaded') }}' : '{{ __('app.pdf_not_uploaded') }}'"></span>
                                            </div>
                                        </div>
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
                        <p class="text-xs font-semibold text-muted-text">{{ __('app.reflection_label') }}</p>
                        <div class="space-y-2 rounded-lg bg-muted border border-border p-3">
                            <p class="text-xs font-semibold text-accent-secondary">{{ __('app.amharic') }}</p>
                            <label class="block text-sm font-medium text-secondary">{{ __('app.reflection_title_label') ?? 'Title (optional)' }}</label>
                            <input type="text" x-model="form.reflection_title_am" placeholder="e.g. ስለ ጸሎት" class="w-full px-4 py-2.5 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                            <label class="block text-sm font-medium text-secondary mt-2">{{ __('app.reflection_label') }}</label>
                            <textarea x-model="form.reflection_am" rows="4" placeholder="{{ __('app.amharic_default') }}" class="w-full min-h-[6rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        </div>
                        <div class="space-y-2 rounded-lg bg-muted border border-border p-3">
                            <p class="text-xs font-semibold text-accent-secondary">{{ __('app.english') }}</p>
                            <label class="block text-sm font-medium text-secondary">{{ __('app.reflection_title_label') ?? 'Title (optional)' }}</label>
                            <input type="text" x-model="form.reflection_title_en" placeholder="e.g. About Prayer" class="w-full px-4 py-2.5 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition">
                            <label class="block text-sm font-medium text-secondary mt-2">{{ __('app.reflection_label') }}</label>
                            <textarea x-model="form.reflection_en" rows="4" placeholder="{{ __('app.english_fallback') }}" class="w-full min-h-[6rem] px-4 py-3 text-base border border-border rounded-xl bg-muted/30 focus:ring-2 focus:ring-accent focus:bg-card outline-none transition"></textarea>
                        </div>
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
                    recentMezmurs: Array.isArray(config.recentMezmurs) ? config.recentMezmurs : [],
                    stepIcons: [
                        // 1 Day info — calendar
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>',
                        // 2 Bible reading — open book
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                        // 3 Mezmur — music note
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>',
                        // 4 Sinksar — scroll/document
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>',
                        // 5 Spiritual book — bookmark
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>',
                        // 6 Reflection & refs — lightbulb
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m1.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>',
                        // 7 Review & publish — eye
                        '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>',
                    ],
                    urls: config.urls || {},
                    messages: config.messages || {},
                    daysWithContent: Array.isArray(config.daysWithContent) ? config.daysWithContent : [],
                    locale: config.locale || 'am',
                    resolvedInfo: null,
                    form: config.state || {},
                    copySourceDay: '',
                    isCopying: false,
                    copyNotice: '',
                    sinksarImageUploading: false,

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
                        if (!Array.isArray(this.form.sinksar_images)) {
                            this.form.sinksar_images = [];
                        }
                        this.form.books = (this.form.books || []).map((book) => ({
                            title_en: book.title_en || '',
                            title_am: book.title_am || '',
                            url_en: book.url_en || '',
                            url_am: book.url_am || '',
                            description_en: book.description_en || '',
                            description_am: book.description_am || '',
                            uploadingPdf: Boolean(book.uploadingPdf),
                        }));
                        this.syncFromDayNumber();
                    },

                    isBookPdfUrl(url) {
                        const clean = String(url || '').split('?')[0].split('#')[0].toLowerCase();
                        return clean.endsWith('.pdf');
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
                            uploadingPdf: false,
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
                            uploadingPdf: false,
                        });
                    },

                    addMezmurFromRecommendation(m) {
                        const blank = this.form.mezmurs.length === 1 &&
                            !this.form.mezmurs[0].title_am && !this.form.mezmurs[0].title_en &&
                            !this.form.mezmurs[0].url_am && !this.form.mezmurs[0].url_en;
                        const entry = {
                            title_en: m.title_en || '',
                            title_am: m.title_am || '',
                            url_en: m.url_en || m.url || '',
                            url_am: m.url_am || m.url || '',
                            description_en: m.description_en || '',
                            description_am: m.description_am || '',
                        };
                        if (blank) {
                            this.form.mezmurs[0] = entry;
                        } else {
                            this.form.mezmurs.push(entry);
                        }
                    },

                    async uploadBookPdf(index, event) {
                        const input = event?.target;
                        const file = input?.files?.[0];
                        if (!input || !file) {
                            return;
                        }

                        this.form.books[index].uploadingPdf = true;
                        this.errorMessage = '';

                        const formData = new FormData();
                        formData.append('book_pdf', file);
                        formData.append('_token', document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || '');

                        try {
                            const response = await fetch(this.urls.uploadBookPdf, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json' },
                                body: formData,
                            });

                            let data = {};
                            try {
                                data = await response.json();
                            } catch (_error) {
                                data = {};
                            }

                            if (!response.ok || !data?.success) {
                                throw new Error(data?.message || this.messages.failed || 'Failed');
                            }

                            const pdfUrl = data.url_en || data.url_am || data.url || '';
                            this.form.books[index].url_en = pdfUrl;
                            this.form.books[index].url_am = pdfUrl;
                            input.value = '';
                        } catch (error) {
                            this.errorMessage = error.message || this.messages.failed || 'Failed';
                        } finally {
                            this.form.books[index].uploadingPdf = false;
                        }
                    },

                    async uploadSinksarImage(event) {
                        const input = event?.target;
                        const file = input?.files?.[0];
                        if (!input || !file) return;
                        if ((this.form.sinksar_images || []).length >= 5) return;

                        this.sinksarImageUploading = true;
                        this.errorMessage = '';

                        const formData = new FormData();
                        formData.append('sinksar_image', file);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');

                        try {
                            const response = await fetch(this.urls.uploadSinksarImage, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: { 'Accept': 'application/json' },
                                body: formData,
                            });
                            let data = {};
                            try { data = await response.json(); } catch (_) { data = {}; }
                            if (!response.ok || !data?.success) {
                                throw new Error(data?.message || this.messages.failed || 'Failed');
                            }
                            if (!this.form.sinksar_images) this.form.sinksar_images = [];
                            this.form.sinksar_images.push({
                                path: data.path,
                                url: data.url,
                                caption_en: '',
                                caption_am: '',
                            });
                            input.value = '';
                        } catch (error) {
                            this.errorMessage = error.message || this.messages.failed || 'Failed';
                        } finally {
                            this.sinksarImageUploading = false;
                        }
                    },

                    removeSinksarImage(index) {
                        if (!this.form.sinksar_images) return;
                        const removed = this.form.sinksar_images.splice(index, 1);
                        if (removed[0]?.path) {
                            fetch(this.urls.deleteSinksarImage, {
                                method: 'POST',
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                },
                                body: JSON.stringify({ path: removed[0].path }),
                            }).catch(() => {});
                        }
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
                            this.form.sinksar_text_en = data.sinksar_text_en ?? '';
                            this.form.sinksar_text_am = data.sinksar_text_am ?? '';
                            this.form.sinksar_description_en = data.sinksar_description_en ?? '';
                            this.form.sinksar_description_am = data.sinksar_description_am ?? '';
                            this.form.reflection_en = data.reflection_en ?? '';
                            this.form.reflection_am = data.reflection_am ?? '';
                            this.form.reflection_title_en = data.reflection_title_en ?? '';
                            this.form.reflection_title_am = data.reflection_title_am ?? '';
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
                                uploadingPdf: false,
                            })) : [];
                            this.form.sinksar_images = Array.isArray(data.sinksar_images) ? data.sinksar_images.map((img) => ({
                                path: img.path || '',
                                url: img.url || '',
                                caption_en: img.caption_en || '',
                                caption_am: img.caption_am || '',
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
                            payload.sinksar_text_am = this.form.sinksar_text_am;
                            payload.sinksar_text_en = this.form.sinksar_text_en;
                            payload.sinksar_description_am = this.form.sinksar_description_am;
                            payload.sinksar_description_en = this.form.sinksar_description_en;
                            payload.sinksar_images = (this.form.sinksar_images || []).map((img) => ({
                                path: img.path || '',
                                caption_en: img.caption_en || '',
                                caption_am: img.caption_am || '',
                            }));
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
                            payload.reflection_title_am = this.form.reflection_title_am;
                            payload.reflection_title_en = this.form.reflection_title_en;
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


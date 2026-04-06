@extends('layouts.admin')

@section('title', __('app.himamat_edit_title'))

@php
    $synaxariumSource = old('synaxarium_source', $day->synaxarium_source ?? 'automatic');
    $synaxariumMonth = old('synaxarium_month', $day->synaxarium_month);
    $synaxariumDay = old('synaxarium_day', $day->synaxarium_day);
    $introSlot = $day->slots->firstWhere('slot_key', 'intro');
    $slotStatusLabels = [
        'intro' => __('app.himamat_slot_7am'),
        'third' => __('app.himamat_slot_9am'),
        'sixth' => __('app.himamat_slot_12pm'),
        'ninth' => __('app.himamat_slot_3pm'),
        'eleventh' => __('app.himamat_slot_5pm'),
    ];
    $dayReminderTime = old('day_reminder_time', $introSlot ? substr((string) $introSlot->scheduled_time_london, 0, 5) : '07:00');
    $dayReminderTitleEn = old('day_reminder_title_en', $introSlot?->reminder_header_en);
    $dayReminderTitleAm = old('day_reminder_title_am', $introSlot?->reminder_header_am);
    $faqItems = old('faqs');
    if ($faqItems === null) {
        $faqItems = $day->faqs
            ->map(fn ($faq) => [
                'id' => $faq->id,
                'question_en' => $faq->question_en,
                'question_am' => $faq->question_am,
                'answer_en' => $faq->answer_en,
                'answer_am' => $faq->answer_am,
            ])
            ->values()
            ->all();
    }

    $annualCelebrations = collect($ethDateInfo['annual_celebrations'] ?? [])
        ->map(fn ($celebration) => localized($celebration, 'celebration') ?? $celebration->celebration_en ?? null)
        ->filter()
        ->unique()
        ->values();

    $monthlyCelebrations = collect($ethDateInfo['monthly_celebrations'] ?? [])
        ->map(fn ($celebration) => localized($celebration, 'celebration') ?? $celebration->celebration_en ?? null)
        ->filter()
        ->unique()
        ->values();
    $linkedDaily = $linkedDaily ?? null;
    $linkedDailyReturnStep = $linkedDailyReturnStep ?? 3;
    $unpublishedSlots = $day->slots
        ->filter(fn ($slot) => ! $slot->is_published)
        ->map(fn ($slot) => $slotStatusLabels[$slot->slot_key] ?? (localized($slot, 'slot_header') ?? $slot->slot_header_en))
        ->values();

    $slotIcons = [
        'intro' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>',
        'third' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>',
        'sixth' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v18m0-18a9 9 0 110 18 9 9 0 010-18zm0 0c-2 2-3 5-3 9s1 7 3 9m0-18c2 2 3 5 3 9s-1 7-3 9"/>',
        'ninth' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
        'eleventh' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>',
    ];
@endphp

@section('content')
<div class="max-w-2xl mx-auto px-0 sm:px-0">

    {{-- Page Header --}}
    <div class="mb-5">
        <div class="flex items-center gap-2 mb-2">
            <a href="{{ route('admin.himamat.index') }}"
               class="w-8 h-8 rounded-lg bg-muted flex items-center justify-center text-muted-text hover:text-primary hover:bg-border transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-primary truncate">{{ localized($day, 'title') ?? $day->title_en }}</h1>
                <p class="text-xs text-muted-text">{{ $day->date?->format('D, d M Y') }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.himamat.preview', ['day' => $day->getKey()]) }}"
               target="_blank" rel="noopener"
               class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                {{ __('app.himamat_admin_preview') }}
            </a>
        </div>
    </div>

    @if($linkedDaily)
        @include('admin.partials.himamat-handoff-card', [
            'dayNumber' => $linkedDaily->day_number,
            'ctaHref' => route('admin.daily.edit', ['daily' => $linkedDaily->getKey(), 'step' => $linkedDailyReturnStep]),
            'ctaLabel' => __('app.himamat_daily_continue_content'),
            'currentLabel' => __('app.himamat_title'),
            'currentItems' => [
                __('app.step_day_info'),
                __('app.himamat_timeline_editor_title'),
                __('app.step_bible_reading'),
            ],
            'linkedLabel' => __('app.daily_content'),
            'linkedTitle' => localized($linkedDaily, 'day_title') ?? $linkedDaily->day_title_en ?? __('app.edit_day', ['day' => $linkedDaily->day_number]),
            'linkedDate' => $linkedDaily->date?->format('D, d M Y'),
            'linkedItems' => [
                __('app.step_mezmur'),
                __('app.step_sinksar'),
                __('app.daily_message'),
            ],
        ])
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger">
            <p class="font-semibold">{{ __('app.himamat_editor_fix_errors') }}</p>
            <ul class="mt-1 list-disc pl-5 space-y-0.5 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Live Status — compact inline badges --}}
    <div class="mb-4 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $day->is_published ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
            <span class="w-1.5 h-1.5 rounded-full {{ $day->is_published ? 'bg-success' : 'bg-danger' }}"></span>
            {{ __('app.himamat_live_status_day') }}
        </span>
        @foreach($day->slots as $slot)
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $slot->is_published ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $slot->is_published ? 'bg-success' : 'bg-danger' }}"></span>
                {{ $slotStatusLabels[$slot->slot_key] ?? $slot->slot_header_en }}
            </span>
        @endforeach
    </div>

    @if(! $day->is_published || $unpublishedSlots->isNotEmpty())
        <div class="mb-4 rounded-xl border border-danger/20 bg-danger/5 px-3 py-2.5 text-xs text-danger">
            @if(! $day->is_published)
                <p>{{ __('app.himamat_live_status_day_warning') }}</p>
            @endif
            @if($unpublishedSlots->isNotEmpty())
                <p class="{{ ! $day->is_published ? 'mt-1' : '' }}">
                    {{ __('app.himamat_live_status_slot_warning', ['slots' => $unpublishedSlots->implode(', ')]) }}
                </p>
            @endif
        </div>
    @endif

    {{-- ═══════════ FORM ═══════════ --}}
    <form action="{{ route('admin.himamat.update', ['day' => $day->getKey()]) }}" method="POST" enctype="multipart/form-data"
          class="space-y-3"
          x-ref="form"
          x-data="{
              synaxariumSource: @js($synaxariumSource),
              dayReminderTitleEn: @js($dayReminderTitleEn),
              dayReminderTitleAm: @js($dayReminderTitleAm),
              saveMode: 'exit',
              saveSection: '',
              openSection: null,
              toggle(id) { this.openSection = this.openSection === id ? null : id; },
              initFromHash() {
                  const map = {
                      'himamat-global-info': 'info',
                      'himamat-synaxarium': 'synaxarium',
                      'himamat-faq': 'faq',
                      @foreach($day->slots as $slot)'himamat-slot-{{ $slot->slot_key }}': 'slot-{{ $slot->slot_key }}',@endforeach
                  };
                  const hash = window.location.hash.replace('#', '');
                  if (hash && map[hash]) { this.openSection = map[hash]; }
              },
              saveDraft(sectionId) {
                  this.saveMode = 'stay';
                  this.saveSection = sectionId;
                  this.$nextTick(() => this.$refs.form.submit());
              }
          }"
          x-init="initFromHash()">
        @csrf
        @method('PUT')
        <input type="hidden" name="save_mode" x-model="saveMode">
        <input type="hidden" name="save_section" x-model="saveSection">

        {{-- ─── 1. Day Info ─── --}}
        <section id="himamat-global-info" class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
            <button type="button" @click="toggle('info')"
                    class="w-full flex items-center justify-between gap-3 px-4 py-4 text-left group touch-manipulation">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-primary">{{ __('app.himamat_global_info_title') }}</h2>
                        <p class="text-[11px] text-muted-text mt-0.5 truncate">{{ __('app.himamat_global_info_hint') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <label class="inline-flex items-center gap-1.5 text-xs font-medium text-primary" @click.stop>
                        <input type="hidden" name="is_published" value="0">
                        <input type="checkbox" name="is_published" value="1" {{ old('is_published', $day->is_published) ? 'checked' : '' }}
                               class="h-3.5 w-3.5 rounded border-border text-accent focus:ring-accent">
                        {{ __('app.published') }}
                    </label>
                    <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="openSection === 'info' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </button>

            <div x-show="openSection === 'info'" x-cloak x-collapse>
                <div class="px-4 pb-4 pt-0 border-t border-border/30">
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.title') }} (EN)</label>
                            <input type="text" name="title_en" value="{{ old('title_en', $day->title_en) }}"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.title') }} (AM)</label>
                            <input type="text" name="title_am" value="{{ old('title_am', $day->title_am) }}"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.date') }}</label>
                            <input type="date" name="date" value="{{ old('date', $day->date?->format('Y-m-d')) }}"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_day_reminder_time') }}</label>
                            <input type="time" name="day_reminder_time" value="{{ $dayReminderTime }}" step="60"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                            <p class="mt-1 text-[11px] text-secondary">{{ __('app.himamat_day_reminder_time_hint') }}</p>
                        </div>
                        <div class="rounded-xl border border-border bg-muted/50 px-3 py-2.5">
                            <p class="text-[11px] font-semibold text-muted-text">{{ __('app.himamat_timezone_label') }}</p>
                            <p class="mt-1 text-sm font-semibold text-primary">{{ __('app.himamat_timezone_value') }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_day_reminder_title') }} (EN)</label>
                            <input type="text" name="day_reminder_title_en" x-model="dayReminderTitleEn"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_day_reminder_title') }} (AM)</label>
                            <input type="text" name="day_reminder_title_am" x-model="dayReminderTitleAm"
                                   class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_day_meaning_title') }} (EN)</label>
                            <textarea name="spiritual_meaning_en" rows="5"
                                      class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('spiritual_meaning_en', $day->spiritual_meaning_en) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_day_meaning_title') }} (AM)</label>
                            <textarea name="spiritual_meaning_am" rows="5"
                                      class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('spiritual_meaning_am', $day->spiritual_meaning_am) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_ritual_intro_title') }} (EN)</label>
                            <textarea name="ritual_guide_intro_en" rows="4"
                                      class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('ritual_guide_intro_en', $day->ritual_guide_intro_en) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_ritual_intro_title') }} (AM)</label>
                            <textarea name="ritual_guide_intro_am" rows="4"
                                      class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('ritual_guide_intro_am', $day->ritual_guide_intro_am) }}</textarea>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="saveDraft('himamat-global-info')"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-muted px-3.5 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('app.himamat_save_draft') }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── 2. Synaxarium ─── --}}
        <section id="himamat-synaxarium" class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
            <button type="button" @click="toggle('synaxarium')"
                    class="w-full flex items-center justify-between gap-3 px-4 py-4 text-left group touch-manipulation">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-primary">{{ __('app.himamat_synaxarium_title') }}</h2>
                        @if(($ethDateInfo['ethiopian_date_formatted'] ?? null))
                            <p class="text-[11px] text-muted-text mt-0.5">{{ $ethDateInfo['ethiopian_date_formatted'] }}</p>
                        @endif
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="openSection === 'synaxarium' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="openSection === 'synaxarium'" x-cloak x-collapse>
                <div class="px-4 pb-4 pt-0 border-t border-border/30">
                    <div class="mt-4 space-y-3">
                        {{-- Source selector --}}
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label class="flex items-start gap-2.5 rounded-xl border border-border bg-muted px-3 py-3 text-sm cursor-pointer" :class="synaxariumSource === 'automatic' && 'ring-2 ring-accent border-accent'">
                                <input type="radio" name="synaxarium_source" value="automatic" x-model="synaxariumSource" class="mt-0.5 h-3.5 w-3.5 border-border text-accent focus:ring-accent">
                                <span class="text-xs"><span class="block font-semibold text-primary">{{ __('app.himamat_synaxarium_source_automatic') }}</span><span class="mt-0.5 block text-secondary">{{ __('app.himamat_synaxarium_source_automatic_help') }}</span></span>
                            </label>
                            <label class="flex items-start gap-2.5 rounded-xl border border-border bg-muted px-3 py-3 text-sm cursor-pointer" :class="synaxariumSource === 'manual' && 'ring-2 ring-accent border-accent'">
                                <input type="radio" name="synaxarium_source" value="manual" x-model="synaxariumSource" class="mt-0.5 h-3.5 w-3.5 border-border text-accent focus:ring-accent">
                                <span class="text-xs"><span class="block font-semibold text-primary">{{ __('app.himamat_synaxarium_source_manual') }}</span><span class="mt-0.5 block text-secondary">{{ __('app.himamat_synaxarium_source_manual_help') }}</span></span>
                            </label>
                        </div>

                        <div x-show="synaxariumSource === 'manual'" x-cloak class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.ethiopian_month') }}</label>
                                <select name="synaxarium_month" class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                    <option value="">{{ __('app.select') }}</option>
                                    @foreach($ethiopianMonthOptions as $monthNumber => $monthLabel)
                                        <option value="{{ $monthNumber }}" {{ (string) $synaxariumMonth === (string) $monthNumber ? 'selected' : '' }}>{{ $monthLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.day') }}</label>
                                <select name="synaxarium_day" class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                    <option value="">{{ __('app.select') }}</option>
                                    @for($d = 1; $d <= 30; $d++)
                                        <option value="{{ $d }}" {{ (string) $synaxariumDay === (string) $d ? 'selected' : '' }}>{{ $d }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- Celebrations preview --}}
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div class="rounded-xl border border-border/60 bg-muted/30 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-muted-text">{{ __('app.himamat_synaxarium_annual') }}</p>
                                @if($annualCelebrations->isNotEmpty())
                                    @foreach($annualCelebrations as $c) <p class="text-xs text-primary mt-1">{{ $c }}</p> @endforeach
                                @else
                                    <p class="text-xs text-secondary mt-1">{{ __('app.himamat_synaxarium_empty') }}</p>
                                @endif
                            </div>
                            <div class="rounded-xl border border-border/60 bg-muted/30 p-3">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-muted-text">{{ __('app.himamat_synaxarium_monthly') }}</p>
                                @if($monthlyCelebrations->isNotEmpty())
                                    @foreach($monthlyCelebrations as $c) <p class="text-xs text-primary mt-1">{{ $c }}</p> @endforeach
                                @else
                                    <p class="text-xs text-secondary mt-1">{{ __('app.himamat_synaxarium_empty') }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Custom entry fields --}}
                        <div class="rounded-xl border border-border/60 bg-muted/30 p-3 space-y-3">
                            <p class="text-[10px] font-bold uppercase tracking-wider text-muted-text">{{ __('app.himamat_synaxarium_entry_title') }}</p>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_synaxarium_entry_heading') }} (EN)</label>
                                    <input type="text" name="synaxarium_title_en" value="{{ old('synaxarium_title_en', $day->synaxarium_title_en) }}"
                                           class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_synaxarium_entry_heading') }} (AM)</label>
                                    <input type="text" name="synaxarium_title_am" value="{{ old('synaxarium_title_am', $day->synaxarium_title_am) }}"
                                           class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_synaxarium_entry_body') }} (EN)</label>
                                <textarea name="synaxarium_text_en" rows="4" class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('synaxarium_text_en', $day->synaxarium_text_en) }}</textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_synaxarium_entry_body') }} (AM)</label>
                                <textarea name="synaxarium_text_am" rows="4" class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('synaxarium_text_am', $day->synaxarium_text_am) }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="saveDraft('himamat-synaxarium')"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-muted px-3.5 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('app.himamat_save_draft') }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── 3. FAQs ─── --}}
        <section id="himamat-faq" class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden"
                 x-data="himamatFaqEditor(@js($faqItems))">
            <button type="button" @click="toggle('faq')"
                    class="w-full flex items-center justify-between gap-3 px-4 py-4 text-left group touch-manipulation">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 1.918-2 3.522-2 2.209 0 4 1.567 4 3.5 0 1.418-.964 2.638-2.347 3.188-.74.294-1.153.838-1.153 1.412V16m.01 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm font-bold text-primary">{{ __('app.himamat_faq_title') }}</h2>
                        <p class="text-[11px] text-muted-text mt-0.5" x-text="faqs.length + ' {{ strtolower(__('app.himamat_faq_title')) }}'"></p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="openSection === 'faq' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="openSection === 'faq'" x-cloak x-collapse>
                <div class="px-4 pb-4 pt-0 border-t border-border/30">
                    <div class="mt-4 space-y-3">
                        <template x-for="(faq, index) in faqs" :key="faq.uid">
                            <div class="rounded-xl border border-border/60 bg-muted/30 p-3">
                                <div class="flex items-center justify-between gap-2 mb-3">
                                    <p class="text-xs font-bold text-primary" x-text="'Q' + (index + 1)"></p>
                                    <button type="button" @click="removeFaq(index)"
                                            class="text-[10px] font-semibold text-danger hover:text-danger/80 transition">{{ __('app.himamat_faq_remove') }}</button>
                                </div>
                                <input type="hidden" :name="`faqs[${index}][id]`" x-model="faq.id">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_faq_question') }} (EN)</label>
                                        <input type="text" :name="`faqs[${index}][question_en]`" x-model="faq.question_en"
                                               class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_faq_question') }} (AM)</label>
                                        <input type="text" :name="`faqs[${index}][question_am]`" x-model="faq.question_am"
                                               class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_faq_answer') }} (EN)</label>
                                        <textarea rows="3" :name="`faqs[${index}][answer_en]`" x-model="faq.answer_en"
                                                  class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_faq_answer') }} (AM)</label>
                                        <textarea rows="3" :name="`faqs[${index}][answer_am]`" x-model="faq.answer_am"
                                                  class="mt-1.5 w-full rounded-xl border border-border bg-card px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                        <button type="button" @click="addFaq()"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-accent/40 bg-accent/5 px-3.5 py-2 text-xs font-semibold text-accent transition hover:bg-accent/10">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('app.himamat_faq_add') }}
                        </button>
                        <button type="button" @click="saveDraft('himamat-faq')"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-muted px-3.5 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            {{ __('app.himamat_save_draft') }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ─── 4. Timeline Slots ─── --}}
        @foreach($day->slots as $index => $slot)
            @php
                $slotResources = old("slots.$index.resources");
                if ($slotResources === null) {
                    $slotResources = $slot->resources
                        ->map(fn ($resource) => [
                            'id' => $resource->id,
                            'type' => $resource->type,
                            'title_en' => $resource->title_en,
                            'title_am' => $resource->title_am,
                            'text_en' => $resource->text_en,
                            'text_am' => $resource->text_am,
                            'url' => $resource->url,
                            'file_path' => $resource->file_path,
                            'file_url' => $resource->resolvedUrl(),
                        ])
                        ->values()
                        ->all();
                }
                $iconPath = $slotIcons[$slot->slot_key] ?? $slotIcons['intro'];
            @endphp

            <section id="himamat-slot-{{ $slot->slot_key }}" class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
                <button type="button" @click="toggle('slot-{{ $slot->slot_key }}')"
                        class="w-full flex items-center justify-between gap-3 px-4 py-4 text-left group touch-manipulation">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iconPath !!}</svg>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-primary">{{ localized($slot, 'slot_header') ?? $slot->slot_header_en }}</h2>
                            <p class="text-[11px] text-muted-text mt-0.5">{{ $slotStatusLabels[$slot->slot_key] ?? '' }} &middot; {{ substr((string) $slot->scheduled_time_london, 0, 5) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label class="inline-flex items-center gap-1.5 text-xs font-medium text-primary" @click.stop>
                            <input type="hidden" name="slots[{{ $index }}][is_published]" value="0">
                            <input type="checkbox" name="slots[{{ $index }}][is_published]" value="1" {{ old("slots.$index.is_published", $slot->is_published) ? 'checked' : '' }}
                                   class="h-3.5 w-3.5 rounded border-border text-accent focus:ring-accent">
                            {{ __('app.published') }}
                        </label>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="openSection === 'slot-{{ $slot->slot_key }}' && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </button>

                <div x-show="openSection === 'slot-{{ $slot->slot_key }}'" x-cloak x-collapse>
                    <div class="px-4 pb-4 pt-0 border-t border-border/30">
                        <input type="hidden" name="slots[{{ $index }}][id]" value="{{ $slot->id }}">

                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_hour_title') }} (EN)</label>
                                <input type="text" name="slots[{{ $index }}][slot_header_en]" value="{{ old("slots.$index.slot_header_en", $slot->slot_header_en) }}"
                                       class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_hour_title') }} (AM)</label>
                                <input type="text" name="slots[{{ $index }}][slot_header_am]" value="{{ old("slots.$index.slot_header_am", $slot->slot_header_am) }}"
                                       class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                            </div>

                            @if($slot->slot_key === 'intro')
                                <input type="hidden" name="slots[{{ $index }}][reminder_header_en]" :value="dayReminderTitleEn">
                                <input type="hidden" name="slots[{{ $index }}][reminder_header_am]" :value="dayReminderTitleAm">
                                <div class="sm:col-span-2 rounded-xl border border-border/60 bg-muted/30 px-3 py-2.5">
                                    <p class="text-[11px] font-semibold text-muted-text">{{ __('app.himamat_day_reminder_title') }}</p>
                                    <p class="mt-1 text-xs text-secondary">{{ __('app.himamat_day_reminder_managed_note') }}</p>
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_slot_reminder_title') }} (EN)</label>
                                    <input type="text" name="slots[{{ $index }}][reminder_header_en]" value="{{ old("slots.$index.reminder_header_en", $slot->reminder_header_en) }}"
                                           class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_slot_reminder_title') }} (AM)</label>
                                    <input type="text" name="slots[{{ $index }}][reminder_header_am]" value="{{ old("slots.$index.reminder_header_am", $slot->reminder_header_am) }}"
                                           class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_slot_reminder_content') }} (EN)</label>
                                    <textarea name="slots[{{ $index }}][reminder_content_en]" rows="3"
                                              class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reminder_content_en", $slot->reminder_content_en) }}</textarea>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_slot_reminder_content') }} (AM)</label>
                                    <textarea name="slots[{{ $index }}][reminder_content_am]" rows="3"
                                              class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reminder_content_am", $slot->reminder_content_am) }}</textarea>
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_bible_reference') }} (EN)</label>
                                <input type="text" name="slots[{{ $index }}][reading_reference_en]" value="{{ old("slots.$index.reading_reference_en", $slot->reading_reference_en) }}"
                                       class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_bible_reference') }} (AM)</label>
                                <input type="text" name="slots[{{ $index }}][reading_reference_am]" value="{{ old("slots.$index.reading_reference_am", $slot->reading_reference_am) }}"
                                       class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_bible_passage') }} (EN)</label>
                                <textarea name="slots[{{ $index }}][reading_text_en]" rows="4"
                                          class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_en", $slot->reading_text_en) }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_bible_passage') }} (AM)</label>
                                <textarea name="slots[{{ $index }}][reading_text_am]" rows="4"
                                          class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_am", $slot->reading_text_am) }}</textarea>
                            </div>

                            {{-- Resources --}}
                            <div class="sm:col-span-2" x-data="himamatSlotResourceEditor(@js($slotResources))">
                                <div class="rounded-xl border border-border/60 bg-muted/30 p-3">
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                        <button type="button" @click="addResource()"
                                                class="inline-flex items-center gap-1 rounded-lg border border-dashed border-accent/40 bg-accent/5 px-2.5 py-1.5 text-[10px] font-semibold text-accent transition hover:bg-accent/10">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            {{ __('app.himamat_resource_add') }}
                                        </button>
                                    </div>

                                    <template x-if="resources.length === 0">
                                        <div class="rounded-lg border border-dashed border-border bg-card px-3 py-3 text-xs text-secondary text-center">
                                            {{ __('app.himamat_resource_empty') }}
                                        </div>
                                    </template>

                                    <div class="space-y-3">
                                        <template x-for="(resource, resourceIndex) in resources" :key="resource.uid">
                                            <div class="rounded-xl border border-border bg-card p-3">
                                                <div class="flex items-center justify-between gap-2 mb-3">
                                                    <p class="text-xs font-bold text-primary" x-text="resourceHeading(resource, resourceIndex)"></p>
                                                    <button type="button" @click="removeResource(resourceIndex)"
                                                            class="text-[10px] font-semibold text-danger hover:text-danger/80 transition">{{ __('app.himamat_resource_remove') }}</button>
                                                </div>

                                                <input type="hidden" :name="`slots[{{ $index }}][resources][${resourceIndex}][id]`" x-model="resource.id">
                                                <input type="hidden" :name="`slots[{{ $index }}][resources][${resourceIndex}][file_path]`" x-model="resource.file_path">

                                                <div class="grid gap-3 sm:grid-cols-2">
                                                    <div>
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_type') }}</label>
                                                        <select :name="`slots[{{ $index }}][resources][${resourceIndex}][type]`" x-model="resource.type"
                                                                class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                                            <option value="video">{{ __('app.himamat_resource_type_video') }}</option>
                                                            <option value="website">{{ __('app.himamat_resource_type_website') }}</option>
                                                            <option value="pdf">{{ __('app.himamat_resource_type_pdf') }}</option>
                                                            <option value="photo">{{ __('app.himamat_resource_type_photo') }}</option>
                                                            <option value="text">{{ __('app.himamat_resource_type_text') }}</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_title') }} (EN)</label>
                                                        <input type="text" :name="`slots[{{ $index }}][resources][${resourceIndex}][title_en]`" x-model="resource.title_en"
                                                               class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_title') }} (AM)</label>
                                                        <input type="text" :name="`slots[{{ $index }}][resources][${resourceIndex}][title_am]`" x-model="resource.title_am"
                                                               class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                                    </div>
                                                    <div x-show="resource.type !== 'text'">
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_url') }}</label>
                                                        <input type="url" :name="`slots[{{ $index }}][resources][${resourceIndex}][url]`" x-model="resource.url"
                                                               class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                                    </div>
                                                    <div class="sm:col-span-2" x-show="resource.type === 'text'">
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_text') }} (EN)</label>
                                                        <textarea :name="`slots[{{ $index }}][resources][${resourceIndex}][text_en]`" x-model="resource.text_en" rows="5"
                                                                  class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                                    </div>
                                                    <div class="sm:col-span-2" x-show="resource.type === 'text'">
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_text') }} (AM)</label>
                                                        <textarea :name="`slots[{{ $index }}][resources][${resourceIndex}][text_am]`" x-model="resource.text_am" rows="5"
                                                                  class="mt-1.5 w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                                    </div>
                                                    <div class="sm:col-span-2" x-show="resource.type === 'pdf' || resource.type === 'photo'">
                                                        <label class="block text-xs font-semibold text-muted-text">{{ __('app.himamat_resource_upload') }}</label>
                                                        <input type="file"
                                                               :name="`slots[{{ $index }}][resources][${resourceIndex}][upload]`"
                                                               :accept="resource.type === 'photo' ? '.jpg,.jpeg,.png,.webp' : '.pdf'"
                                                               class="mt-1.5 block w-full rounded-xl border border-border bg-muted px-3 py-2.5 text-sm text-primary file:mr-3 file:rounded-lg file:border-0 file:bg-accent file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-on-accent">
                                                        <div class="mt-2 rounded-lg border border-border/60 bg-muted/30 p-2" x-show="resource.file_path || resource.file_url">
                                                            <template x-if="resource.type === 'photo' && (resource.file_url || resource.url)">
                                                                <img :src="resource.file_url || resource.url" alt="" class="h-24 w-full rounded-lg object-cover">
                                                            </template>
                                                            <template x-if="resource.type !== 'photo'">
                                                                <a :href="resource.file_url || resource.url" target="_blank" rel="noopener"
                                                                   class="inline-flex items-center gap-1.5 text-xs font-semibold text-accent hover:text-accent/80">
                                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    {{ __('app.himamat_resource_open') }}
                                                                </a>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="saveDraft('himamat-slot-{{ $slot->slot_key }}')"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-border bg-muted px-3.5 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                {{ __('app.himamat_save_draft') }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach

        {{-- Final Save --}}
        <div class="sticky bottom-0 z-20 -mx-4 px-4 py-3 bg-gradient-to-t from-surface via-surface to-transparent">
            <button type="submit"
                    @click="saveMode = 'exit'; saveSection = ''"
                    class="w-full rounded-xl bg-accent px-5 py-3 text-sm font-bold text-on-accent transition hover:bg-accent-hover shadow-lg shadow-accent/20">
                {{ __('app.save_changes') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function himamatFaqEditor(initialFaqs) {
    return {
        faqs: (() => {
            const items = Array.isArray(initialFaqs) && initialFaqs.length ? initialFaqs : [{}];
            return items.map((faq, index) => ({
                uid: `faq-${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
                id: faq.id ?? '',
                question_en: faq.question_en ?? '',
                question_am: faq.question_am ?? '',
                answer_en: faq.answer_en ?? '',
                answer_am: faq.answer_am ?? '',
            }));
        })(),
        addFaq() {
            this.faqs.push({
                uid: `faq-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                id: '',
                question_en: '',
                question_am: '',
                answer_en: '',
                answer_am: '',
            });
        },
        removeFaq(index) {
            this.faqs.splice(index, 1);
            if (this.faqs.length === 0) {
                this.addFaq();
            }
        },
    };
}

function himamatSlotResourceEditor(initialResources) {
    return {
        resources: (() => {
            if (!Array.isArray(initialResources) || initialResources.length === 0) {
                return [];
            }

            return initialResources.map((resource, index) => ({
                uid: `resource-${Date.now()}-${index}-${Math.random().toString(36).slice(2, 8)}`,
                id: resource.id ?? '',
                type: resource.type ?? 'website',
                title_en: resource.title_en ?? '',
                title_am: resource.title_am ?? '',
                text_en: resource.text_en ?? '',
                text_am: resource.text_am ?? '',
                url: resource.url ?? '',
                file_path: resource.file_path ?? '',
                file_url: resource.file_url ?? '',
            }));
        })(),
        addResource() {
            this.resources.push({
                uid: `resource-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
                id: '',
                type: 'website',
                title_en: '',
                title_am: '',
                text_en: '',
                text_am: '',
                url: '',
                file_path: '',
                file_url: '',
            });
        },
        removeResource(index) {
            this.resources.splice(index, 1);
        },
        resourceHeading(resource, index) {
            return resource.title_en || resource.title_am || `{{ __('app.himamat_resource_fallback') }}`.replace(':number', index + 1);
        },
    };
}
</script>
@endpush

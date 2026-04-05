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
    $unpublishedSlots = $day->slots
        ->filter(fn ($slot) => ! $slot->is_published)
        ->map(fn ($slot) => $slotStatusLabels[$slot->slot_key] ?? (localized($slot, 'slot_header') ?? $slot->slot_header_en))
        ->values();
@endphp

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.himamat_edit_title') }}</h1>
        <p class="mt-1 text-sm text-secondary">{{ localized($day, 'title') ?? $day->title_en }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.himamat.preview', ['day' => $day->getKey()]) }}"
           target="_blank" rel="noopener"
           class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
            {{ __('app.himamat_admin_preview') }}
        </a>
        <a href="{{ route('admin.himamat.index') }}"
           class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-muted">
            {{ __('app.back') }}
        </a>
    </div>
</div>

@if($errors->any())
    <div class="mb-5 rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger">
        <p class="font-semibold">{{ __('app.himamat_editor_fix_errors') }}</p>
        <ul class="mt-2 list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="mb-5 rounded-2xl border border-border bg-card p-5 shadow-sm">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_live_status_title') }}</p>
        <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_live_status_title') }}</h2>
        <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_live_status_hint') }}</p>
    </div>

    <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-2xl border border-border bg-muted/40 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_live_status_day') }}</p>
            <p class="mt-2 text-sm font-semibold {{ $day->is_published ? 'text-success' : 'text-danger' }}">
                {{ $day->is_published ? __('app.himamat_live_status_live') : __('app.himamat_live_status_draft') }}
            </p>
        </div>

        @foreach($day->slots as $slot)
            <div class="rounded-2xl border border-border bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">
                    {{ __('app.himamat_live_status_slot', ['slot' => $slotStatusLabels[$slot->slot_key] ?? (localized($slot, 'slot_header') ?? $slot->slot_header_en)]) }}
                </p>
                <p class="mt-2 text-sm font-semibold {{ $slot->is_published ? 'text-success' : 'text-danger' }}">
                    {{ $slot->is_published ? __('app.himamat_live_status_live') : __('app.himamat_live_status_draft') }}
                </p>
            </div>
        @endforeach
    </div>

    @if(! $day->is_published || $unpublishedSlots->isNotEmpty())
        <div class="mt-4 rounded-2xl border border-danger/20 bg-danger/5 px-4 py-3 text-sm text-danger">
            @if(! $day->is_published)
                <p>{{ __('app.himamat_live_status_day_warning') }}</p>
            @endif

            @if($unpublishedSlots->isNotEmpty())
                <p class="{{ ! $day->is_published ? 'mt-2' : '' }}">
                    {{ __('app.himamat_live_status_slot_warning', ['slots' => $unpublishedSlots->implode(', ')]) }}
                </p>
            @endif
        </div>
    @endif
</section>

<form action="{{ route('admin.himamat.update', ['day' => $day->getKey()]) }}" method="POST" enctype="multipart/form-data" class="space-y-5"
      x-ref="form"
      x-data="{
          synaxariumSource: @js($synaxariumSource),
          dayReminderTitleEn: @js($dayReminderTitleEn),
          dayReminderTitleAm: @js($dayReminderTitleAm),
          saveMode: 'exit',
          saveSection: '',
          saveDraft(sectionId) {
              this.saveMode = 'stay';
              this.saveSection = sectionId;
              this.$nextTick(() => this.$refs.form.submit());
          }
      }">
    @csrf
    @method('PUT')
    <input type="hidden" name="save_mode" x-model="saveMode">
    <input type="hidden" name="save_section" x-model="saveSection">

    <section id="himamat-global-info" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_global_info_title') }}</p>
                <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_global_info_title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_global_info_hint') }}</p>
            </div>
            <label class="inline-flex items-center gap-3 rounded-xl border border-border bg-muted px-4 py-3 text-sm font-medium text-primary">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" {{ old('is_published', $day->is_published) ? 'checked' : '' }}
                       class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
                {{ __('app.published') }}
            </label>
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.title') }} (EN)</label>
                <input type="text" name="title_en" value="{{ old('title_en', $day->title_en) }}"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.title') }} (AM)</label>
                <input type="text" name="title_am" value="{{ old('title_am', $day->title_am) }}"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.date') }}</label>
                <input type="date" name="date" value="{{ old('date', $day->date?->format('Y-m-d')) }}"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_reminder_time') }}</label>
                <input type="time" name="day_reminder_time" value="{{ $dayReminderTime }}" step="60"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_day_reminder_time_hint') }}</p>
            </div>
            <div class="rounded-xl border border-border bg-muted px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timezone_label') }}</p>
                <p class="mt-2 text-sm font-semibold text-primary">{{ __('app.himamat_timezone_value') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_reminder_title') }} (EN)</label>
                <input type="text" name="day_reminder_title_en" x-model="dayReminderTitleEn"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_day_reminder_title_hint') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_reminder_title') }} (AM)</label>
                <input type="text" name="day_reminder_title_am" x-model="dayReminderTitleAm"
                       class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_day_reminder_title_hint') }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_meaning_title') }} (EN)</label>
                <textarea name="spiritual_meaning_en" rows="7"
                          class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('spiritual_meaning_en', $day->spiritual_meaning_en) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_meaning_title') }} (AM)</label>
                <textarea name="spiritual_meaning_am" rows="7"
                          class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('spiritual_meaning_am', $day->spiritual_meaning_am) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }} (EN)</label>
                <textarea name="ritual_guide_intro_en" rows="5"
                          class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('ritual_guide_intro_en', $day->ritual_guide_intro_en) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }} (AM)</label>
                <textarea name="ritual_guide_intro_am" rows="5"
                          class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('ritual_guide_intro_am', $day->ritual_guide_intro_am) }}</textarea>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button"
                    @click="saveDraft('himamat-global-info')"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.himamat_save_draft') }}
            </button>
        </div>
    </section>

    <section id="himamat-synaxarium" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_title') }}</p>
                <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_synaxarium_title') }}</h2>
                <p class="mt-1 text-sm text-secondary" x-show="synaxariumSource === 'automatic'">{{ __('app.himamat_synaxarium_auto_note') }}</p>
                <p class="mt-1 text-sm text-secondary" x-show="synaxariumSource === 'manual'">{{ __('app.himamat_synaxarium_manual_note') }}</p>
            </div>
            @if(($ethDateInfo['ethiopian_date_formatted'] ?? null))
                <span class="rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-secondary">
                    {{ $ethDateInfo['ethiopian_date_formatted'] }}
                </span>
            @endif
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_source_title') }}</label>
                <div class="mt-2 grid gap-3 md:grid-cols-2">
                    <label class="inline-flex items-start gap-3 rounded-2xl border border-border bg-muted px-4 py-4 text-sm text-primary">
                        <input type="radio" name="synaxarium_source" value="automatic" x-model="synaxariumSource"
                               class="mt-0.5 h-4 w-4 border-border text-accent focus:ring-accent">
                        <span>
                            <span class="block font-semibold">{{ __('app.himamat_synaxarium_source_automatic') }}</span>
                            <span class="mt-1 block text-secondary">{{ __('app.himamat_synaxarium_source_automatic_help') }}</span>
                        </span>
                    </label>
                    <label class="inline-flex items-start gap-3 rounded-2xl border border-border bg-muted px-4 py-4 text-sm text-primary">
                        <input type="radio" name="synaxarium_source" value="manual" x-model="synaxariumSource"
                               class="mt-0.5 h-4 w-4 border-border text-accent focus:ring-accent">
                        <span>
                            <span class="block font-semibold">{{ __('app.himamat_synaxarium_source_manual') }}</span>
                            <span class="mt-1 block text-secondary">{{ __('app.himamat_synaxarium_source_manual_help') }}</span>
                        </span>
                    </label>
                </div>
            </div>
            <div x-show="synaxariumSource === 'manual'" x-cloak>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.ethiopian_month') }}</label>
                <select name="synaxarium_month"
                        class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    <option value="">{{ __('app.select') }}</option>
                    @foreach($ethiopianMonthOptions as $monthNumber => $monthLabel)
                        <option value="{{ $monthNumber }}" {{ (string) $synaxariumMonth === (string) $monthNumber ? 'selected' : '' }}>
                            {{ $monthLabel }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div x-show="synaxariumSource === 'manual'" x-cloak>
                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.day') }}</label>
                <select name="synaxarium_day"
                        class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    <option value="">{{ __('app.select') }}</option>
                    @for($d = 1; $d <= 30; $d++)
                        <option value="{{ $d }}" {{ (string) $synaxariumDay === (string) $d ? 'selected' : '' }}>
                            {{ $d }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_annual') }}</p>
                @if($annualCelebrations->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($annualCelebrations as $celebration)
                            <p class="text-sm leading-relaxed text-primary">{{ $celebration }}</p>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-secondary">{{ __('app.himamat_synaxarium_empty') }}</p>
                @endif
            </div>
            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_monthly') }}</p>
                @if($monthlyCelebrations->isNotEmpty())
                    <div class="mt-3 space-y-2">
                        @foreach($monthlyCelebrations as $celebration)
                            <p class="text-sm leading-relaxed text-primary">{{ $celebration }}</p>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm text-secondary">{{ __('app.himamat_synaxarium_empty') }}</p>
                @endif
            </div>
            <div class="md:col-span-2 rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_entry_title') }}</p>
                <p class="mt-2 text-sm text-secondary">{{ __('app.himamat_synaxarium_entry_hint') }}</p>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_entry_heading') }} (EN)</label>
                        <input type="text" name="synaxarium_title_en" value="{{ old('synaxarium_title_en', $day->synaxarium_title_en) }}"
                               class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_entry_heading') }} (AM)</label>
                        <input type="text" name="synaxarium_title_am" value="{{ old('synaxarium_title_am', $day->synaxarium_title_am) }}"
                               class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_entry_body') }} (EN)</label>
                        <textarea name="synaxarium_text_en" rows="6"
                                  class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('synaxarium_text_en', $day->synaxarium_text_en) }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_entry_body') }} (AM)</label>
                        <textarea name="synaxarium_text_am" rows="6"
                                  class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old('synaxarium_text_am', $day->synaxarium_text_am) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button"
                    @click="saveDraft('himamat-synaxarium')"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.himamat_save_draft') }}
            </button>
        </div>
    </section>

    <section id="himamat-faq" class="rounded-2xl border border-border bg-card p-5 shadow-sm"
             x-data="himamatFaqEditor(@js($faqItems))">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_title') }}</p>
                <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_faq_title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_faq_intro') }}</p>
            </div>
            <button type="button"
                    @click="addFaq()"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.himamat_faq_add') }}
            </button>
        </div>

        <div class="mt-5 space-y-4">
            <template x-for="(faq, index) in faqs" :key="faq.uid">
                <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-primary" x-text="`${index + 1}. {{ __('app.himamat_faq_title') }}`"></p>
                        <button type="button"
                                @click="removeFaq(index)"
                                class="inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-2 text-xs font-semibold text-secondary transition hover:bg-muted">
                            {{ __('app.himamat_faq_remove') }}
                        </button>
                    </div>

                    <input type="hidden" :name="`faqs[${index}][id]`" x-model="faq.id">

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_question') }} (EN)</label>
                            <input type="text"
                                   :name="`faqs[${index}][question_en]`"
                                   x-model="faq.question_en"
                                   class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_question') }} (AM)</label>
                            <input type="text"
                                   :name="`faqs[${index}][question_am]`"
                                   x-model="faq.question_am"
                                   class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_answer') }} (EN)</label>
                            <textarea rows="4"
                                      :name="`faqs[${index}][answer_en]`"
                                      x-model="faq.answer_en"
                                      class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_answer') }} (AM)</label>
                            <textarea rows="4"
                                      :name="`faqs[${index}][answer_am]`"
                                      x-model="faq.answer_am"
                                      class="mt-2 w-full rounded-xl border border-border bg-card px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 flex justify-end">
            <button type="button"
                    @click="saveDraft('himamat-faq')"
                    class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                {{ __('app.himamat_save_draft') }}
            </button>
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timeline_editor_title') }}</p>
            <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_timeline_editor_title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_timeline_editor_hint') }}</p>
        </div>
    </section>

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
        @endphp
        <section id="himamat-slot-{{ $slot->slot_key }}" class="rounded-2xl border border-border bg-card p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ substr((string) $slot->scheduled_time_london, 0, 5) }}</p>
                    <h2 class="mt-1 text-lg font-bold text-primary">{{ localized($slot, 'slot_header') ?? $slot->slot_header_en }}</h2>
                </div>
                <label class="inline-flex items-center gap-2 rounded-xl border border-border bg-muted px-3 py-2 text-sm font-medium text-primary">
                    <input type="hidden" name="slots[{{ $index }}][is_published]" value="0">
                    <input type="checkbox" name="slots[{{ $index }}][is_published]" value="1" {{ old("slots.$index.is_published", $slot->is_published) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
                    {{ __('app.published') }}
                </label>
            </div>

            <input type="hidden" name="slots[{{ $index }}][id]" value="{{ $slot->id }}">

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_title') }} (EN)</label>
                    <input type="text" name="slots[{{ $index }}][slot_header_en]" value="{{ old("slots.$index.slot_header_en", $slot->slot_header_en) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_title') }} (AM)</label>
                    <input type="text" name="slots[{{ $index }}][slot_header_am]" value="{{ old("slots.$index.slot_header_am", $slot->slot_header_am) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                @if($slot->slot_key === 'intro')
                    <input type="hidden" name="slots[{{ $index }}][reminder_header_en]" :value="dayReminderTitleEn">
                    <input type="hidden" name="slots[{{ $index }}][reminder_header_am]" :value="dayReminderTitleAm">
                    <div class="md:col-span-2 rounded-2xl border border-border/80 bg-muted/40 p-4">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_reminder_title') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_day_reminder_managed_note') }}</p>
                    </div>
                @else
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_slot_reminder_title') }} (EN)</label>
                        <input type="text" name="slots[{{ $index }}][reminder_header_en]" value="{{ old("slots.$index.reminder_header_en", $slot->reminder_header_en) }}"
                               class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_slot_reminder_title') }} (AM)</label>
                        <input type="text" name="slots[{{ $index }}][reminder_header_am]" value="{{ old("slots.$index.reminder_header_am", $slot->reminder_header_am) }}"
                               class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                    </div>
                @endif
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_reference') }} (EN)</label>
                    <input type="text" name="slots[{{ $index }}][reading_reference_en]" value="{{ old("slots.$index.reading_reference_en", $slot->reading_reference_en) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_reference') }} (AM)</label>
                    <input type="text" name="slots[{{ $index }}][reading_reference_am]" value="{{ old("slots.$index.reading_reference_am", $slot->reading_reference_am) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_passage') }} (EN)</label>
                    <textarea name="slots[{{ $index }}][reading_text_en]" rows="5"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_en", $slot->reading_text_en) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_passage') }} (AM)</label>
                    <textarea name="slots[{{ $index }}][reading_text_am]" rows="5"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_am", $slot->reading_text_am) }}</textarea>
                </div>
                <div class="md:col-span-2"
                     x-data="himamatSlotResourceEditor(@js($slotResources))">
                    <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_hour_resources_hint') }}</p>
                            </div>
                            <button type="button"
                                    @click="addResource()"
                                    class="inline-flex items-center justify-center rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-muted">
                                {{ __('app.himamat_resource_add') }}
                            </button>
                        </div>

                        <div class="mt-4 space-y-4">
                            <template x-if="resources.length === 0">
                                <div class="rounded-xl border border-dashed border-border bg-card px-4 py-4 text-sm text-secondary">
                                    {{ __('app.himamat_resource_empty') }}
                                </div>
                            </template>

                            <template x-for="(resource, resourceIndex) in resources" :key="resource.uid">
                                <div class="rounded-2xl border border-border bg-card p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-primary" x-text="resourceHeading(resource, resourceIndex)"></p>
                                        <button type="button"
                                                @click="removeResource(resourceIndex)"
                                                class="inline-flex items-center justify-center rounded-lg border border-border bg-muted px-3 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                                            {{ __('app.himamat_resource_remove') }}
                                        </button>
                                    </div>

                                    <input type="hidden" :name="`slots[{{ $index }}][resources][${resourceIndex}][id]`" x-model="resource.id">
                                    <input type="hidden" :name="`slots[{{ $index }}][resources][${resourceIndex}][file_path]`" x-model="resource.file_path">

                                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_type') }}</label>
                                            <select :name="`slots[{{ $index }}][resources][${resourceIndex}][type]`"
                                                    x-model="resource.type"
                                                    class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                                <option value="video">{{ __('app.himamat_resource_type_video') }}</option>
                                                <option value="website">{{ __('app.himamat_resource_type_website') }}</option>
                                                <option value="pdf">{{ __('app.himamat_resource_type_pdf') }}</option>
                                                <option value="photo">{{ __('app.himamat_resource_type_photo') }}</option>
                                                <option value="text">{{ __('app.himamat_resource_type_text') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_title') }} (EN)</label>
                                            <input type="text"
                                                   :name="`slots[{{ $index }}][resources][${resourceIndex}][title_en]`"
                                                   x-model="resource.title_en"
                                                   class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_title') }} (AM)</label>
                                            <input type="text"
                                                   :name="`slots[{{ $index }}][resources][${resourceIndex}][title_am]`"
                                                   x-model="resource.title_am"
                                                   class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                        </div>
                                        <div x-show="resource.type !== 'text'">
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_url') }}</label>
                                            <input type="url"
                                                   :name="`slots[{{ $index }}][resources][${resourceIndex}][url]`"
                                                   x-model="resource.url"
                                                   class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                                            <p class="mt-2 text-xs text-secondary" x-show="resource.type === 'video' || resource.type === 'website'">
                                                {{ __('app.himamat_resource_url_hint') }}
                                            </p>
                                            <p class="mt-2 text-xs text-secondary" x-show="resource.type === 'pdf' || resource.type === 'photo'">
                                                {{ __('app.himamat_resource_url_optional_hint') }}
                                            </p>
                                        </div>
                                        <div class="md:col-span-2" x-show="resource.type === 'text'">
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_text') }} (EN)</label>
                                            <textarea :name="`slots[{{ $index }}][resources][${resourceIndex}][text_en]`"
                                                      x-model="resource.text_en"
                                                      rows="10"
                                                      class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_resource_text_hint') }}</p>
                                        </div>
                                        <div class="md:col-span-2" x-show="resource.type === 'text'">
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_text') }} (AM)</label>
                                            <textarea :name="`slots[{{ $index }}][resources][${resourceIndex}][text_am]`"
                                                      x-model="resource.text_am"
                                                      rows="10"
                                                      class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent"></textarea>
                                            <p class="mt-2 text-xs text-secondary">{{ __('app.himamat_resource_text_hint') }}</p>
                                        </div>
                                        <div class="md:col-span-2" x-show="resource.type === 'pdf' || resource.type === 'photo'">
                                            <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_upload') }}</label>
                                            <input type="file"
                                                   :name="`slots[{{ $index }}][resources][${resourceIndex}][upload]`"
                                                   :accept="resource.type === 'photo' ? '.jpg,.jpeg,.png,.webp' : '.pdf'"
                                                   class="mt-2 block w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary file:mr-4 file:rounded-lg file:border-0 file:bg-accent file:px-3 file:py-2 file:text-sm file:font-semibold file:text-on-accent">

                                            <div class="mt-3 rounded-xl border border-border/70 bg-muted/30 p-3" x-show="resource.file_path || resource.file_url">
                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_resource_current_file') }}</p>
                                                <template x-if="resource.type === 'photo' && (resource.file_url || resource.url)">
                                                    <img :src="resource.file_url || resource.url"
                                                         alt=""
                                                         class="mt-3 h-40 w-full rounded-xl object-cover">
                                                </template>
                                                <template x-if="resource.type !== 'photo'">
                                                    <a :href="resource.file_url || resource.url"
                                                       target="_blank" rel="noopener"
                                                       class="mt-3 inline-flex items-center justify-center rounded-lg border border-border bg-card px-3 py-2 text-sm font-semibold text-secondary transition hover:bg-muted">
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

            <div class="mt-5 flex justify-end">
                <button type="button"
                        @click="saveDraft('himamat-slot-{{ $slot->slot_key }}')"
                        class="inline-flex items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border">
                    {{ __('app.himamat_save_draft') }}
                </button>
            </div>
        </section>
    @endforeach

    <div class="flex justify-end">
        <button type="submit"
                @click="saveMode = 'exit'; saveSection = ''"
                class="inline-flex items-center justify-center rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-on-accent transition hover:bg-accent-hover">
            {{ __('app.save_changes') }}
        </button>
    </div>
</form>
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

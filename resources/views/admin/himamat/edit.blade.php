@extends('layouts.admin')

@section('title', __('app.himamat_edit_title'))

@php
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
            {{ __('app.view') }}
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

<form action="{{ route('admin.himamat.update', ['day' => $day->getKey()]) }}" method="POST" class="space-y-5">
    @csrf
    @method('PUT')

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
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
            <div class="rounded-xl border border-border bg-muted px-4 py-3">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timezone_label') }}</p>
                <p class="mt-2 text-sm font-semibold text-primary">{{ __('app.himamat_timezone_value') }}</p>
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
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_title') }}</p>
                <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_synaxarium_title') }}</h2>
                <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_synaxarium_auto_note') }}</p>
            </div>
            @if(($ethDateInfo['ethiopian_date_formatted'] ?? null))
                <span class="rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-secondary">
                    {{ $ethDateInfo['ethiopian_date_formatted'] }}
                </span>
            @endif
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
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
        </div>
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm"
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
    </section>

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timeline_editor_title') }}</p>
            <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_timeline_editor_title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_timeline_editor_hint') }}</p>
        </div>
    </section>

    @foreach($day->slots as $index => $slot)
        <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
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
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">Slot Header (EN)</label>
                    <input type="text" name="slots[{{ $index }}][slot_header_en]" value="{{ old("slots.$index.slot_header_en", $slot->slot_header_en) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">Slot Header (AM)</label>
                    <input type="text" name="slots[{{ $index }}][slot_header_am]" value="{{ old("slots.$index.slot_header_am", $slot->slot_header_am) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">Reminder Header (EN)</label>
                    <input type="text" name="slots[{{ $index }}][reminder_header_en]" value="{{ old("slots.$index.reminder_header_en", $slot->reminder_header_en) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">Reminder Header (AM)</label>
                    <input type="text" name="slots[{{ $index }}][reminder_header_am]" value="{{ old("slots.$index.reminder_header_am", $slot->reminder_header_am) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_significance_title') }} (EN)</label>
                    <textarea name="slots[{{ $index }}][spiritual_significance_en]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.spiritual_significance_en", $slot->spiritual_significance_en) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_significance_title') }} (AM)</label>
                    <textarea name="slots[{{ $index }}][spiritual_significance_am]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.spiritual_significance_am", $slot->spiritual_significance_am) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reading_title') }} Ref (EN)</label>
                    <input type="text" name="slots[{{ $index }}][reading_reference_en]" value="{{ old("slots.$index.reading_reference_en", $slot->reading_reference_en) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reading_title') }} Ref (AM)</label>
                    <input type="text" name="slots[{{ $index }}][reading_reference_am]" value="{{ old("slots.$index.reading_reference_am", $slot->reading_reference_am) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reading_title') }} Text (EN)</label>
                    <textarea name="slots[{{ $index }}][reading_text_en]" rows="5"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_en", $slot->reading_text_en) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_reading_title') }} Text (AM)</label>
                    <textarea name="slots[{{ $index }}][reading_text_am]" rows="5"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.reading_text_am", $slot->reading_text_am) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bows_title') }}</label>
                    <input type="number" min="0" max="500" name="slots[{{ $index }}][prostration_count]" value="{{ old("slots.$index.prostration_count", $slot->prostration_count) }}"
                           class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bows_title') }} Guidance (EN)</label>
                    <textarea name="slots[{{ $index }}][prostration_guidance_en]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.prostration_guidance_en", $slot->prostration_guidance_en) }}</textarea>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bows_title') }} Guidance (AM)</label>
                    <textarea name="slots[{{ $index }}][prostration_guidance_am]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.prostration_guidance_am", $slot->prostration_guidance_am) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_prayer_title') }} (EN)</label>
                    <textarea name="slots[{{ $index }}][short_prayer_en]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.short_prayer_en", $slot->short_prayer_en) }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_prayer_title') }} (AM)</label>
                    <textarea name="slots[{{ $index }}][short_prayer_am]" rows="4"
                              class="mt-2 w-full rounded-xl border border-border bg-muted px-4 py-3 text-sm text-primary outline-none focus:ring-2 focus:ring-accent">{{ old("slots.$index.short_prayer_am", $slot->short_prayer_am) }}</textarea>
                </div>
            </div>
        </section>
    @endforeach

    <div class="flex justify-end">
        <button type="submit"
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
</script>
@endpush

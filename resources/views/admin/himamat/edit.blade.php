@extends('layouts.admin')

@section('title', __('app.himamat_edit_title'))

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

<form action="{{ route('admin.himamat.update', ['day' => $day->getKey()]) }}" method="POST" class="space-y-5">
    @csrf
    @method('PUT')

    <section class="rounded-2xl border border-border bg-card p-5 shadow-sm">
        <div class="grid gap-4 md:grid-cols-2">
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
            <div class="flex items-end">
                <label class="inline-flex items-center gap-3 rounded-xl border border-border bg-muted px-4 py-3 text-sm font-medium text-primary">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', $day->is_published) ? 'checked' : '' }}
                           class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
                    {{ __('app.published') }}
                </label>
            </div>
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

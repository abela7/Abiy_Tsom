@extends('layouts.admin')
@php($isEdit = isset($daily) && $daily->exists)
@section('title', $isEdit ? __('app.edit_day', ['day' => $daily->day_number]) : __('app.create_daily_content'))

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ $isEdit ? __('app.edit_day', ['day' => $daily->day_number]) : __('app.create_daily_content') }}</h1>

    <form method="POST" action="{{ $isEdit ? route('admin.daily.update', $daily) : route('admin.daily.store') }}"
          class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <input type="hidden" name="lent_season_id" value="{{ $season?->id }}">

        @php
            $dayRangesByWeek = $dayRangesByWeek ?? [];
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4" x-data="{
            seasonStart: '{{ $season?->start_date?->format('Y-m-d') ?? '' }}',
            themesByWeek: {{ json_encode($themes?->keyBy('week_number') ?? []) }},
            dayRangesByWeek: {{ json_encode($dayRangesByWeek) }},
            resolvedInfo: null,
            syncFromDay() {
                const day = parseInt(this.$refs?.dayNumber?.value || 0, 10);
                if (!day || day < 1 || day > 55 || !this.seasonStart) {
                    this.resolvedInfo = null;
                    return;
                }
                const d = new Date(this.seasonStart);
                d.setDate(d.getDate() + day - 1);
                const dateStr = d.toISOString().slice(0, 10);
                if (this.$refs?.dateInput) this.$refs.dateInput.value = dateStr;
                let info = null;
                for (const [w, range] of Object.entries(this.dayRangesByWeek || {})) {
                    const [start, end] = range;
                    if (day >= start && day <= end) {
                        const theme = this.themesByWeek[w];
                        const dayOfWeek = day - start + 1;
                        info = { week: parseInt(w, 10), themeName: theme?.name_en ?? 'Week ' + w, dayOfWeek, dayStart: start, dayEnd: end };
                        if (theme && this.$refs?.themeSelect) this.$refs.themeSelect.value = theme.id;
                        break;
                    }
                }
                this.resolvedInfo = info;
            }
        }" x-init="$nextTick(() => { syncFromDay(); $watch('$refs.dayNumber?.value', () => syncFromDay()); })">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.day_number_label') }}</label>
                <input type="number" name="day_number" min="1" max="55" value="{{ old('day_number', $daily->day_number ?? '') }}" required
                       x-ref="dayNumber" @input="syncFromDay()"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.date_label') }}</label>
                <input type="date" name="date" x-ref="dateInput" value="{{ old('date', $isEdit ? $daily->date->format('Y-m-d') : '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.weekly_theme_label') }}</label>
                <select name="weekly_theme_id" x-ref="themeSelect" required class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                    <option value="">{{ __('app.select_placeholder') }}</option>
                    @foreach($themes as $theme)
                        <option value="{{ $theme->id }}" {{ old('weekly_theme_id', $daily->weekly_theme_id ?? '') == $theme->id ? 'selected' : '' }}>
                            @php $range = \App\Services\AbiyTsomStructure::getDayRangeForWeek($theme->week_number); @endphp
                            Week {{ $theme->week_number }} - {{ $theme->name_en }} (Days {{ $range[0] }}-{{ $range[1] }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div x-show="resolvedInfo" x-transition class="flex items-center gap-2 px-3 py-2 rounded-lg bg-accent/10 border border-accent/20 text-sm">
            <span class="font-semibold text-accent" x-text="resolvedInfo ? 'Week ' + resolvedInfo.week + ' (' + resolvedInfo.themeName + ')' : ''"></span>
            <span class="text-muted-text">—</span>
            <span x-text="resolvedInfo ? 'Day ' + resolvedInfo.dayOfWeek + ' of week (Days ' + resolvedInfo.dayStart + '-' + resolvedInfo.dayEnd + ')' : ''"></span>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-secondary">{{ __('app.day_title_optional') }}</label>
            <div>
                <span class="text-xs text-muted-text">{{ __('app.amharic_default') }}</span>
                <input type="text" name="day_title_am" value="{{ old('day_title_am', $daily->day_title_am ?? '') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none mt-0.5">
            </div>
            <div>
                <span class="text-xs text-muted-text">{{ __('app.english_fallback') }}</span>
                <input type="text" name="day_title_en" value="{{ old('day_title_en', $daily->day_title_en ?? '') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none mt-0.5">
            </div>
        </div>

        {{-- Bible Reading --}}
        <fieldset class="border border-border rounded-lg p-4 space-y-3">
            <legend class="text-sm font-semibold text-accent px-2">{{ __('app.bible_reading_label') }}</legend>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.reference_placeholder') }}</label>
                <input type="text" name="bible_reference_am" value="{{ old('bible_reference_am', $daily->bible_reference_am ?? '') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.amharic') }}">
                <input type="text" name="bible_reference_en" value="{{ old('bible_reference_en', $daily->bible_reference_en ?? '') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.english') }}">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.summary_label') }}</label>
                <textarea name="bible_summary_am" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.amharic') }}">{{ old('bible_summary_am', $daily->bible_summary_am ?? '') }}</textarea>
                <textarea name="bible_summary_en" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.english') }}">{{ old('bible_summary_en', $daily->bible_summary_en ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.bible_text_en_label') }}</label>
                <textarea name="bible_text_en" rows="6" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.bible_text_en_placeholder') }}">{{ old('bible_text_en', $daily->bible_text_en ?? '') }}</textarea>
                <p class="text-xs text-muted-text mt-1">{{ __('app.shown_when_english') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.bible_text_am_label') }}</label>
                <textarea name="bible_text_am" rows="6" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.bible_text_am_placeholder') }}">{{ old('bible_text_am', $daily->bible_text_am ?? '') }}</textarea>
                <p class="text-xs text-muted-text mt-1">{{ __('app.shown_when_amharic') }}</p>
            </div>
        </fieldset>

        {{-- Mezmur (multiple) — add 2 or more as needed --}}
        <fieldset class="border border-border rounded-lg p-4 space-y-3"
                 x-data="{
                     mezmurs: {{ json_encode(old('mezmurs', isset($daily) && $daily->exists ? $daily->mezmurs->map(fn($m) => ['title_en' => $m->title_en ?? '', 'title_am' => $m->title_am ?? '', 'url' => $m->url ?? '', 'description_en' => $m->description_en ?? '', 'description_am' => $m->description_am ?? ''])->values()->toArray() : [['title_en' => '', 'title_am' => '', 'url' => '', 'description_en' => '', 'description_am' => '']])) }},
                     addMezmur() { this.mezmurs.push({ title_en: '', title_am: '', url: '', description_en: '', description_am: '' }); },
                     removeMezmur(i) { this.mezmurs.splice(i, 1); }
                 }">
            <legend class="text-sm font-semibold text-accent-secondary px-2">{{ __('app.mezmur_label') }}</legend>
            <p class="text-xs text-muted-text">{{ __('app.add_mezmur_hint') }}</p>
            <template x-for="(m, i) in mezmurs" :key="i">
                <div class="p-3 rounded-lg bg-accent-secondary/5 border border-accent-secondary/20 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-medium text-accent-secondary" x-text="'Mezmur ' + (i + 1)"></span>
                        <button type="button" @click="removeMezmur(i)" class="p-1 text-muted-text hover:text-error transition" title="{{ __('app.remove') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    <input :name="'mezmurs[' + i + '][title_am]'" x-model="m.title_am" type="text" placeholder="{{ __('app.name_amharic_label') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                    <input :name="'mezmurs[' + i + '][title_en]'" x-model="m.title_en" type="text" placeholder="{{ __('app.name_english_label') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                    <input :name="'mezmurs[' + i + '][url]'" x-model="m.url" type="url" placeholder="{{ __('app.url_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                    <textarea :name="'mezmurs[' + i + '][description_am]'" x-model="m.description_am" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.amharic') }})"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm"></textarea>
                    <textarea :name="'mezmurs[' + i + '][description_en]'" x-model="m.description_en" rows="2" placeholder="{{ __('app.description_label') }} ({{ __('app.english') }})"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm"></textarea>
                </div>
            </template>
            <button type="button" @click="addMezmur()"
                    class="w-full py-2 border border-dashed border-accent-secondary/30 rounded-lg text-sm text-accent-secondary hover:bg-accent-secondary/10 transition">
                + {{ __('app.mezmur_label') }}
            </button>
        </fieldset>

        {{-- Sinksar (Synaxarium) — YouTube/video link like Mezmur --}}
        <fieldset class="border border-border rounded-lg p-4 space-y-3">
            <legend class="text-sm font-semibold text-sinksar px-2">{{ __('app.sinksar_label') }}</legend>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.title_label') }}</label>
                <input type="text" name="sinksar_title_am" value="{{ old('sinksar_title_am', $daily->sinksar_title_am ?? '') }}" placeholder="{{ __('app.amharic') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                <input type="text" name="sinksar_title_en" value="{{ old('sinksar_title_en', $daily->sinksar_title_en ?? '') }}" placeholder="{{ __('app.english') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.url_video_label') }}</label>
                <input type="url" name="sinksar_url" value="{{ old('sinksar_url', $daily->sinksar_url ?? '') }}" placeholder="{{ __('app.youtube_url_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.description_label') }}</label>
                <textarea name="sinksar_description_am" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.amharic') }}">{{ old('sinksar_description_am', $daily->sinksar_description_am ?? '') }}</textarea>
                <textarea name="sinksar_description_en" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.english') }}">{{ old('sinksar_description_en', $daily->sinksar_description_en ?? '') }}</textarea>
            </div>
        </fieldset>

        {{-- Spiritual Book --}}
        <fieldset class="border border-border rounded-lg p-4 space-y-3">
            <legend class="text-sm font-semibold text-book px-2">{{ __('app.spiritual_book_label') }}</legend>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.title_label') }}</label>
                <input type="text" name="book_title_am" value="{{ old('book_title_am', $daily->book_title_am ?? '') }}" placeholder="{{ __('app.amharic') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                <input type="text" name="book_title_en" value="{{ old('book_title_en', $daily->book_title_en ?? '') }}" placeholder="{{ __('app.english') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.url_label') }}</label>
                <input type="url" name="book_url" value="{{ old('book_url', $daily->book_url ?? '') }}" placeholder="{{ __('app.url_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-secondary">{{ __('app.description_label') }}</label>
                <textarea name="book_description_am" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.amharic') }}">{{ old('book_description_am', $daily->book_description_am ?? '') }}</textarea>
                <textarea name="book_description_en" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.english') }}">{{ old('book_description_en', $daily->book_description_en ?? '') }}</textarea>
            </div>
        </fieldset>

        {{-- References (know more about week/day) — add as many as needed --}}
        <fieldset class="border border-border rounded-lg p-4 space-y-3"
                 x-data="{
                     refs: {{ json_encode(old('references', isset($daily) && $daily->exists ? $daily->references->map(fn($r) => ['name_en' => $r->name_en ?? '', 'name_am' => $r->name_am ?? '', 'url' => $r->url])->values()->toArray() : [['name_en' => '', 'name_am' => '', 'url' => '']])) }},
                     addRef() { this.refs.push({ name_en: '', name_am: '', url: '' }); },
                     removeRef(i) { this.refs.splice(i, 1); }
                 }">
            <legend class="text-sm font-semibold text-accent px-2">{{ __('app.references_legend') }}</legend>
            <p class="text-xs text-muted-text">{{ __('app.references_help') }}</p>
            <template x-for="(ref, i) in refs" :key="i">
                <div class="flex gap-2 items-start p-2 rounded-lg bg-muted/50">
                    <div class="flex-1 space-y-2">
                        <input :name="'references[' + i + '][name_am]'" x-model="ref.name_am" type="text" placeholder="{{ __('app.name_amharic_label') }}"
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                        <input :name="'references[' + i + '][name_en]'" x-model="ref.name_en" type="text" placeholder="{{ __('app.name_english_label') }}"
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                        <input :name="'references[' + i + '][url]'" x-model="ref.url" type="url" placeholder="{{ __('app.url_placeholder') }}"
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none text-sm">
                    </div>
                    <button type="button" @click="removeRef(i)" class="p-2 text-muted-text hover:text-error transition shrink-0" title="{{ __('app.remove') }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
            <button type="button" @click="addRef()"
                    class="w-full py-2 border border-dashed border-border rounded-lg text-sm text-muted-text hover:text-accent hover:border-accent transition">
                + {{ __('app.add_reference') }}
            </button>
        </fieldset>

        {{-- Reflection --}}
        <div class="space-y-2">
            <label class="block text-sm font-medium text-secondary">{{ __('app.reflection_label') }}</label>
            <textarea name="reflection_am" rows="3" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.amharic_default') }}">{{ old('reflection_am', $daily->reflection_am ?? '') }}</textarea>
            <textarea name="reflection_en" rows="3" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none" placeholder="{{ __('app.english_fallback') }}">{{ old('reflection_en', $daily->reflection_en ?? '') }}</textarea>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_published" value="1" {{ old('is_published', $daily->is_published ?? false) ? 'checked' : '' }}
                   class="rounded border-border text-accent focus:ring-accent">
            <span class="text-secondary">{{ __('app.publish_label') }}</span>
        </label>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            <a href="{{ route('admin.daily.index') }}" class="px-6 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

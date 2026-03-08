@extends('layouts.admin')
@section('title', isset($theme) ? __('app.edit_theme') : __('app.create_theme'))

@section('content')
<div class="max-w-3xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ isset($theme) ? __('app.edit_theme') : __('app.create_theme') }}</h1>

    <form method="POST" action="{{ isset($theme) ? route('admin.themes.update', $theme) : route('admin.themes.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if(isset($theme)) @method('PUT') @endif

        <input type="hidden" name="lent_season_id" value="{{ $season?->id }}">

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 1: Week Info                                      --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.week_info') }}</h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_number_1_8') }}</label>
                    <input type="number" name="week_number" min="1" max="8"
                           value="{{ old('week_number', $theme->week_number ?? '') }}" required
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_start_date') }}</label>
                    <input type="date" name="week_start_date"
                           value="{{ old('week_start_date', isset($theme) ? $theme->week_start_date->format('Y-m-d') : '') }}" required
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_end_date') }}</label>
                    <input type="date" name="week_end_date"
                           value="{{ old('week_end_date', isset($theme) ? $theme->week_end_date->format('Y-m-d') : '') }}" required
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_english') }}</label>
                    <input type="text" name="name_en"
                           value="{{ old('name_en', $theme->name_en ?? '') }}" required
                           placeholder="{{ __('app.theme_name_en_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_amharic') }}</label>
                    <input type="text" name="name_am"
                           value="{{ old('name_am', $theme->name_am ?? '') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_geez') }}</label>
                    <input type="text" name="name_geez"
                           value="{{ old('name_geez', $theme->name_geez ?? '') }}"
                           placeholder="{{ __('app.theme_name_geez_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.meaning') }} (EN)</label>
                    <input type="text" name="meaning"
                           value="{{ old('meaning', $theme->meaning ?? '') }}" required
                           placeholder="{{ __('app.meaning_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.meaning') }} (AM)</label>
                    <input type="text" name="meaning_am"
                           value="{{ old('meaning_am', $theme->meaning_am ?? '') }}"
                           placeholder="Amharic meaning"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        @if(isset($theme))
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.theme_import_from_lectionary') }}</h2>
                <p class="text-sm text-muted-text">{{ __('app.theme_import_from_lectionary_help') }}</p>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.ethiopian_month') }}</label>
                        <input type="number" name="month" min="1" max="13" form="theme-lectionary-import"
                               value="{{ old('month', $importDefaults['month'] ?? '') }}"
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.day_label') }}</label>
                        <input type="number" name="day" min="1" max="30" form="theme-lectionary-import"
                               value="{{ old('day', $importDefaults['day'] ?? '') }}"
                               class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" form="theme-lectionary-import"
                                class="w-full px-4 py-2.5 bg-accent-secondary text-on-accent rounded-lg font-medium hover:bg-accent-secondary/90 transition">
                            {{ __('app.theme_import_action') }}
                        </button>
                    </div>
                </div>

                @if(!empty($importDefaults['month_name_en'] ?? null))
                    <p class="text-xs text-muted-text">
                        {{ __('app.theme_import_from_lectionary_default_hint', ['month' => $importDefaults['month_name_en'], 'day' => $importDefaults['day'] ?? '-']) }}
                    </p>
                @endif
            </div>
        </div>
        @endif

        {{-- SECTION 2: Feature Picture                                --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.feature_picture') }}</h2>

            @if(isset($theme) && $theme->feature_picture)
                <div class="flex items-end gap-4">
                    <img src="{{ Storage::disk('public')->url($theme->feature_picture) }}"
                         alt="Feature picture"
                         class="h-28 w-auto rounded-xl object-cover border border-border">
                    <label class="inline-flex items-center gap-2 text-sm text-muted-text cursor-pointer">
                        <input type="checkbox" name="remove_feature_picture" value="1"
                               class="rounded border-border text-accent focus:ring-accent">
                        {{ __('app.remove') }}
                    </label>
                </div>
            @endif

            <div>
                <input type="file" name="feature_picture" accept="image/*"
                       class="block w-full text-sm text-secondary
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-medium
                              file:bg-accent/10 file:text-accent
                              hover:file:bg-accent/20 cursor-pointer">
                <p class="text-xs text-muted-text mt-1">{{ __('app.image_max_2mb') }}</p>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTIONS 3-5: Bible Readings 1, 2, 3                     --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        @foreach([1, 2, 3] as $n)
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">
                {{ __('app.bible_reading') }} {{ $n }}
            </h2>

            {{-- Reference: EN and AM side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.reading_reference') }} (EN)</label>
                    <input type="text" name="reading_{{ $n }}_reference"
                           value="{{ old('reading_'.$n.'_reference', $theme->{'reading_'.$n.'_reference'} ?? '') }}"
                           placeholder="e.g. Hebrews 9:11-28"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.reading_reference') }} (AM)</label>
                    <input type="text" name="reading_{{ $n }}_reference_am"
                           value="{{ old('reading_'.$n.'_reference_am', $theme->{'reading_'.$n.'_reference_am'} ?? '') }}"
                           placeholder="ለምሳሌ፡ ዕብራውያን 9:11-28"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            {{-- Full text: EN and AM side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.reading_text_en') }}</label>
                    <textarea name="reading_{{ $n }}_text_en" rows="6"
                              placeholder="Full reading text in English..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('reading_'.$n.'_text_en', $theme->{'reading_'.$n.'_text_en'} ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.reading_text_am') }}</label>
                    <textarea name="reading_{{ $n }}_text_am" rows="6"
                              placeholder="ሙሉ ምንባብ በአማርኛ..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('reading_'.$n.'_text_am', $theme->{'reading_'.$n.'_text_am'} ?? '') }}</textarea>
                </div>
            </div>
        </div>
        @endforeach

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 6: Psalm                                          --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.psalm') }}</h2>

            {{-- Reference: EN and AM side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.psalm_reference') }} (EN)</label>
                    <input type="text" name="psalm_reference"
                           value="{{ old('psalm_reference', $theme->psalm_reference ?? '') }}"
                           placeholder="{{ __('app.psalm_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.psalm_reference') }} (AM)</label>
                    <input type="text" name="psalm_reference_am"
                           value="{{ old('psalm_reference_am', $theme->psalm_reference_am ?? '') }}"
                           placeholder="ለምሳሌ፡ 69:9-10"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            {{-- Full text: EN and AM side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.psalm_text') }} (EN)</label>
                    <textarea name="psalm_text_en" rows="6"
                              placeholder="Psalm text in English..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('psalm_text_en', $theme->psalm_text_en ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.psalm_text') }} (AM)</label>
                    <textarea name="psalm_text_am" rows="6"
                              placeholder="የዳዊት መዝሙር ጽሑፍ..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('psalm_text_am', $theme->psalm_text_am ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 7: Gospel                                         --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.gospel') }}</h2>

            {{-- Reference: EN and AM side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.gospel_reference') }} (EN)</label>
                    <input type="text" name="gospel_reference"
                           value="{{ old('gospel_reference', $theme->gospel_reference ?? '') }}"
                           placeholder="{{ __('app.reference_placeholder_short') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.gospel_reference') }} (AM)</label>
                    <input type="text" name="gospel_reference_am"
                           value="{{ old('gospel_reference_am', $theme->gospel_reference_am ?? '') }}"
                           placeholder="ለምሳሌ፡ ዮሐ 3:16"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            {{-- Full text: EN and AM side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.gospel_text') }} (EN)</label>
                    <textarea name="gospel_text_en" rows="6"
                              placeholder="Gospel text in English..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('gospel_text_en', $theme->gospel_text_en ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.gospel_text') }} (AM)</label>
                    <textarea name="gospel_text_am" rows="6"
                              placeholder="የወንጌል ጽሑፍ..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('gospel_text_am', $theme->gospel_text_am ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 7b: Epistles                                      --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.epistles') }}</h2>

            {{-- Reference: EN and AM side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.epistles_reference') }} (EN)</label>
                    <input type="text" name="epistles_reference"
                           value="{{ old('epistles_reference', $theme->epistles_reference ?? '') }}"
                           placeholder="{{ __('app.epistles_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.epistles_reference') }} (AM)</label>
                    <input type="text" name="epistles_reference_am"
                           value="{{ old('epistles_reference_am', $theme->epistles_reference_am ?? '') }}"
                           placeholder="ለምሳሌ፡ ሮሜ 8:1-4"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            {{-- Full text: EN and AM side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.epistles_text') }} (EN)</label>
                    <textarea name="epistles_text_en" rows="6"
                              placeholder="Epistles text in English..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('epistles_text_en', $theme->epistles_text_en ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.epistles_text') }} (AM)</label>
                    <textarea name="epistles_text_am" rows="6"
                              placeholder="የመልእክት ጽሑፍ..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('epistles_text_am', $theme->epistles_text_am ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 8: Liturgy / Anaphora                            --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.liturgy_anaphora') }}</h2>

            {{-- Anaphora name: EN and AM side by side --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.liturgy_anaphora') }} (EN)</label>
                    <input type="text" name="liturgy"
                           value="{{ old('liturgy', $theme->liturgy ?? '') }}"
                           placeholder="{{ __('app.liturgy_placeholder') }}"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.liturgy_anaphora') }} (AM)</label>
                    <input type="text" name="liturgy_am"
                           value="{{ old('liturgy_am', $theme->liturgy_am ?? '') }}"
                           placeholder="ለምሳሌ፡ የጌታችን አናፌራ"
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>

            {{-- Full text: EN and AM side by side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.liturgy_text') }} (EN)</label>
                    <textarea name="liturgy_text_en" rows="6"
                              placeholder="Liturgy / Anaphora text in English..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('liturgy_text_en', $theme->liturgy_text_en ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.liturgy_text') }} (AM)</label>
                    <textarea name="liturgy_text_am" rows="6"
                              placeholder="የቅዳሴ ጽሑፍ..."
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('liturgy_text_am', $theme->liturgy_text_am ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- SECTION 9: Description & Summary                         --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.description_and_summary') }}</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.description_label') }} (EN)</label>
                    <textarea name="description" rows="4"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('description', $theme->description ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.description_label') }} (AM)</label>
                    <textarea name="description_am" rows="4"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('description_am', $theme->description_am ?? '') }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.theme_summary') }} (EN)</label>
                    <textarea name="theme_summary" rows="3"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('theme_summary', $theme->theme_summary ?? '') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.theme_summary') }} (AM)</label>
                    <textarea name="summary_am" rows="3"
                              class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none resize-y">{{ old('summary_am', $theme->summary_am ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ───────────────────────────────────────────────────────── --}}
        {{-- Submit / Cancel                                           --}}
        {{-- ───────────────────────────────────────────────────────── --}}
        <div class="flex gap-3 pb-4">
            <button type="submit"
                    class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">
                {{ __('app.save') }}
            </button>
            <a href="{{ route('admin.themes.index') }}"
               class="px-6 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">
                {{ __('app.cancel') }}
            </a>
        </div>
    </form>

    @if(isset($theme))
    <form id="theme-lectionary-import" method="POST" action="{{ route('admin.themes.import-lectionary', $theme) }}">
        @csrf
    </form>
    @endif
</div>
@endsection

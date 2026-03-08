@extends('layouts.admin')

@section('title', isset($theme) ? __('app.edit_theme') : __('app.create_theme'))

@php
    $isEdit = isset($theme);
    $inputClass = 'w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition';
    $textAreaClass = $inputClass . ' resize-y min-h-[14rem] lg:min-h-[6rem]';
    $labelClass = 'block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1';

    $reading1Filled = $isEdit && (filled($theme->reading_1_reference) || filled($theme->reading_1_reference_am) || filled($theme->reading_1_text_en) || filled($theme->reading_1_text_am));
    $reading2Filled = $isEdit && (filled($theme->reading_2_reference) || filled($theme->reading_2_reference_am) || filled($theme->reading_2_text_en) || filled($theme->reading_2_text_am));
    $reading3Filled = $isEdit && (filled($theme->reading_3_reference) || filled($theme->reading_3_reference_am) || filled($theme->reading_3_text_en) || filled($theme->reading_3_text_am));
    $psalmFilled = $isEdit && (filled($theme->psalm_reference) || filled($theme->psalm_reference_am) || filled($theme->psalm_text_en) || filled($theme->psalm_text_am));
    $gospelFilled = $isEdit && (filled($theme->gospel_reference) || filled($theme->gospel_reference_am) || filled($theme->gospel_text_en) || filled($theme->gospel_text_am));
    $epistlesFilled = $isEdit && (filled($theme->epistles_reference) || filled($theme->epistles_reference_am) || filled($theme->epistles_text_en) || filled($theme->epistles_text_am));
    $liturgyFilled = $isEdit && (filled($theme->liturgy) || filled($theme->liturgy_am) || filled($theme->liturgy_text_en) || filled($theme->liturgy_text_am));

    $readingSections = [
        ['number' => 1, 'filled' => $reading1Filled, 'ref_en' => 'reading_1_reference', 'ref_am' => 'reading_1_reference_am', 'text_en' => 'reading_1_text_en', 'text_am' => 'reading_1_text_am'],
        ['number' => 2, 'filled' => $reading2Filled, 'ref_en' => 'reading_2_reference', 'ref_am' => 'reading_2_reference_am', 'text_en' => 'reading_2_text_en', 'text_am' => 'reading_2_text_am'],
        ['number' => 3, 'filled' => $reading3Filled, 'ref_en' => 'reading_3_reference', 'ref_am' => 'reading_3_reference_am', 'text_en' => 'reading_3_text_en', 'text_am' => 'reading_3_text_am'],
    ];
@endphp

@section('content')
<style>[x-cloak]{display:none!important}</style>

<div class="max-w-4xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ $isEdit ? __('app.edit_theme') : __('app.create_theme') }}</h1>

    <form method="POST"
          action="{{ $isEdit ? route('admin.themes.update', $theme) : route('admin.themes.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @if($isEdit) @method('PUT') @endif

        <input type="hidden" name="lent_season_id" value="{{ $season?->id }}">

        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 space-y-4">
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
                           value="{{ old('week_start_date', $isEdit ? $theme->week_start_date?->format('Y-m-d') : '') }}" required
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_end_date') }}</label>
                    <input type="date" name="week_end_date"
                           value="{{ old('week_end_date', $isEdit ? $theme->week_end_date?->format('Y-m-d') : '') }}" required
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
                           class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
                </div>
            </div>
        </div>

        @if($isEdit)
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 space-y-4">
            <div class="flex flex-col gap-1">
                <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.theme_import_from_lectionary') }}</h2>
                <p class="text-sm text-muted-text">{{ __('app.theme_import_from_lectionary_help') }}</p>
            </div>

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
        @endif

        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 space-y-4">
            <h2 class="text-sm font-bold text-primary uppercase tracking-wide">{{ __('app.feature_picture') }}</h2>

            @if($isEdit && $theme->feature_picture)
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
                       class="block w-full text-sm text-secondary file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-accent/10 file:text-accent hover:file:bg-accent/20 cursor-pointer">
                <p class="text-xs text-muted-text mt-1">{{ __('app.image_max_2mb') }}</p>
            </div>
        </div>

        @foreach($readingSections as $section)
        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: window.innerWidth >= 1024 || {{ $section['filled'] ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $section['filled'] ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($section['filled'])
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">{{ $section['number'] }}</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.bible_reading') }} {{ $section['number'] }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.reading_reference') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3 lg:px-3 lg:pb-3 lg:pt-2 lg:space-y-2">
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.reading_reference') }} (EN)</label>
                        <input type="text" name="{{ $section['ref_en'] }}"
                               value="{{ old($section['ref_en'], $theme->{$section['ref_en']} ?? '') }}"
                               placeholder="e.g. Hebrews 9:11-28"
                               class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.reading_reference') }} (AM)</label>
                        <input type="text" name="{{ $section['ref_am'] }}"
                               value="{{ old($section['ref_am'], $theme->{$section['ref_am']} ?? '') }}"
                               placeholder="ለምሳሌ፡ ዕብራውያን 9:11-28"
                               class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.reading_text_am') }}</label>
                        <textarea name="{{ $section['text_am'] }}" rows="6"
                                  placeholder="ሙሉ ምንባብ በአማርኛ..."
                                  class="{{ $textAreaClass }}">{{ old($section['text_am'], $theme->{$section['text_am']} ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.reading_text_en') }}</label>
                        <textarea name="{{ $section['text_en'] }}" rows="6"
                                  placeholder="Full reading text in English..."
                                  class="{{ $textAreaClass }}">{{ old($section['text_en'], $theme->{$section['text_en']} ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: window.innerWidth >= 1024 || {{ $psalmFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $psalmFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($psalmFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">4</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_mesbak') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_mesbak_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3 lg:px-3 lg:pb-3 lg:pt-2 lg:space-y-2">
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.psalm_reference') }} (EN)</label>
                        <input type="text" name="psalm_reference"
                               value="{{ old('psalm_reference', $theme->psalm_reference ?? '') }}"
                               placeholder="{{ __('app.psalm_placeholder') }}"
                               class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.psalm_reference') }} (AM)</label>
                        <input type="text" name="psalm_reference_am"
                               value="{{ old('psalm_reference_am', $theme->psalm_reference_am ?? '') }}"
                               placeholder="ለምሳሌ፡ 69:9-10"
                               class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.psalm_text') }} (AM)</label>
                        <textarea name="psalm_text_am" rows="6"
                                  placeholder="የዳዊት መዝሙር ጽሑፍ..."
                                  class="{{ $textAreaClass }}">{{ old('psalm_text_am', $theme->psalm_text_am ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.psalm_text') }} (EN)</label>
                        <textarea name="psalm_text_en" rows="6"
                                  placeholder="Psalm text in English..."
                                  class="{{ $textAreaClass }}">{{ old('psalm_text_en', $theme->psalm_text_en ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: window.innerWidth >= 1024 || {{ $gospelFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $gospelFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($gospelFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">5</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_gospel') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_gospel_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3 lg:px-3 lg:pb-3 lg:pt-2 lg:space-y-2">
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.gospel_reference') }} (EN)</label>
                        <input type="text" name="gospel_reference"
                               value="{{ old('gospel_reference', $theme->gospel_reference ?? '') }}"
                               placeholder="{{ __('app.reference_placeholder_short') }}"
                               class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.gospel_reference') }} (AM)</label>
                        <input type="text" name="gospel_reference_am"
                               value="{{ old('gospel_reference_am', $theme->gospel_reference_am ?? '') }}"
                               placeholder="ለምሳሌ፡ ዮሐ 3:16"
                               class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.gospel_text') }} (AM)</label>
                        <textarea name="gospel_text_am" rows="6"
                                  placeholder="የወንጌል ጽሑፍ..."
                                  class="{{ $textAreaClass }}">{{ old('gospel_text_am', $theme->gospel_text_am ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.gospel_text') }} (EN)</label>
                        <textarea name="gospel_text_en" rows="6"
                                  placeholder="Gospel text in English..."
                                  class="{{ $textAreaClass }}">{{ old('gospel_text_en', $theme->gospel_text_en ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: window.innerWidth >= 1024 || {{ $epistlesFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $epistlesFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($epistlesFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">6</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.epistles') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.epistles_reference') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3 lg:px-3 lg:pb-3 lg:pt-2 lg:space-y-2">
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.epistles_reference') }} (EN)</label>
                        <input type="text" name="epistles_reference"
                               value="{{ old('epistles_reference', $theme->epistles_reference ?? '') }}"
                               placeholder="{{ __('app.epistles_placeholder') }}"
                               class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.epistles_reference') }} (AM)</label>
                        <input type="text" name="epistles_reference_am"
                               value="{{ old('epistles_reference_am', $theme->epistles_reference_am ?? '') }}"
                               placeholder="ለምሳሌ፡ ሮሜ 8:1-4"
                               class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.epistles_text') }} (AM)</label>
                        <textarea name="epistles_text_am" rows="6"
                                  placeholder="የመልእክት ጽሑፍ..."
                                  class="{{ $textAreaClass }}">{{ old('epistles_text_am', $theme->epistles_text_am ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.epistles_text') }} (EN)</label>
                        <textarea name="epistles_text_en" rows="6"
                                  placeholder="Epistles text in English..."
                                  class="{{ $textAreaClass }}">{{ old('epistles_text_en', $theme->epistles_text_en ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: window.innerWidth >= 1024 || {{ $liturgyFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $liturgyFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($liturgyFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">7</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_qiddase') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_qiddase_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3 lg:px-3 lg:pb-3 lg:pt-2 lg:space-y-2">
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_qiddase_en') }}</label>
                        <input type="text" name="liturgy"
                               value="{{ old('liturgy', $theme->liturgy ?? '') }}"
                               placeholder="{{ __('app.liturgy_placeholder') }}"
                               class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_qiddase_am') }}</label>
                        <input type="text" name="liturgy_am"
                               value="{{ old('liturgy_am', $theme->liturgy_am ?? '') }}"
                               placeholder="ለምሳሌ፡ የጌታችን አናፌራ"
                               class="{{ $inputClass }}">
                    </div>
                </div>
                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-3 lg:space-y-0">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.liturgy_text') }} (AM)</label>
                        <textarea name="liturgy_text_am" rows="6"
                                  placeholder="የቅዳሴ ጽሑፍ..."
                                  class="{{ $textAreaClass }}">{{ old('liturgy_text_am', $theme->liturgy_text_am ?? '') }}</textarea>
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.liturgy_text') }} (EN)</label>
                        <textarea name="liturgy_text_en" rows="6"
                                  placeholder="Liturgy / Anaphora text in English..."
                                  class="{{ $textAreaClass }}">{{ old('liturgy_text_en', $theme->liturgy_text_en ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 space-y-4">
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

    @if($isEdit)
    <form id="theme-lectionary-import" method="POST" action="{{ route('admin.themes.import-lectionary', $theme) }}">
        @csrf
    </form>
    @endif
</div>
@endsection

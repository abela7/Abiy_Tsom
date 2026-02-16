@extends('layouts.admin')
@section('title', isset($theme) ? __('app.edit_theme') : __('app.create_theme'))

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ isset($theme) ? __('app.edit_theme') : __('app.create_theme') }}</h1>

    <form method="POST" action="{{ isset($theme) ? route('admin.themes.update', $theme) : route('admin.themes.store') }}"
          class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
        @csrf
        @if(isset($theme)) @method('PUT') @endif

        <input type="hidden" name="lent_season_id" value="{{ $season?->id }}">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_number_1_8') }}</label>
                <input type="number" name="week_number" min="1" max="8" value="{{ old('week_number', $theme->week_number ?? '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_english') }}</label>
                <input type="text" name="name_en" value="{{ old('name_en', $theme->name_en ?? '') }}" required placeholder="{{ __('app.theme_name_en_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_geez') }}</label>
                <input type="text" name="name_geez" value="{{ old('name_geez', $theme->name_geez ?? '') }}" placeholder="{{ __('app.theme_name_geez_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.name_amharic') }}</label>
                <input type="text" name="name_am" value="{{ old('name_am', $theme->name_am ?? '') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.gospel_reference') }}</label>
                <input type="text" name="gospel_reference" value="{{ old('gospel_reference', $theme->gospel_reference ?? '') }}" placeholder="{{ __('app.reference_placeholder_short') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.epistles_reference') }}</label>
                <input type="text" name="epistles_reference" value="{{ old('epistles_reference', $theme->epistles_reference ?? '') }}" placeholder="{{ __('app.epistles_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.psalm_reference') }}</label>
                <input type="text" name="psalm_reference" value="{{ old('psalm_reference', $theme->psalm_reference ?? '') }}" placeholder="{{ __('app.psalm_placeholder') }}"
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <div>
<label class="block text-sm font-medium text-secondary mb-1">{{ __('app.liturgy_anaphora') }}</label>
                <input type="text" name="liturgy" value="{{ old('liturgy', $theme->liturgy ?? '') }}" placeholder="{{ __('app.liturgy_placeholder') }}"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.meaning') }} (EN)</label>
            <input type="text" name="meaning" value="{{ old('meaning', $theme->meaning ?? '') }}" required placeholder="{{ __('app.meaning_placeholder') }}"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.meaning') }} (AM)</label>
            <input type="text" name="meaning_am" value="{{ old('meaning_am', $theme->meaning_am ?? '') }}" placeholder="Amharic meaning"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.description_label') }} (EN)</label>
            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">{{ old('description', $theme->description ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.description_label') }} (AM)</label>
            <textarea name="description_am" rows="3" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">{{ old('description_am', $theme->description_am ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.theme_summary') }} (EN)</label>
            <textarea name="theme_summary" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">{{ old('theme_summary', $theme->theme_summary ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.theme_summary') }} (AM)</label>
            <textarea name="summary_am" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">{{ old('summary_am', $theme->summary_am ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_start_date') }}</label>
                <input type="date" name="week_start_date" value="{{ old('week_start_date', isset($theme) ? $theme->week_start_date->format('Y-m-d') : '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.week_end_date') }}</label>
                <input type="date" name="week_end_date" value="{{ old('week_end_date', isset($theme) ? $theme->week_end_date->format('Y-m-d') : '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            <a href="{{ route('admin.themes.index') }}" class="px-6 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

@extends('layouts.admin')
@section('title', isset($season) ? __('app.edit_season') : __('app.create_season'))

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ isset($season) ? __('app.edit_season') : __('app.create_season') }}</h1>

    <form method="POST" action="{{ isset($season) ? route('admin.seasons.update', $season) : route('admin.seasons.store') }}"
          class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
        @csrf
        @if(isset($season)) @method('PUT') @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.year') }}</label>
                <input type="number" name="year" value="{{ old('year', $season->year ?? date('Y')) }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.total_days') }}</label>
                <input type="number" name="total_days" value="{{ old('total_days', $season->total_days ?? 55) }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.start_date') }}</label>
                <input type="date" name="start_date" value="{{ old('start_date', isset($season) ? $season->start_date->format('Y-m-d') : '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.end_date_easter') }}</label>
                <input type="date" name="end_date" value="{{ old('end_date', isset($season) ? $season->end_date->format('Y-m-d') : '') }}" required
                       class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $season->is_active ?? false) ? 'checked' : '' }}
                   class="rounded border-border text-accent focus:ring-accent">
            <span class="text-secondary">{{ __('app.set_as_active_season') }}</span>
        </label>
        @if(isset($season))
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="regenerate_weeks" value="1" {{ old('regenerate_weeks') ? 'checked' : '' }}
                   class="rounded border-border text-accent focus:ring-accent">
            <span class="text-secondary">{{ __('app.regenerate_8_weeks') }}</span>
        </label>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            <a href="{{ route('admin.seasons.index') }}" class="px-6 py-2.5 bg-muted text-secondary rounded-lg font-medium hover:bg-border transition">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

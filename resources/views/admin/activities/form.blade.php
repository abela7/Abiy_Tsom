@extends('layouts.admin')
@section('title', isset($activity) ? __('app.edit_activity') : __('app.create_activity'))

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-primary mb-6">{{ isset($activity) ? __('app.edit_activity') : __('app.create_activity') }}</h1>

    <form method="POST" action="{{ isset($activity) ? route('admin.activities.update', $activity) : route('admin.activities.store') }}"
          class="bg-card rounded-xl shadow-sm border border-border p-6 space-y-4">
        @csrf
        @if(isset($activity)) @method('PUT') @endif

        <input type="hidden" name="lent_season_id" value="{{ $season?->id }}">

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.activity_name') }}</label>
            <input type="text" name="name" value="{{ old('name', $activity->name ?? '') }}" required placeholder="{{ __('app.activity_placeholder') }}"
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.description_optional') }}</label>
            <textarea name="description" rows="2" class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">{{ old('description', $activity->description ?? '') }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.sort_order') }}</label>
            <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $activity->sort_order ?? 0) }}" required
                   class="w-full px-3 py-2 border border-border rounded-lg focus:ring-2 focus:ring-accent outline-none">
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $activity->is_active ?? true) ? 'checked' : '' }}
                   class="rounded border-border text-accent focus:ring-accent">
            <span class="text-secondary">{{ __('app.active') }}</span>
        </label>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-accent text-on-accent rounded-lg font-medium hover:bg-accent-hover transition">{{ __('app.save') }}</button>
            <a href="{{ route('admin.activities.index') }}" class="px-6 py-2.5 bg-gray-100 text-secondary rounded-lg font-medium hover:bg-border transition">{{ __('app.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

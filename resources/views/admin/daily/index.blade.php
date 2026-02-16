@extends('layouts.admin')
@section('title', __('app.daily_content'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.daily_content') }}</h1>
    @if($season)
        <div class="flex gap-2">
            <form method="POST" action="{{ route('admin.daily.scaffold') }}" class="inline" onsubmit="return confirm('{{ __('app.scaffold_confirm') }}');">
                @csrf
                <button type="submit" class="px-4 py-2 bg-muted text-secondary rounded-lg text-sm font-medium hover:bg-border transition">{{ __('app.scaffold_55_days') }}</button>
            </form>
            <a href="{{ route('admin.daily.create') }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">+ {{ __('app.create') }}</a>
        </div>
    @endif
</div>

@if(!$season)
    <p class="text-muted-text">{{ __('app.no_active_season') }} <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a></p>
@else
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.day_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.week_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.title') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.bible') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.writer') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($contents as $content)
                    <tr class="hover:bg-muted">
                        <td class="px-4 py-3 font-bold text-accent">{{ $content->day_number }}</td>
                        <td class="px-4 py-3 text-secondary">{{ $content->date->format('M d') }}</td>
                        <td class="px-4 py-3 text-secondary">{{ optional($content->weeklyTheme)->name_en ?: '-' }}</td>
                        <td class="px-4 py-3">{{ localized($content, 'day_title') ?? '-' }}</td>
                        <td class="px-4 py-3 text-secondary">{{ localized($content, 'bible_reference') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $content->is_published ? 'bg-success-bg text-success' : 'bg-reflection-bg text-accent-secondary' }}">
                                {{ $content->is_published ? __('app.published') : __('app.draft') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-secondary">
                            <div>{{ __('app.created_by') }}: {{ optional($content->createdBy)->name ?: '-' }}</div>
                            <div class="text-muted-text">{{ __('app.updated_by') }}: {{ optional($content->updatedBy)->name ?: '-' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.daily.edit', $content) }}" class="text-accent hover:underline">{{ __('app.edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-8 text-center text-muted-text">{{ __('app.no_daily_content_yet') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection

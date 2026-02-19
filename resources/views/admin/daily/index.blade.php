@extends('layouts.admin')
@section('title', __('app.daily_content'))

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-primary">{{ __('app.daily_content') }}</h1>
    @if($season)
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.day-assignments.index') }}"
               class="px-3.5 py-2 bg-muted text-secondary rounded-lg text-sm font-medium hover:bg-border transition whitespace-nowrap">
                {{ __('app.day_assignments') }}
            </a>
            <a href="{{ route('admin.daily.create') }}"
               class="px-3.5 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition whitespace-nowrap">
                + {{ __('app.create') }}
            </a>
        </div>
    @endif
</div>

@if(!$season)
    <p class="text-muted-text">
        {{ __('app.no_active_season') }}
        <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a>
    </p>
@else
    {{-- ═══ Desktop table (hidden on mobile) ═══ --}}
    <div class="hidden md:block bg-card rounded-xl shadow-sm border border-border overflow-x-auto">
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
                    <tr class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3 font-bold text-accent">{{ $content->day_number }}</td>
                        <td class="px-4 py-3 text-secondary">{{ $content->date->format('M d') }}</td>
                        <td class="px-4 py-3 text-secondary">{{ $content->weeklyTheme ? (localized($content->weeklyTheme, 'name') ?? '-') : '-' }}</td>
                        <td class="px-4 py-3">{{ localized($content, 'day_title') ?? '-' }}</td>
                        <td class="px-4 py-3 text-secondary">{{ localized($content, 'bible_reference') ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $content->is_published ? 'bg-success-bg text-success' : 'bg-reflection-bg text-accent-secondary' }}">
                                {{ $content->is_published ? __('app.published') : __('app.draft') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-secondary">
                            <div>{{ __('app.assigned_writer') }}: {{ optional($content->assignedTo)->name ?: '-' }}</div>
                            <div class="text-muted-text">{{ __('app.created_by') }}: {{ optional($content->createdBy)->name ?: '-' }}</div>
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

    {{-- ═══ Mobile card list (hidden on desktop) ═══ --}}
    <div class="md:hidden space-y-3">
        @forelse($contents as $content)
            <a href="{{ route('admin.daily.edit', $content) }}"
               class="block bg-card rounded-xl border border-border shadow-sm hover:shadow-md hover:border-accent/30 transition-all active:scale-[0.99]">
                <div class="p-4 space-y-3">
                    {{-- Top row: day number + status --}}
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-accent/10 text-accent font-bold text-base">
                                {{ $content->day_number }}
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-primary leading-tight">
                                    {{ localized($content, 'day_title') ?: __('app.day_label') . ' ' . $content->day_number }}
                                </p>
                                <p class="text-xs text-muted-text mt-0.5">{{ $content->date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold shrink-0 {{ $content->is_published ? 'bg-success-bg text-success' : 'bg-reflection-bg text-accent-secondary' }}">
                            {{ $content->is_published ? __('app.published') : __('app.draft') }}
                        </span>
                    </div>

                    {{-- Details grid --}}
                    <div class="grid grid-cols-1 gap-x-4 gap-y-1.5 text-xs">
                        <div>
                            <span class="text-muted-text">{{ __('app.week_label') }}:</span>
                            <span class="text-secondary font-medium ml-1">{{ $content->weeklyTheme ? (localized($content->weeklyTheme, 'name') ?? '-') : '-' }}</span>
                        </div>
                    </div>

                    {{-- Writer info --}}
                    @if(optional($content->assignedTo)->name)
                        <div class="flex items-center gap-1.5 text-xs text-muted-text pt-1 border-t border-border">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <span>{{ optional($content->assignedTo)->name }}</span>
                        </div>
                    @endif
                </div>
            </a>
        @empty
            <div class="bg-card rounded-xl border border-border p-8 text-center text-muted-text">
                {{ __('app.no_daily_content_yet') }}
            </div>
        @endforelse
    </div>
@endif
@endsection

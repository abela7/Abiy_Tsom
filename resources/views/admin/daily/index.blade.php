@extends('layouts.admin')
@section('title', __('app.daily_content'))

@section('content')
<div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.daily_content') }}</h1>
    @if($season)
        <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row sm:items-center">
            @if($canEdit ?? false)
                <a href="{{ route('admin.day-assignments.index') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border sm:w-auto">
                    {{ __('app.day_assignments') }}
                </a>
                <a href="{{ route('admin.daily.create') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-accent px-4 py-2.5 text-sm font-semibold text-on-accent transition hover:bg-accent-hover sm:w-auto">
                    {{ __('app.create') }}
                </a>
                <a href="{{ route('admin.daily-suggestions.index') }}"
                   class="inline-flex w-full items-center justify-center rounded-xl border border-accent-secondary bg-accent-secondary/10 px-4 py-2.5 text-sm font-semibold text-accent-secondary transition hover:bg-accent-secondary/20 sm:w-auto">
                    {{ __('app.daily_suggestions') }}
                </a>
            @endif
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
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                    <th class="text-center px-4 py-3 font-semibold text-secondary">{{ __('app.views') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.created_by') }}</th>
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
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $content->is_published ? 'bg-success-bg text-success' : 'bg-reflection-bg text-accent-secondary' }}">
                                {{ $content->is_published ? __('app.published') : __('app.draft') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-medium {{ ($content->views_count ?? 0) > 0 ? 'text-accent' : 'text-muted-text' }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ $content->views_count ?? 0 }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-secondary">
                            {{ __('app.created_by') }}: {{ optional($content->createdBy)->name ?: '-' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.daily.preview', $content) }}" target="_blank" rel="noopener" class="text-accent hover:underline">{{ __('app.view') }}</a>
                                <span class="text-muted-text">|</span>
                                <a href="{{ route('admin.daily.edit', $content) }}" class="text-accent hover:underline">
                                    {{ ($canEdit ?? false) ? __('app.edit') : __('app.suggest_update') }}
                                </a>
                                @if($canEdit ?? false)
                                    <span class="text-muted-text">|</span>
                                    <form action="{{ route('admin.daily.destroy', $content) }}" method="POST" class="inline"
                                          onsubmit="return confirm('{{ __('app.confirm_delete_daily') }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:underline">{{ __('app.delete') }}</button>
                                    </form>
                                @endif
                            </span>
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
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
            <a href="{{ route('admin.daily.edit', $content) }}"
               class="block hover:bg-muted/30 transition-colors active:scale-[0.99]">
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

                    {{-- Created by + views --}}
                    <div class="flex items-center justify-between text-xs text-muted-text pt-1 border-t border-border">
                        <span>{{ __('app.created_by') }}: {{ optional($content->createdBy)->name ?: '-' }}</span>
                        <span class="inline-flex items-center gap-1 {{ ($content->views_count ?? 0) > 0 ? 'text-accent' : '' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ $content->views_count ?? 0 }}
                        </span>
                    </div>
                </div>
            </a>
            <div class="flex border-t border-border">
                <a href="{{ route('admin.daily.preview', $content) }}" target="_blank" rel="noopener" class="flex-1 py-2.5 text-center text-sm font-medium text-accent hover:bg-muted/50 transition">{{ __('app.view') }}</a>
                <a href="{{ route('admin.daily.edit', $content) }}" class="flex-1 py-2.5 text-center text-sm font-medium text-accent hover:bg-muted/50 transition border-l border-border">
                    {{ ($canEdit ?? false) ? __('app.edit') : __('app.suggest_update') }}
                </a>
                @if($canEdit ?? false)
                    <form action="{{ route('admin.daily.destroy', $content) }}" method="POST" class="flex-1 border-l border-border"
                          onsubmit="return confirm('{{ __('app.confirm_delete_daily') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full py-2.5 text-center text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">{{ __('app.delete') }}</button>
                    </form>
                @endif
            </div>
            </div>
        @empty
            <div class="bg-card rounded-xl border border-border p-8 text-center text-muted-text">
                {{ __('app.no_daily_content_yet') }}
            </div>
        @endforelse
    </div>
@endif
@endsection

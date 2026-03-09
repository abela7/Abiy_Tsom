@extends('layouts.admin')
@section('title', __('app.announcement_suggestions'))

@section('content')
<div class="flex flex-col gap-3 mb-5 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-2xl sm:text-3xl font-bold text-primary leading-tight">{{ __('app.announcement_suggestions') }}</h1>
    <a href="{{ route('admin.announcements.index') }}"
       class="inline-flex w-full items-center justify-center rounded-xl border border-border bg-muted px-4 py-2.5 text-sm font-semibold text-secondary transition hover:bg-border sm:w-auto">
        {{ __('app.announcements') }}
    </a>
</div>

@if($suggestions->isEmpty())
    <div class="bg-card rounded-2xl border border-border p-10 text-center space-y-3">
        <div class="mx-auto w-14 h-14 rounded-full bg-muted flex items-center justify-center">
            <svg class="w-7 h-7 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
        </div>
        <p class="text-sm text-muted-text">{{ __('app.announcement_suggestions_empty') }}</p>
    </div>
@else
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.title_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.submitted_by') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @foreach($suggestions as $s)
                    <tr class="hover:bg-muted/50 transition-colors">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.announcements.edit', $s->announcement) }}" class="text-accent hover:underline font-medium">
                                {{ $s->announcement->titleForLocale() ?: '-' }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-secondary">{{ $s->submittedBy?->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-muted-text text-xs">{{ $s->created_at->format('M d, H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.announcement-suggestions.apply', $s) }}">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-accent text-on-accent hover:bg-accent-hover transition">
                                        {{ __('app.apply') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.announcement-suggestions.reject', $s) }}">
                                    @csrf
                                    <input type="hidden" name="rejected_reason" value="">
                                    <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium border border-border text-error hover:bg-error-bg transition">
                                        {{ __('app.reject') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $suggestions->links() }}
    </div>
@endif
@endsection

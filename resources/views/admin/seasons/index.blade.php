@extends('layouts.admin')
@section('title', __('app.seasons'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.seasons') }}</h1>
    <a href="{{ route('admin.seasons.create') }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">+ {{ __('app.create') }}</a>
</div>

<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-muted border-b border-border">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.year') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.start') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.end') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.total_days') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            @forelse($seasons as $s)
                <tr class="hover:bg-muted">
                    <td class="px-4 py-3 font-medium">{{ $s->year }}</td>
                    <td class="px-4 py-3">{{ $s->start_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3">{{ $s->end_date->format('M d, Y') }}</td>
                    <td class="px-4 py-3">{{ $s->total_days }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $s->is_active ? 'bg-success-bg text-success' : 'bg-muted text-muted-text' }}">
                            {{ $s->is_active ? __('app.active') : __('app.inactive') }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.seasons.edit', $s) }}" class="text-accent hover:underline text-sm">{{ __('app.edit') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-muted-text">{{ __('app.no_seasons_yet') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@extends('layouts.admin')
@section('title', __('app.activities'))

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-primary">{{ __('app.activities') }}</h1>
    @if($season)
        <a href="{{ route('admin.activities.create') }}" class="px-4 py-2 bg-accent text-on-accent rounded-lg text-sm font-medium hover:bg-accent-hover transition">+ {{ __('app.create') }}</a>
    @endif
</div>

@if(!$season)
    <p class="text-muted-text">{{ __('app.no_active_season') }} <a href="{{ route('admin.seasons.create') }}" class="text-accent hover:underline">{{ __('app.create_one_first') }}</a></p>
@else
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-muted border-b border-border">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.order') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.name') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.status') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($activities as $activity)
                    <tr class="hover:bg-muted">
                        <td class="px-4 py-3 text-secondary">{{ $activity->sort_order }}</td>
                        <td class="px-4 py-3 font-medium">{{ localized($activity, 'name') }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $activity->is_active ? 'bg-success-bg text-success' : 'bg-muted text-muted-text' }}">
                                {{ $activity->is_active ? __('app.active') : __('app.inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 flex gap-3">
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="text-accent hover:underline">{{ __('app.edit') }}</a>
                            <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" onsubmit="return confirm('{{ __('app.confirm_delete_activity') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-error hover:underline">{{ __('app.delete') }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-muted-text">{{ __('app.no_activities_yet') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endif
@endsection

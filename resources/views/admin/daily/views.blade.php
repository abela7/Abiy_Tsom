@extends('layouts.admin')

@section('title', __('app.view_details') . ' — ' . __('app.day_label') . ' ' . $daily->day_number)

@section('content')
{{-- Back link + header --}}
<div class="mb-6">
    <a href="{{ route('admin.daily.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-accent transition mb-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('app.back_to_daily') }}
    </a>
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-lg">
            {{ $daily->day_number }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ __('app.view_details') }} — {{ __('app.day_label') }} {{ $daily->day_number }}</h1>
            <p class="text-sm text-muted-text">{{ localized($daily, 'day_title') ?: $daily->date->format('M d, Y') }}</p>
        </div>
    </div>
</div>

{{-- Summary stat cards --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.total_views') }}</p>
        </div>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalViews) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-green-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.member_viewers') }}</p>
        </div>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($memberViews->count()) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.anonymous_viewers') }}</p>
        </div>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($anonymousViews->count()) }}</p>
    </div>
</div>

{{-- Members who viewed --}}
<div class="bg-card rounded-xl shadow-sm border border-border mb-6">
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <h2 class="text-sm font-bold text-primary">{{ __('app.member_viewers') }}</h2>
        <span class="text-xs text-muted-text">{{ $memberViews->count() }} {{ __('app.members') }}</span>
    </div>

    @if($memberViews->isEmpty())
        <div class="px-4 py-8 text-center text-muted-text text-sm">
            {{ __('app.no_member_views') }}
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">#</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.baptism_name') }}</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.viewed_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($memberViews as $i => $view)
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-4 py-2.5 text-muted-text">{{ $i + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">
                                        {{ mb_strtoupper(mb_substr($view->member?->baptism_name ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-primary">{{ $view->member?->baptism_name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-muted-text">{{ $view->viewed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-border">
            @foreach($memberViews as $i => $view)
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">
                            {{ mb_strtoupper(mb_substr($view->member?->baptism_name ?? '?', 0, 1)) }}
                        </span>
                        <span class="text-sm font-medium text-primary">{{ $view->member?->baptism_name ?? '—' }}</span>
                    </div>
                    <span class="text-xs text-muted-text">{{ $view->viewed_at?->diffForHumans() ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Anonymous viewers --}}
<div class="bg-card rounded-xl shadow-sm border border-border">
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <h2 class="text-sm font-bold text-primary">{{ __('app.anonymous_viewers') }}</h2>
        <span class="text-xs text-muted-text">{{ $anonymousViews->count() }} {{ __('app.visitors') }}</span>
    </div>

    @if($anonymousViews->isEmpty())
        <div class="px-4 py-8 text-center text-muted-text text-sm">
            {{ __('app.no_anonymous_views') }}
        </div>
    @else
        {{-- Desktop table --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted border-b border-border">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">#</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.ip_address') }}</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.viewed_at') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($anonymousViews as $i => $view)
                        <tr class="hover:bg-muted/50 transition-colors">
                            <td class="px-4 py-2.5 text-muted-text">{{ $i + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-full bg-muted text-muted-text text-xs font-bold flex items-center justify-center">?</span>
                                    <span class="font-mono text-xs text-secondary">{{ $view->ip_address ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-2.5 text-muted-text">{{ $view->viewed_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="md:hidden divide-y divide-border">
            @foreach($anonymousViews as $i => $view)
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <span class="w-8 h-8 rounded-full bg-muted text-muted-text text-xs font-bold flex items-center justify-center">?</span>
                        <span class="font-mono text-xs text-secondary">{{ $view->ip_address ?? '—' }}</span>
                    </div>
                    <span class="text-xs text-muted-text">{{ $view->viewed_at?->diffForHumans() ?? '—' }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@extends('layouts.admin')

@section('title', $user->name . ' — Referral Details')

@section('content')
{{-- Back link + header --}}
<div class="mb-6">
    <a href="{{ route('admin.referrals.index') }}" class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-accent transition mb-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('app.back_to_leaderboard') }}
    </a>
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-accent/15 flex items-center justify-center text-accent font-bold text-lg">
            {{ strtoupper(substr($user->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ $user->name }}</h1>
            <p class="text-sm text-muted-text capitalize">{{ $user->role }}</p>
        </div>
    </div>
</div>

{{-- Referral link card --}}
<div class="bg-card rounded-xl p-4 shadow-sm border border-border mb-6" x-data="{ copied: false }">
    <p class="text-xs text-muted-text font-medium mb-2">{{ __('app.referral_link') }}</p>
    <div class="flex items-center gap-2">
        <input type="text"
               class="flex-1 px-3 py-2 text-sm border border-border rounded-lg bg-surface text-secondary font-mono"
               value="{{ url('/r/' . $user->referral_code) }}"
               readonly
               id="detail-ref-link">
        <button type="button"
                @click="navigator.clipboard.writeText(document.getElementById('detail-ref-link').value); copied = true; setTimeout(() => copied = false, 2000)"
                class="px-4 py-2 rounded-lg border text-sm font-semibold transition"
                :class="copied ? 'bg-green-500/10 border-green-500/30 text-green-600' : 'bg-surface border-border text-secondary hover:bg-muted'">
            <span x-show="!copied">{{ __('app.copy') }}</span>
            <span x-show="copied" x-cloak>{{ __('app.copied_exclamation') }}</span>
        </button>
    </div>
</div>

{{-- Summary stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-blue-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.total_clicks') }}</p>
        </div>
        <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalClicks) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-purple-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.unique_visitors') }}</p>
        </div>
        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($uniqueClicks) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-green-500"></div>
            <p class="text-xs text-muted-text font-medium">Registrations</p>
        </div>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalRegistrations) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.conversion_rate') }}</p>
        </div>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $conversionRate }}%</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-red-500"></div>
            <p class="text-xs text-muted-text font-medium">Bounces</p>
        </div>
        <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($bounces) }}</p>
    </div>
</div>

{{-- Click trend bar chart --}}
@php
    $maxTrend = max(1, max($trendData));
@endphp
<div class="bg-card rounded-xl p-5 shadow-sm border border-border mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-4">Click Trend (Last 14 Days)</h2>
    <div class="flex items-end gap-1 h-32">
        @foreach($trendData as $date => $count)
        <div class="flex-1 flex flex-col items-center gap-1 group relative">
            <div class="w-full rounded-t bg-accent/80 hover:bg-accent transition-colors cursor-default relative"
                 style="height: {{ max(2, ($count / $maxTrend) * 100) }}%">
                <div class="absolute -top-6 left-1/2 -translate-x-1/2 hidden group-hover:block bg-primary text-on-accent text-[10px] font-bold px-1.5 py-0.5 rounded whitespace-nowrap z-10">
                    {{ $count }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    <div class="flex gap-1 mt-2">
        @foreach($trendData as $date => $count)
        <div class="flex-1 text-center">
            <span class="text-[9px] text-muted-text">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
        </div>
        @endforeach
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Referred Members --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border flex items-center justify-between">
            <h2 class="text-sm font-bold text-primary">{{ __('app.referred_members') }}</h2>
            <span class="text-xs text-muted-text font-medium bg-muted px-2 py-0.5 rounded-full">{{ $referredMembers->count() }}</span>
        </div>
        @if($referredMembers->isEmpty())
            <div class="px-4 py-8 text-center text-muted-text">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm">No registrations yet</p>
            </div>
        @else
            <div class="divide-y divide-border max-h-72 overflow-y-auto">
                @foreach($referredMembers as $member)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-green-500/10 flex items-center justify-center text-green-600 text-xs font-bold">
                            {{ strtoupper(substr($member->baptism_name, 0, 1)) }}
                        </div>
                        <p class="text-sm font-medium text-primary">{{ $member->baptism_name }}</p>
                    </div>
                    <p class="text-xs text-muted-text">{{ $member->created_at->format('M d, Y') }}</p>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Top Referrer Sources --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
            <h2 class="text-sm font-bold text-primary">{{ __('app.top_sources') }}</h2>
        </div>
        @if($topSources->isEmpty())
            <div class="px-4 py-8 text-center text-muted-text">
                <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-1.102-4.828a4 4 0 015.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <p class="text-sm">No referrer data yet</p>
            </div>
        @else
            <div class="divide-y divide-border">
                @foreach($topSources as $source)
                <div class="px-4 py-3 flex items-center justify-between gap-3">
                    <p class="text-sm text-secondary truncate flex-1" title="{{ $source->referer }}">{{ Str::limit($source->referer, 50) }}</p>
                    <span class="text-xs font-bold text-accent bg-accent/10 px-2 py-0.5 rounded-full shrink-0">{{ $source->total }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Visitors (grouped by IP) --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <h2 class="text-sm font-bold text-primary">Visitors</h2>
        <span class="text-xs text-muted-text font-medium bg-muted px-2 py-0.5 rounded-full">{{ $visitors->count() }} unique</span>
    </div>
    @if($visitors->isEmpty())
        <div class="px-4 py-8 text-center text-muted-text">
            <svg class="w-8 h-8 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
            </svg>
            <p class="text-sm">No clicks recorded yet</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">Visitor (IP)</th>
                        <th class="text-right px-4 py-2.5 font-semibold text-secondary">Clicks</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.first_visit') }}</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-secondary">{{ __('app.last_visit') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($visitors as $visitor)
                    <tr class="hover:bg-muted/40 transition">
                        <td class="px-4 py-2.5">
                            <span class="text-xs font-mono text-secondary bg-muted px-2 py-0.5 rounded" title="{{ $visitor->ip_address }}">
                                {{ $visitor->ip_address ? Str::mask($visitor->ip_address, '*', 0, (int) (strlen($visitor->ip_address) * 0.5)) : '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            @if($visitor->click_count > 1)
                                <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full">
                                    {{ $visitor->click_count }}x
                                </span>
                            @else
                                <span class="text-xs text-muted-text font-medium">1</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-muted-text tabular-nums whitespace-nowrap text-xs">
                            {{ \Carbon\Carbon::parse($visitor->first_click)->format('M d, Y H:i') }}
                        </td>
                        <td class="px-4 py-2.5 text-muted-text tabular-nums whitespace-nowrap text-xs">
                            @if($visitor->click_count > 1)
                                {{ \Carbon\Carbon::parse($visitor->last_click)->format('M d, Y H:i') }}
                            @else
                                <span class="text-muted-text/50">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

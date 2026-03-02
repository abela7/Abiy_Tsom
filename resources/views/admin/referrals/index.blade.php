@extends('layouts.admin')

@section('title', __('app.referral_tracking'))

@section('content')
<h1 class="text-2xl font-bold text-primary mb-1">{{ __('app.referral_tracking') }}</h1>
<p class="text-sm text-muted-text mb-6">{{ __('app.referral_tracking_subtitle') }}</p>

{{-- Summary stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
        <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($totalUniqueClicks) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-green-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.registrations') }}</p>
        </div>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($totalReferredMembers) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <div class="flex items-center gap-2 mb-1">
            <div class="w-2 h-2 rounded-full bg-amber-500"></div>
            <p class="text-xs text-muted-text font-medium">{{ __('app.conversion_rate') }}</p>
        </div>
        <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $overallConversionRate }}%</p>
    </div>
</div>

{{-- Click trend bar chart --}}
@php
    $maxTrend = max(1, max($trendData));
@endphp
<div class="bg-card rounded-xl p-5 shadow-sm border border-border mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-4">{{ __('app.click_trend') }}</h2>
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

{{-- Enable affiliate --}}
@if($availableAdmins->isNotEmpty())
<div class="bg-card rounded-xl p-5 shadow-sm border border-border mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-3">{{ __('app.enable_affiliate') }}</h2>
    <form x-data="{ userId: '' }" :action="userId ? '{{ url('admin/referrals') }}/' + userId + '/enable' : '#'" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
        @csrf
        <div class="flex-1">
            <select x-model="userId"
                    class="w-full px-3 py-2.5 rounded-lg bg-surface border border-border text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition">
                <option value="">{{ __('app.select_member') }}</option>
                @foreach($availableAdmins as $admin)
                    <option value="{{ $admin->id }}">{{ $admin->name }} ({{ $admin->role }})</option>
                @endforeach
            </select>
        </div>
        <button type="submit"
                :disabled="!userId"
                class="px-5 py-2.5 rounded-lg bg-accent text-on-accent text-sm font-semibold hover:bg-accent/90 transition disabled:opacity-40 disabled:cursor-not-allowed">
            {{ __('app.enable') }}
        </button>
    </form>
</div>
@endif

{{-- Leaderboard --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
    <div class="px-4 py-3 border-b border-border">
        <h2 class="text-sm font-bold text-primary">{{ __('app.referral_leaderboard') }}</h2>
    </div>

    @if($affiliates->isEmpty())
        <div class="px-4 py-12 text-center text-muted-text">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-1.102-4.828a4 4 0 015.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <p class="text-sm font-medium">{{ __('app.no_affiliates_yet') }}</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.rank') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.name') }}</th>
                        <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.referral_link') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.clicks') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.unique') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.registrations') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.bounces') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.conversion_rate') }}</th>
                        <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($affiliates as $index => $affiliate)
                    <tr class="hover:bg-muted/40 transition" x-data="{ confirmDisable: false, confirmRegen: false, copied: false }">
                        <td class="px-4 py-3 text-muted-text tabular-nums">
                            @if($index === 0 && $affiliate->total_registrations > 0)
                                <span class="text-amber-500 font-bold">1</span>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-primary">{{ $affiliate->name }}</p>
                                <p class="text-xs text-muted-text capitalize">{{ $affiliate->role }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <input id="ref-link-{{ $affiliate->id }}"
                                       type="text"
                                       class="w-44 max-w-full px-2 py-1.5 text-xs border border-border rounded-md bg-surface text-secondary font-mono"
                                       value="{{ url('/r/' . $affiliate->referral_code) }}"
                                       readonly>
                                <button type="button"
                                        @click="navigator.clipboard.writeText($el.previousElementSibling.value); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="px-2.5 py-1.5 rounded-md border text-xs font-semibold transition"
                                        :class="copied ? 'bg-green-500/10 border-green-500/30 text-green-600' : 'bg-surface border-border text-secondary hover:bg-muted'">
                                    <span x-show="!copied">{{ __('app.copy') }}</span>
                                    <span x-show="copied" x-cloak>{{ __('app.copied') }}</span>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium text-blue-600 dark:text-blue-400">{{ number_format($affiliate->total_clicks) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-medium text-purple-600 dark:text-purple-400">{{ number_format($affiliate->unique_clicks) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-bold text-green-600 dark:text-green-400">{{ number_format($affiliate->total_registrations) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-muted-text">{{ number_format($affiliate->bounces) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-amber-600 dark:text-amber-400">{{ $affiliate->conversion_rate }}%</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2 flex-wrap">
                                {{-- Regenerate --}}
                                <template x-if="!confirmRegen && !confirmDisable">
                                    <form method="POST" action="{{ route('admin.referrals.regenerate', $affiliate) }}">
                                        @csrf
                                        <button type="button"
                                                @click="confirmRegen = true"
                                                class="px-3 py-1.5 rounded-lg bg-accent-secondary/10 text-accent-secondary hover:bg-accent-secondary/20 text-xs font-semibold transition border border-accent-secondary/20">
                                            {{ __('app.regenerate') }}
                                        </button>
                                    </form>
                                </template>
                                <template x-if="confirmRegen">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-accent-secondary font-semibold">{{ __('app.confirm_regenerate') }}?</span>
                                        <form method="POST" action="{{ route('admin.referrals.regenerate', $affiliate) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-md bg-accent-secondary text-white text-xs font-bold hover:bg-accent-secondary/80 transition">
                                                {{ __('app.yes') }}
                                            </button>
                                        </form>
                                        <button type="button" @click="confirmRegen = false"
                                                class="px-2.5 py-1 rounded-md bg-muted text-secondary text-xs font-semibold hover:bg-border transition">
                                            {{ __('app.no') }}
                                        </button>
                                    </div>
                                </template>

                                {{-- Disable --}}
                                <template x-if="!confirmDisable && !confirmRegen">
                                    <button type="button"
                                            @click="confirmDisable = true"
                                            class="px-3 py-1.5 rounded-lg bg-red-600/10 text-red-600 hover:bg-red-600/20 text-xs font-semibold transition border border-red-600/20">
                                        {{ __('app.disable') }}
                                    </button>
                                </template>
                                <template x-if="confirmDisable">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-xs text-red-600 font-semibold">{{ __('app.confirm_disable') }}?</span>
                                        <form method="POST" action="{{ route('admin.referrals.disable', $affiliate) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1 rounded-md bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">
                                                {{ __('app.yes') }}
                                            </button>
                                        </form>
                                        <button type="button" @click="confirmDisable = false"
                                                class="px-2.5 py-1 rounded-md bg-muted text-secondary text-xs font-semibold hover:bg-border transition">
                                            {{ __('app.no') }}
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

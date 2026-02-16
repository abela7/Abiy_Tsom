@extends('layouts.admin')

@section('title', __('app.members_tracking'))

@section('content')
<h1 class="text-2xl font-bold text-primary mb-1">{{ __('app.members_tracking') }}</h1>
<p class="text-sm text-muted-text mb-6">{{ __('app.members_tracking_subtitle') }}</p>

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.total_members') }}</p>
        <p class="text-2xl font-black text-accent mt-1">{{ number_format($totalMembers) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.new_last_7_days') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($last7Days) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.new_last_30_days') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($last30Days) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.engaged_members') }}</p>
        <p class="text-2xl font-black text-success mt-1">{{ number_format($engagedMembers) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.passcode_users') }}</p>
        <p class="text-2xl font-black text-primary mt-1">{{ number_format($passcodeEnabled) }}</p>
    </div>
</div>

{{-- Date range --}}
@if($firstRegistration || $lastRegistration)
<div class="bg-card rounded-xl p-4 shadow-sm border border-border mb-6">
    <p class="text-xs font-semibold text-muted-text uppercase tracking-wider mb-3">{{ __('app.first_registration') }} / {{ __('app.last_registration') }}</p>
    <div class="flex flex-wrap gap-4 text-sm">
        @if($firstRegistration)
            <span class="font-medium">{{ \Carbon\Carbon::parse($firstRegistration)->format('d M Y') }}</span>
        @endif
        @if($lastRegistration)
            <span class="font-medium">{{ \Carbon\Carbon::parse($lastRegistration)->format('d M Y') }}</span>
        @endif
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Registrations by day --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
            <h2 class="text-sm font-bold text-primary">{{ __('app.registrations_by_day') }}</h2>
        </div>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted sticky top-0">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                        <th class="text-right px-4 py-2 font-semibold text-secondary">{{ __('app.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($registrationsByDay->reverse() as $row)
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-2 font-medium">
                                {{ \Carbon\Carbon::parse($row->date)->format('D, d M Y') }}
                            </td>
                            <td class="px-4 py-2 text-right font-bold text-accent">{{ $row->count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-muted-text">{{ __('app.no_registrations_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Locale & Theme --}}
    <div class="space-y-6">
        <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary">{{ __('app.locale_distribution') }}</h2>
            </div>
            <div class="p-4">
                @forelse($localeDistribution as $row)
                    <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                        <span class="font-medium">{{ $row->locale === 'en' ? __('app.english') : ($row->locale === 'am' ? __('app.amharic') : $row->locale) }}</span>
                        <span class="font-bold text-accent">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-muted-text text-sm py-4">{{ __('app.no_data_short') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary">{{ __('app.theme_distribution') }}</h2>
            </div>
            <div class="p-4">
                @forelse($themeDistribution as $row)
                    <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                        <span class="font-medium capitalize">{{ $row->theme }}</span>
                        <span class="font-bold text-accent">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-muted-text text-sm py-4">{{ __('app.no_data_short') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Engagement stats --}}
<div class="bg-card rounded-xl p-4 shadow-sm border border-border">
    <p class="text-xs font-semibold text-muted-text uppercase tracking-wider mb-2">{{ __('app.total_completions') }}</p>
    <p class="text-xl font-bold text-primary">
        {{ number_format($totalChecklistCompletions + $totalCustomCompletions) }}
        <span class="text-sm font-normal text-muted-text ml-1">({{ number_format($totalChecklistCompletions) }} standard + {{ number_format($totalCustomCompletions) }} custom)</span>
    </p>
</div>
@endsection

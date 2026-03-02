@extends('layouts.admin')

@section('title', __('app.dashboard'))

@section('content')
<h1 class="text-2xl font-bold text-primary mb-6">{{ __('app.dashboard') }}</h1>

{{-- Row 1: Season + Published + Total Members + Quick Actions --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-sm text-muted-text mb-1">{{ __('app.active_season') }}</p>
        @if($season)
            <p class="text-2xl font-bold text-accent">{{ $season->year }}</p>
            <p class="text-xs text-muted-text mt-1">{{ $season->start_date->format('M d') }} - {{ $season->end_date->format('M d') }}</p>
        @else
            <p class="text-lg font-semibold text-error">{{ __('app.none') }}</p>
            <a href="{{ route('admin.seasons.create') }}" class="text-xs text-accent font-medium">{{ __('app.create_one') }} &rarr;</a>
        @endif
    </div>

    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-sm text-muted-text mb-1">{{ __('app.published_days') }}</p>
        <p class="text-2xl font-bold text-accent">{{ $publishedDays }} <span class="text-base font-normal text-muted-text">/ {{ $totalDays }}</span></p>
        <div class="w-full h-2 bg-muted rounded-full mt-2 overflow-hidden">
            <div class="h-full bg-accent rounded-full" style="width: {{ $totalDays > 0 ? round(($publishedDays / $totalDays) * 100) : 0 }}%"></div>
        </div>
    </div>

    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-sm text-muted-text mb-1">{{ __('app.total_members') }}</p>
        <p class="text-2xl font-bold text-primary">{{ $totalMembers }}</p>
        <p class="text-xs text-green-500 font-semibold mt-1">+{{ $newToday }} today</p>
    </div>

    <div class="bg-accent rounded-xl p-4 shadow-sm text-on-accent">
        <p class="text-sm text-on-accent/70 mb-1">{{ __('app.quick_actions') }}</p>
        <div class="space-y-2 mt-2">
            <a href="{{ route('admin.daily.create') }}" class="block text-sm font-medium hover:text-accent-secondary transition">+ {{ __('app.add_daily_content') }}</a>
            <a href="{{ route('admin.activities.create') }}" class="block text-sm font-medium hover:text-accent-secondary transition">+ {{ __('app.add_activity') }}</a>
            <a href="{{ route('admin.translations.index') }}" class="block text-sm font-medium hover:text-accent-secondary transition">{{ __('app.manage_translations') }}</a>
        </div>
    </div>
</div>

{{-- Row 2: New Registrations --}}
<div class="mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-3">New Registrations</h2>
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                <p class="text-xs text-muted-text font-medium">Today</p>
            </div>
            <p class="text-2xl font-bold text-primary">{{ $newToday }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <p class="text-xs text-muted-text font-medium">Last 7 Days</p>
            </div>
            <p class="text-2xl font-bold text-primary">{{ $new7d }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                <p class="text-xs text-muted-text font-medium">Last 30 Days</p>
            </div>
            <p class="text-2xl font-bold text-primary">{{ $new30d }}</p>
        </div>
    </div>
</div>

{{-- Row 3: Active Users --}}
<div class="mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-3">Active Users</h2>
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                <p class="text-xs text-muted-text font-medium">Last 24h</p>
            </div>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $active24h }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                <p class="text-xs text-muted-text font-medium">Last 7 Days</p>
            </div>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $active7d }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-2 h-2 rounded-full bg-purple-500"></div>
                <p class="text-xs text-muted-text font-medium">Last 30 Days</p>
            </div>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ $active30d }}</p>
        </div>
    </div>
</div>

{{-- Row 4: Engagement + Connections --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs text-muted-text font-medium mb-1">Engaged Members</p>
        <p class="text-2xl font-bold text-primary">{{ $engagedMembers }}</p>
        @if($totalMembers > 0)
        <div class="w-full h-1.5 bg-muted rounded-full mt-2 overflow-hidden">
            <div class="h-full bg-amber-500 rounded-full" style="width: {{ round(($engagedMembers / $totalMembers) * 100) }}%"></div>
        </div>
        <p class="text-[11px] text-muted-text mt-1">{{ round(($engagedMembers / $totalMembers) * 100) }}% of all members</p>
        @endif
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs text-muted-text font-medium mb-1">Total Completions</p>
        <p class="text-2xl font-bold text-primary">{{ number_format($totalCompletions) }}</p>
        <p class="text-[11px] text-muted-text mt-1">Checklist items done</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs text-muted-text font-medium mb-1">Telegram</p>
        <p class="text-2xl font-bold text-blue-500">{{ $telegramConnected }}</p>
        <p class="text-[11px] text-muted-text mt-1">Connected accounts</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs text-muted-text font-medium mb-1">WhatsApp</p>
        <p class="text-2xl font-bold text-green-500">{{ $whatsappConnected }}</p>
        <p class="text-[11px] text-muted-text mt-1">Reminders active</p>
    </div>
</div>

{{-- Row 5: Registration Trend Chart --}}
@php
    $maxTrend = max(1, max($trendData));
@endphp
<div class="bg-card rounded-xl p-5 shadow-sm border border-border mb-6">
    <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-4">Registration Trend (Last 14 Days)</h2>
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

{{-- Row 6: Distributions --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    {{-- Locale --}}
    <div class="bg-card rounded-xl p-5 shadow-sm border border-border">
        <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-3">Language</h2>
        @php $localeTotal = max(1, $localeDistribution->sum()); @endphp
        <div class="space-y-3">
            @foreach($localeDistribution as $locale => $count)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-primary">{{ $locale === 'am' ? 'አማርኛ' : 'English' }}</span>
                    <span class="text-sm font-bold text-primary">{{ $count }} <span class="text-xs text-muted-text font-normal">({{ round(($count / $localeTotal) * 100) }}%)</span></span>
                </div>
                <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $locale === 'am' ? 'bg-amber-500' : 'bg-blue-500' }}" style="width: {{ round(($count / $localeTotal) * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Theme --}}
    <div class="bg-card rounded-xl p-5 shadow-sm border border-border">
        <h2 class="text-sm font-bold text-muted-text uppercase tracking-wider mb-3">Theme Preference</h2>
        @php $themeTotal = max(1, $themeDistribution->sum()); @endphp
        <div class="space-y-3">
            @foreach($themeDistribution as $theme => $count)
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm font-medium text-primary flex items-center gap-2">
                        @if($theme === 'dark')
                            <span class="w-3 h-3 rounded-full bg-gray-800 border border-gray-600"></span>
                        @else
                            <span class="w-3 h-3 rounded-full bg-white border border-gray-300"></span>
                        @endif
                        {{ ucfirst($theme ?? 'light') }}
                    </span>
                    <span class="text-sm font-bold text-primary">{{ $count }} <span class="text-xs text-muted-text font-normal">({{ round(($count / $themeTotal) * 100) }}%)</span></span>
                </div>
                <div class="w-full h-2 bg-muted rounded-full overflow-hidden">
                    <div class="h-full rounded-full {{ $theme === 'dark' ? 'bg-gray-600' : 'bg-yellow-400' }}" style="width: {{ round(($count / $themeTotal) * 100) }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

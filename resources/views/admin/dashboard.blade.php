@extends('layouts.admin')

@section('title', __('app.dashboard'))

@section('content')
<h1 class="text-2xl font-bold text-primary mb-6">{{ __('app.dashboard') }}</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    {{-- Season status --}}
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

    {{-- Published days --}}
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-sm text-muted-text mb-1">{{ __('app.published_days') }}</p>
        <p class="text-2xl font-bold text-accent">{{ $publishedDays }} <span class="text-base font-normal text-muted-text">/ {{ $totalDays }}</span></p>
        <div class="w-full h-2 bg-muted rounded-full mt-2 overflow-hidden">
            <div class="h-full bg-accent rounded-full" style="width: {{ $totalDays > 0 ? round(($publishedDays / $totalDays) * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- Members --}}
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-sm text-muted-text mb-1">{{ __('app.total_members') }}</p>
        <p class="text-2xl font-bold text-accent-secondary">{{ $totalMembers }}</p>
    </div>

    {{-- Quick link --}}
    <div class="bg-accent rounded-xl p-4 shadow-sm text-on-accent">
        <p class="text-sm text-on-accent/70 mb-1">{{ __('app.quick_actions') }}</p>
        <div class="space-y-2 mt-2">
            <a href="{{ route('admin.daily.create') }}" class="block text-sm font-medium hover:text-accent-secondary transition">+ {{ __('app.add_daily_content') }}</a>
            <a href="{{ route('admin.activities.create') }}" class="block text-sm font-medium hover:text-accent-secondary transition">+ {{ __('app.add_activity') }}</a>
            <a href="{{ route('admin.translations.index') }}" class="block text-sm font-medium hover:text-accent-secondary transition">{{ __('app.manage_translations') }}</a>
        </div>
    </div>
</div>
@endsection

@extends('layouts.member')

@php
    $locale = app()->getLocale();
    $ethFormatted = $ethDateInfo['ethiopian_date_formatted'] ?? '';
    $gregorianDate = $daily->date->locale('en')->translatedFormat('l, F j, Y');

    $annualCelebrations = $ethDateInfo['annual_celebrations'] ?? collect();
    $monthlyCelebrations = $ethDateInfo['monthly_celebrations'] ?? collect();
    $hasAnnuals = $annualCelebrations->isNotEmpty();
    $hasMonthlies = $monthlyCelebrations->isNotEmpty();
@endphp

@section('title', $ethFormatted . ' - ' . __('app.app_name'))

@section('content')
<div class="max-w-2xl mx-auto px-4 py-4 space-y-4">

    {{-- Back button --}}
    <div>
        <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('app.back') }}
        </a>
    </div>

    {{-- Header: Ethiopian date + Gregorian --}}
    <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
        <div class="px-5 py-5 text-center">
            <span class="text-[11px] font-semibold text-accent-secondary uppercase tracking-wider">{{ __('app.ethiopian_calendar_title') }}</span>
            <h1 class="text-xl font-black text-primary mt-1.5">{{ $ethFormatted }}</h1>
            <p class="text-sm text-muted-text mt-1">{{ $gregorianDate }}</p>
        </div>
    </div>

    {{-- Yearly Commemorations --}}
    @if($hasAnnuals)
    <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
        <div class="flex items-center gap-2.5 px-4 pt-4 pb-2">
            <div class="shrink-0 w-8 h-8 rounded-xl bg-accent-secondary/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.synaxarium_yearly_commemorations') }}</span>
        </div>

        <div class="px-4 pb-4 space-y-3">
            @foreach($annualCelebrations as $saint)
            <div class="flex items-start gap-3 {{ !$loop->first ? 'pt-3 border-t border-border' : '' }}">
                @if($saint->imageUrl())
                    <img src="{{ $saint->imageUrl() }}" alt="" class="w-14 h-14 rounded-xl object-cover shrink-0">
                @else
                    <div class="shrink-0 w-14 h-14 rounded-xl bg-accent-secondary/10 flex items-center justify-center">
                        <svg class="w-7 h-7 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="block text-base font-bold text-primary">{{ localized($saint, 'celebration') }}</span>
                    <span class="block text-[11px] text-accent-secondary font-semibold mt-0.5">{{ __('app.synaxarium_annual_feast') }}</span>
                    @if(localized($saint, 'description'))
                        <p class="text-sm text-secondary mt-1.5 leading-relaxed whitespace-pre-line">{{ localized($saint, 'description') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Monthly Commemorations --}}
    @if($hasMonthlies)
    <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
        <div class="flex items-center gap-2.5 px-4 pt-4 pb-2">
            <div class="shrink-0 w-8 h-8 rounded-xl bg-accent/10 flex items-center justify-center">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.synaxarium_monthly_commemorations') }}</span>
        </div>

        <div class="px-4 pb-3 space-y-1.5">
            @foreach($monthlyCelebrations as $saint)
            <div class="flex items-center gap-3 px-2 py-2 rounded-xl">
                @if($saint->imageUrl())
                    <img src="{{ $saint->imageUrl() }}" alt="" class="w-10 h-10 rounded-xl object-cover shrink-0">
                @else
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                @endif
                <span class="text-sm font-semibold text-primary">{{ localized($saint, 'celebration') }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if(!$hasAnnuals && !$hasMonthlies)
    <div class="rounded-xl bg-muted/20 p-6 text-center">
        <p class="text-sm text-muted-text">{{ __('app.synaxarium_no_saints_for_day') }}</p>
    </div>
    @endif

</div>
@endsection

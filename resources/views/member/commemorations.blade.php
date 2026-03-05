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
<div x-data="{ expandedSaint: null, showImageModal: false, modalImage: '' }" class="max-w-2xl mx-auto px-4 pt-3 pb-6 space-y-5">

    {{-- Sticky back bar --}}
    <div class="flex items-center gap-3">
        <a href="javascript:history.back()" class="shrink-0 w-9 h-9 rounded-xl bg-muted hover:bg-border flex items-center justify-center text-muted-text hover:text-primary transition-all active:scale-95">
            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-black text-primary truncate">{{ $ethFormatted }}</h1>
            <p class="text-[11px] text-muted-text">{{ $gregorianDate }}</p>
        </div>
    </div>

    {{-- Yearly Commemorations --}}
    @if($hasAnnuals)
    <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
        <div class="flex items-center gap-2.5 px-4 pt-4 pb-2">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-accent-secondary/15 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-accent-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><line x1="4" y1="8" x2="20" y2="8"/></svg>
            </div>
            <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.synaxarium_yearly_commemorations') }}</span>
        </div>

        <div class="pb-3 space-y-0 divide-y divide-border">
            @foreach($annualCelebrations as $saint)
            @php $hasImage = (bool) $saint->imageUrl(); $hasDesc = (bool) localized($saint, 'description'); @endphp
            <div class="px-4 py-3">
                {{-- Image as a contained hero banner --}}
                @if($hasImage)
                <div class="relative rounded-xl overflow-hidden mb-3 cursor-pointer"
                     @click="showImageModal = true; modalImage = '{{ $saint->imageUrl() }}'">
                    <img src="{{ $saint->imageUrl() }}" alt="" class="w-full h-36 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 px-3 pb-2.5">
                        <span class="inline-block px-1.5 py-0.5 rounded bg-accent-secondary/90 text-[9px] font-bold text-white uppercase tracking-wider">{{ __('app.synaxarium_annual_feast') }}</span>
                    </div>
                </div>
                @endif

                {{-- Name --}}
                <div class="flex items-center gap-2.5">
                    @if(!$hasImage)
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-accent-secondary/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-accent-secondary" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z"/>
                        </svg>
                    </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-primary leading-snug">{{ localized($saint, 'celebration') }}</span>
                        @if(!$hasImage)
                        <span class="block text-[10px] text-accent-secondary font-semibold mt-0.5 uppercase tracking-wide">{{ __('app.synaxarium_annual_feast') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                @if($hasDesc)
                <div class="mt-2" x-data="{ expanded: false }">
                    <p class="text-[13px] text-secondary leading-relaxed whitespace-pre-line"
                       :class="expanded ? '' : 'line-clamp-2'">{{ localized($saint, 'description') }}</p>
                    <button @click="expanded = !expanded"
                            class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-accent hover:text-accent-hover transition-colors">
                        <span x-text="expanded ? '{{ __('app.show_less') ?? 'Show less' }}' : '{{ __('app.read_more') ?? 'Read more' }}'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Monthly Commemorations --}}
    @if($hasMonthlies)
    <div>
        <div class="flex items-center gap-2 mb-3">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-sinksar/15 flex items-center justify-center">
                {{-- Cross icon --}}
                <svg class="w-3.5 h-3.5 text-sinksar" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><line x1="4" y1="8" x2="20" y2="8"/></svg>
            </div>
            <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.synaxarium_monthly_commemorations') }}</span>
        </div>

        <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden divide-y divide-border">
            @foreach($monthlyCelebrations as $saint)
            <div class="flex items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors active:scale-[0.99]">
                {{-- Use Saints.png as icon for monthly saints --}}
                @if($saint->imageUrl())
                    <img src="{{ $saint->imageUrl() }}" alt="" class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-sm ring-1 ring-border">
                @else
                    <div class="shrink-0 w-11 h-11 rounded-xl overflow-hidden shadow-sm ring-1 ring-border">
                        <img src="{{ asset('images/Saints.png') }}" alt="" class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="block text-sm font-bold text-primary leading-snug">{{ localized($saint, 'celebration') }}</span>
                    <span class="block text-[10px] text-sinksar font-semibold mt-0.5 uppercase tracking-wide">{{ __('app.synaxarium_monthly_commemorations') }}</span>
                </div>
                {{-- Cross ornament --}}
                <div class="shrink-0 text-sinksar/30">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z"/>
                    </svg>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty state --}}
    @if(!$hasAnnuals && !$hasMonthlies)
    <div class="rounded-2xl bg-card border border-border shadow-sm p-8 text-center">
        <div class="w-16 h-16 rounded-2xl bg-muted/50 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-muted-text/50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><line x1="4" y1="8" x2="20" y2="8"/></svg>
        </div>
        <p class="text-sm text-muted-text font-medium">{{ __('app.synaxarium_no_saints_for_day') }}</p>
    </div>
    @endif

    {{-- Fullscreen image modal --}}
    <div x-show="showImageModal" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showImageModal = false"
         class="fixed inset-0 z-[200] bg-black/90 backdrop-blur-sm flex items-center justify-center p-4">
        <button @click="showImageModal = false" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="modalImage" alt="" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl" @click.stop>
    </div>
</div>
@endsection

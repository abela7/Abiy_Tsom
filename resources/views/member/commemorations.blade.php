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
            <p class="text-xs text-muted-text">{{ $gregorianDate }}</p>
        </div>
    </div>

    {{-- Yearly Commemorations --}}
    @if($hasAnnuals)
    <div class="space-y-3">
        <h2 class="text-base font-black text-primary text-center">{{ __('app.synaxarium_yearly_commemorations') }}</h2>

        @foreach($annualCelebrations as $index => $saint)
        @php $hasImage = (bool) $saint->imageUrl(); $hasDesc = (bool) localized($saint, 'description'); @endphp
        <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
            {{-- Image as a contained hero banner --}}
            @if($hasImage)
            <div class="relative h-64 overflow-hidden cursor-pointer"
                 @click="showImageModal = true; modalImage = '{{ $saint->imageUrl() }}'">
                {{-- Blurred ambient background --}}
                <img src="{{ $saint->imageUrl() }}" alt=""
                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                     decoding="async"
                     class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none">
                {{-- Warm golden veil to match Orthodox iconography tones --}}
                <div class="absolute inset-0 bg-gradient-to-br from-amber-900/30 via-transparent to-black/40"></div>
                {{-- Main image, sharp and centred --}}
                <img src="{{ $saint->imageUrl() }}" alt=""
                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                     decoding="async"
                     class="relative z-10 h-full w-full object-contain drop-shadow-[0_4px_24px_rgba(0,0,0,0.6)]">
                {{-- Bottom gradient for badge legibility --}}
                <div class="absolute inset-0 z-20 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 right-0 px-3 pb-2.5 z-30">
                    <span class="inline-block px-1.5 py-0.5 rounded bg-accent-secondary/90 text-[9px] font-bold text-white uppercase tracking-wider">{{ __('app.synaxarium_annual_feast') }}</span>
                </div>
            </div>
            @endif

            <div class="px-4 py-3">
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
                        <span class="block text-base font-bold text-primary leading-snug">{{ localized($saint, 'celebration') }}</span>
                        @if(!$hasImage)
                        <span class="block text-[10px] text-accent-secondary font-semibold mt-0.5 uppercase tracking-wide">{{ __('app.synaxarium_annual_feast') }}</span>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                @if($hasDesc)
                <div class="mt-2.5" x-data="{ expanded: false }">
                    <p class="text-sm text-secondary leading-relaxed whitespace-pre-line"
                       :class="expanded ? '' : 'line-clamp-2'">{{ localized($saint, 'description') }}</p>
                    <button @click="expanded = !expanded"
                            class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-accent hover:text-accent-hover transition-colors">
                        <span x-text="expanded ? '{{ __('app.show_less') ?? 'Show less' }}' : '{{ __('app.read_more') ?? 'Read more' }}'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Monthly Commemorations --}}
    @if($hasMonthlies)
    <div class="space-y-3">
        <h2 class="text-base font-black text-primary text-center">{{ __('app.synaxarium_monthly_commemorations') }}</h2>

        @foreach($monthlyCelebrations as $index => $saint)
        @php $monthlyImage = $saint->imageUrl(); $monthlyDesc = localized($saint, 'description'); $hasMonthlyDetail = $monthlyImage || $monthlyDesc; @endphp
        <div x-data="{ open: false }" class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
            <div class="px-4 flex items-center gap-3 py-3 {{ $hasMonthlyDetail ? 'cursor-pointer' : '' }}" @if($hasMonthlyDetail) @click="open = !open" @endif>
                {{-- Thumbnail --}}
                @if($monthlyImage)
                    <img src="{{ $monthlyImage }}" alt="" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-sm ring-1 ring-border">
                @else
                    <div class="shrink-0 w-11 h-11 rounded-xl overflow-hidden shadow-sm ring-1 ring-border">
                        <img src="{{ asset('images/Saints.png') }}" alt="" loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async" class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="block text-sm font-bold text-primary leading-snug">{{ localized($saint, 'celebration') }}</span>
                    <span class="block text-[10px] text-sinksar font-semibold mt-0.5 tracking-wide">{{ $ethDateInfo['ethiopian_date']['month_name_' . $locale] ?? '' }} {{ $ethDateInfo['ethiopian_date']['day'] ?? $saint->day }}</span>
                </div>
                {{-- Chevron only if expandable --}}
                @if($hasMonthlyDetail)
                <div class="shrink-0 text-muted-text">
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
                @endif
            </div>

            {{-- Expandable detail --}}
            @if($hasMonthlyDetail)
            <div x-show="open" x-collapse x-cloak class="px-4 pb-3 space-y-2.5">
                @if($monthlyImage)
                <div class="relative h-52 rounded-xl overflow-hidden cursor-pointer"
                     @click.stop="showImageModal = true; modalImage = '{{ $monthlyImage }}'">
                    {{-- Blurred ambient background --}}
                    <img src="{{ $monthlyImage }}" alt=""
                         loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                         decoding="async"
                         class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-900/25 via-transparent to-black/35"></div>
                    {{-- Main image --}}
                    <img src="{{ $monthlyImage }}" alt=""
                         loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                         decoding="async"
                         class="relative z-10 h-full w-full object-contain drop-shadow-[0_4px_20px_rgba(0,0,0,0.55)]">
                    <div class="absolute inset-0 z-20 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
                </div>
                @endif
                @if($monthlyDesc)
                <p class="text-sm text-secondary leading-relaxed whitespace-pre-line">{{ $monthlyDesc }}</p>
                @endif
            </div>
            @endif
        </div>
        @endforeach
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

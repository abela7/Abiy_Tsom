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

    {{-- Hero date banner --}}
    <div class="relative rounded-2xl overflow-hidden bg-gradient-to-br from-accent via-accent to-accent-secondary shadow-lg">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 400 160" fill="none">
                {{-- Decorative crosses pattern --}}
                <g opacity="0.4" stroke="white" stroke-width="1.5">
                    <line x1="50" y1="20" x2="50" y2="60"/><line x1="30" y1="40" x2="70" y2="40"/>
                    <line x1="350" y1="100" x2="350" y2="140"/><line x1="330" y1="120" x2="370" y2="120"/>
                    <line x1="200" y1="10" x2="200" y2="40"/><line x1="185" y1="25" x2="215" y2="25"/>
                    <line x1="100" y1="110" x2="100" y2="150"/><line x1="80" y1="130" x2="120" y2="130"/>
                    <line x1="300" y1="30" x2="300" y2="60"/><line x1="285" y1="45" x2="315" y2="45"/>
                </g>
            </svg>
        </div>
        <div class="relative px-5 py-6 text-center">
            <span class="inline-block px-3 py-1 rounded-full bg-white/15 backdrop-blur-sm text-[10px] font-bold text-white uppercase tracking-widest">{{ __('app.ethiopian_calendar_title') }}</span>
            <h2 class="text-2xl font-black text-white mt-2.5 drop-shadow-sm">{{ $ethFormatted }}</h2>
            <p class="text-sm text-white/70 mt-1">{{ $gregorianDate }}</p>
            <div class="flex items-center justify-center gap-4 mt-3">
                @if($hasAnnuals)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/15 backdrop-blur-sm text-[11px] font-semibold text-white">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    {{ $annualCelebrations->count() }}
                </span>
                @endif
                @if($hasMonthlies)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white/15 backdrop-blur-sm text-[11px] font-semibold text-white">
                    {{-- Cross icon --}}
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"/><line x1="4" y1="8" x2="20" y2="8"/></svg>
                    {{ $monthlyCelebrations->count() }}
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Yearly Commemorations --}}
    @if($hasAnnuals)
    <div>
        <div class="flex items-center gap-2 mb-3">
            <div class="shrink-0 w-7 h-7 rounded-lg bg-accent-secondary/15 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            </div>
            <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.synaxarium_yearly_commemorations') }}</span>
        </div>

        <div class="space-y-4">
            @foreach($annualCelebrations as $saint)
            @php $hasImage = (bool) $saint->imageUrl(); $hasDesc = (bool) localized($saint, 'description'); @endphp
            <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden transition-all hover:shadow-md">
                {{-- Saint image as hero banner --}}
                @if($hasImage)
                <div class="relative cursor-pointer" @click="showImageModal = true; modalImage = '{{ $saint->imageUrl() }}'">
                    <img src="{{ $saint->imageUrl() }}" alt="{{ localized($saint, 'celebration') }}" class="w-full h-48 object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-0 left-0 right-0 px-4 pb-3.5">
                        <span class="inline-block px-2 py-0.5 rounded-md bg-accent-secondary/90 text-[10px] font-bold text-white uppercase tracking-wider mb-1.5">{{ __('app.synaxarium_annual_feast') }}</span>
                        <h3 class="text-lg font-black text-white drop-shadow-md leading-tight">{{ localized($saint, 'celebration') }}</h3>
                    </div>
                    {{-- Zoom hint --}}
                    <div class="absolute top-3 right-3 w-8 h-8 rounded-full bg-black/30 backdrop-blur-sm flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/></svg>
                    </div>
                </div>
                @else
                {{-- No image: compact header with cross icon --}}
                <div class="px-4 pt-4 pb-3 flex items-start gap-3">
                    <div class="shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-accent-secondary/20 to-accent-secondary/5 flex items-center justify-center">
                        {{-- Ethiopian-style cross --}}
                        <svg class="w-6 h-6 text-accent-secondary" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z"/>
                            <rect x="8" y="0" width="2" height="2" rx="0.5"/>
                            <rect x="14" y="0" width="2" height="2" rx="0.5"/>
                            <rect x="2" y="8" width="2" height="2" rx="0.5"/>
                            <rect x="20" y="8" width="2" height="2" rx="0.5"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <span class="inline-block px-2 py-0.5 rounded-md bg-accent-secondary/10 text-[10px] font-bold text-accent-secondary uppercase tracking-wider mb-1">{{ __('app.synaxarium_annual_feast') }}</span>
                        <h3 class="text-base font-black text-primary leading-snug">{{ localized($saint, 'celebration') }}</h3>
                    </div>
                </div>
                @endif

                {{-- Description with expand/collapse --}}
                @if($hasDesc)
                <div class="px-4 pb-4 {{ $hasImage ? 'pt-3' : '' }}">
                    <div x-data="{ expanded: false }">
                        <p class="text-sm text-secondary leading-relaxed whitespace-pre-line"
                           :class="expanded ? '' : 'line-clamp-3'"
                           x-ref="descText">{{ localized($saint, 'description') }}</p>
                        <button @click="expanded = !expanded"
                                class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-accent hover:text-accent-hover transition-colors">
                            <span x-text="expanded ? '{{ __('app.show_less') ?? 'Show less' }}' : '{{ __('app.read_more') ?? 'Read more' }}'"></span>
                            <svg class="w-3.5 h-3.5 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                    </div>
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

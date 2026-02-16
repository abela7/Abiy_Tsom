@extends('layouts.member')

@section('title', __('app.calendar_title') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-6 pb-10 space-y-5 max-w-2xl mx-auto sm:px-6">
    {{-- Header --}}
    <div class="text-center sm:text-left">
        <h1 class="text-2xl font-bold text-primary tracking-tight sm:text-3xl">{{ __('app.calendar_title') }}</h1>
        @if($season)
            <div class="inline-flex items-center gap-2 mt-1 px-3 py-1 rounded-full bg-accent/5 border border-accent/10">
                <span class="text-sm font-medium text-accent">
                    {{ $season->start_date?->locale('en')->translatedFormat('M j') }} – {{ $season->end_date?->locale('en')->translatedFormat('M j, Y') }}
                </span>
                @if($season->year)
                    <span class="w-1 h-1 rounded-full bg-muted-text/40"></span>
                    <span class="text-sm text-muted-text">{{ $season->year }}</span>
                @endif
            </div>
        @endif
    </div>

    @if(! empty($weeks))
        @php
            $dayToken = $dayToken ?? null;
        @endphp

        {{-- Color Legend --}}
        <div class="flex items-center justify-center gap-3 sm:gap-4 text-[11px] font-medium">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm border-2 border-cal-past-border bg-cal-past-bg"></span>
                <span class="text-cal-past-text">{{ __('app.calendar_passed') }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm border-2 border-cal-today-border bg-cal-today-bg"></span>
                <span class="text-cal-today-text font-bold">{{ __('app.today') }}</span>
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 rounded-sm border-2 border-cal-upcoming-border bg-cal-upcoming-bg"></span>
                <span class="text-cal-upcoming-text">{{ __('app.calendar_upcoming') }}</span>
            </span>
        </div>

        <div class="space-y-5">
            @foreach($weeks as $week)
                <section class="space-y-2">
                    {{-- Week Header --}}
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-start gap-2">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-bold text-accent uppercase tracking-widest leading-none mb-0.5">
                                    {{ __('app.week', ['number' => $week['number']]) }}
                                </span>
                                <h2 class="text-base sm:text-lg font-bold text-primary leading-tight">{{ $week['name'] }}</h2>
                                <p class="text-[11px] text-muted-text mt-0.5 italic opacity-80">{{ $week['meaning'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Day Grid: 4 cols on mobile, 7 on sm+ — compact layout --}}
                    <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5 sm:gap-2">
                        @foreach($week['days'] as $day)
                            @php
                                $isToday = $day['is_today'];
                                $isPast = $day['is_past'];
                                $isFuture = $day['is_future'];
                                $hasContent = $day['has_content'];
                                $content = $day['content'];
                                $pct = $day['pct'];
                            @endphp

                            <div @if($isToday) id="current-day" @endif class="relative">
                                @if($hasContent)
                                    <a href="{{ route('member.day', $content) }}{{ $dayToken ? '?token=' . e($dayToken) : '' }}"
                                       class="relative aspect-square min-w-0 flex flex-col items-center justify-center rounded-xl transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md active:scale-95 group
                                              @if($isToday)
                                                  bg-cal-today-bg border-2 border-cal-today-border shadow-md scale-[1.02] z-10 ring-2 ring-cal-today-border/25
                                              @elseif($isPast)
                                                  bg-cal-past-bg border border-cal-past-border
                                              @else
                                                  bg-cal-upcoming-bg border border-cal-upcoming-border
                                              @endif">
                                        
                                        {{-- Day Number --}}
                                        <span class="group-hover:scale-105 transition-transform
                                                     @if($isToday) text-base font-black text-cal-today-text
                                                     @elseif($isPast) text-xs font-bold text-cal-past-text
                                                     @else text-xs font-bold text-cal-upcoming-text @endif">
                                            {{ $day['day_number'] }}
                                        </span>
                                        
                                        {{-- Date --}}
                                        <span class="@if($isToday) text-[10px] font-bold text-cal-today-text
                                                      @elseif($isPast) text-[9px] sm:text-[10px] text-cal-past-text
                                                      @else text-[9px] sm:text-[10px] text-cal-upcoming-text @endif">
                                            {{ $day['date']->locale('en')->translatedFormat('M j') }}
                                        </span>

                                        @if($isToday)
                                            <div class="absolute -top-1.5 left-1/2 -translate-x-1/2 px-2 py-0.5 rounded-full bg-cal-today-border text-[8px] font-black uppercase tracking-wider shadow whitespace-nowrap"
                                                 style="color: #fff;">
                                                {{ __('app.today') }}
                                            </div>
                                        @endif
                                    </a>
                                @else
                                    <div class="aspect-square min-w-0 flex flex-col items-center justify-center rounded-xl border
                                                @if($isPast) border-cal-past-border bg-cal-past-bg opacity-50
                                                @else border-cal-upcoming-border bg-cal-upcoming-bg opacity-50 @endif">
                                        <span class="text-[10px] font-bold @if($isPast) text-cal-past-text @else text-cal-upcoming-text @endif">{{ $day['day_number'] }}</span>
                                        <span class="text-[9px] sm:text-[10px] @if($isPast) text-cal-past-text @else text-cal-upcoming-text @endif">{{ $day['date']->locale('en')->translatedFormat('M j') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="text-center py-20 bg-card rounded-3xl border border-dashed border-border px-6">
            <div class="w-20 h-20 bg-muted rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-primary mb-2">{{ __('app.no_calendar_content') }}</h3>
            <p class="text-sm text-muted-text mb-8">{{ __('app.check_back_soon') }}</p>
            <a href="{{ route('member.home') }}{{ $dayToken ? '?token=' . e($dayToken) : '' }}"
               class="inline-flex items-center gap-2 px-6 py-3 bg-accent text-on-accent rounded-2xl font-bold text-sm shadow-lg hover:opacity-90 active:scale-95 transition-all">
                {{ __('app.nav_home') }}
            </a>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const currentDay = document.getElementById('current-day');
        if (currentDay) {
            setTimeout(() => {
                currentDay.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 100);
        }
    });
</script>
@endpush

@extends('layouts.member')

@section('title', __('app.nav_home') . ' - ' . __('app.app_name'))

@section('content')
<div class="px-4 pt-4 space-y-4">

    {{-- View Today — hero CTA card --}}
    @php $dayToken = isset($member) && $member?->token ? '?token=' . e($member->token) : ''; @endphp
    @if(isset($viewTodayTarget) && $viewTodayTarget)
    <a href="{{ route('member.day', $viewTodayTarget) }}{{ $dayToken }}"
       class="group relative block overflow-hidden rounded-3xl bg-gradient-to-br from-accent via-accent to-accent-hover dark:from-accent-hover dark:via-accent-hover dark:to-[#7a5a08] transition-all duration-300 active:scale-[0.98]">

        <div class="relative flex items-center gap-4 p-5 sm:p-6">
            {{-- Day number badge --}}
            @if($season && $today)
            <div class="shrink-0 w-16 h-16 sm:w-[4.5rem] sm:h-[4.5rem] rounded-2xl bg-white/20 dark:bg-white/25 backdrop-blur-md border border-white/30 flex flex-col items-center justify-center shadow-[inset_0_1px_0_rgba(255,255,255,0.3),0_4px_12px_rgba(0,0,0,0.15)]">
                <span class="text-3xl sm:text-4xl font-black text-on-accent dark:text-white leading-none drop-shadow-sm">{{ $today->day_number }}</span>
                <span class="text-[9px] sm:text-[10px] font-bold text-white uppercase tracking-wider">{{ __('app.of_total', ['total' => $season->total_days]) }}</span>
            </div>
            @endif

            {{-- Text content --}}
            <div class="flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-semibold text-white mb-0.5">
                    {{ now()->locale('en')->translatedFormat('l, j F Y') }}
                </p>
                <h2 class="text-xl sm:text-2xl font-black text-on-accent dark:text-white leading-tight drop-shadow-sm">
                    {{ $today ? __('app.view_today') : __('app.view_recommended_day') }}
                </h2>
                @if($today && $today->weeklyTheme)
                <p class="text-sm sm:text-base font-medium text-white mt-0.5 truncate">
                    {{ localized($today->weeklyTheme, 'name') ?? $today->weeklyTheme->name_en ?? '' }}
                </p>
                @endif
            </div>

            {{-- Arrow indicator with nudge animation --}}
            <div class="shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 shadow-inner flex items-center justify-center group-hover:bg-white/25 group-hover:scale-105 transition-all duration-200">
                <svg class="w-6 h-6 sm:w-7 sm:h-7 text-white animate-nudge-right" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </div>
        </div>
    </a>
    @else
    <a href="{{ route('member.calendar') }}{{ $dayToken }}"
       class="group relative block overflow-hidden rounded-3xl bg-card border border-border shadow-md hover:shadow-lg transition-all duration-300 active:scale-[0.98]">
        <div class="relative flex items-center gap-4 p-5 sm:p-6">
            <div class="flex-1 min-w-0">
                <p class="text-xs sm:text-sm font-medium text-muted-text mb-0.5">
                    {{ now()->locale('en')->translatedFormat('l, j F Y') }}
                </p>
                <h2 class="text-lg sm:text-xl font-bold text-primary leading-tight">
                    {{ __('app.view_today') }}
                </h2>
            </div>
            <div class="shrink-0 w-10 h-10 rounded-xl bg-muted flex items-center justify-center group-hover:bg-border transition-colors">
                <svg class="w-5 h-5 text-muted-text group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </div>
        </div>
    </a>
    @endif

    {{-- Easter countdown — visible to all members, mobile-first --}}
    <div class="relative overflow-hidden rounded-3xl shadow-2xl border border-white/10 bg-gradient-to-br from-[#0a6286] via-[#134e5e] to-[#0a6286]"
         x-data="easterCountdown('{{ $easterAt->format('c') }}', '{{ $lentStartAt->format('c') }}')">

        {{-- Decorative ambient glows (fixed gold — same in light/dark) --}}
        <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-easter-gold/20 blur-[80px] pointer-events-none"></div>
        <div class="absolute -bottom-24 -left-24 w-64 h-64 rounded-full bg-white/5 blur-[80px] pointer-events-none"></div>

        <div class="relative px-4 py-3 sm:px-6 sm:py-4 text-white" x-show="totalSeconds > 0">

            {{-- Top row: title + circular % ring --}}
            <div class="flex items-center justify-between gap-3 mb-3 sm:mb-4">
                <h2 class="text-sm sm:text-2xl font-black tracking-tight leading-tight whitespace-nowrap">{{ __('app.easter_countdown') }}</h2>

                {{-- Circular progress ring --}}
                <div class="relative w-10 h-10 sm:w-16 sm:h-16 shrink-0">
                    <svg class="w-full h-full -rotate-90" viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="28" fill="none"
                                stroke="currentColor" stroke-width="5" class="text-white/10"/>
                        <circle cx="32" cy="32" r="28" fill="none"
                                stroke="currentColor" stroke-width="5"
                                stroke-linecap="round"
                                class="text-easter-gold transition-all duration-1000 ease-out"
                                :stroke-dasharray="2 * Math.PI * 28"
                                :stroke-dashoffset="2 * Math.PI * 28 * (1 - (100 - progressPct) / 100)"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xs sm:text-base font-black text-easter-gold" x-text="animatedPct + '%'"></span>
                    </div>
                </div>
            </div>

            {{-- Countdown digits --}}
            <div class="grid grid-cols-4 gap-2 sm:gap-3">
                {{-- Days --}}
                <div class="relative flex flex-col items-center py-2 sm:py-3.5 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(days)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1 font-bold uppercase tracking-widest">{{ __('app.days') }}</span>
                </div>
                {{-- Hours --}}
                <div class="relative flex flex-col items-center py-2 sm:py-3.5 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(hours)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1 font-bold uppercase tracking-widest">{{ __('app.hours') }}</span>
                </div>
                {{-- Minutes --}}
                <div class="relative flex flex-col items-center py-2 sm:py-3.5 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm" x-text="pad(minutes)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1 font-bold uppercase tracking-widest">{{ __('app.minutes') }}</span>
                </div>
                {{-- Seconds --}}
                <div class="relative flex flex-col items-center py-2 sm:py-3.5 rounded-2xl bg-white/5 backdrop-blur-md border border-white/10 shadow-inner group transition-all duration-300 hover:bg-white/10">
                    <span class="text-xl sm:text-5xl font-black tabular-nums leading-none tracking-tighter text-white drop-shadow-sm easter-pulse" x-text="pad(seconds)">—</span>
                    <span class="text-[8px] sm:text-[11px] text-white/60 mt-1 font-bold uppercase tracking-widest">{{ __('app.seconds') }}</span>
                </div>
            </div>

            {{-- Bottom progress bar (100 → 0) --}}
            <div class="mt-3 sm:mt-4">
                <div class="h-2 sm:h-3 w-full bg-white/10 rounded-full border border-white/5 relative">
                    <div class="absolute inset-y-0 left-0 rounded-full bg-easter-gold transition-all duration-1000 ease-out progress-striped"
                         :style="'width: max(' + (100 - progressPct) + '%, 0.75rem)'"></div>
                </div>
            </div>
        </div>

        {{-- Easter reached --}}
        <div x-show="totalSeconds <= 0" class="relative p-8 sm:p-12 text-center text-white space-y-4">
            <div class="mx-auto w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-easter-gold/20 flex items-center justify-center border border-easter-gold/30 animate-bounce">
                <svg class="w-8 h-8 sm:w-10 sm:h-10 text-easter-gold" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L14.09 8.26L21 9.27L16 13.14L17.18 20.02L12 16.77L6.82 20.02L8 13.14L3 9.27L9.91 8.26L12 2Z"/>
                </svg>
            </div>
            <h2 class="text-2xl sm:text-5xl font-black tracking-tighter leading-none">{{ __('app.christ_is_risen') }}</h2>
            <p class="text-white/70 text-base sm:text-lg font-medium">{{ __('app.easter_countdown_subtitle') }}</p>
        </div>
    </div>

    {{-- Announcements --}}
    @if($announcements->isNotEmpty())
    @php $navToken = isset($currentMember) ? '?token=' . e($currentMember->token) : ''; @endphp
    <section x-data="announcementDisplay({{ $announcements->count() }})" x-init="init()">
        <div class="flex items-center justify-between gap-2 mb-4">
            <h2 class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ __('app.announcements_section') }}</h2>
            <div class="inline-flex items-center rounded-xl bg-muted/50 p-1 gap-0.5 border border-border/50">
                <button type="button"
                        class="w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                        :class="mode === 'list' ? 'bg-accent text-on-accent shadow-sm' : 'text-muted-text hover:text-primary hover:bg-muted'"
                        @click="setMode('list')"
                        aria-label="List view"
                        :aria-pressed="mode === 'list'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <button type="button"
                        class="w-8 h-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                        :class="mode === 'carousel' ? 'bg-accent text-on-accent shadow-sm' : 'text-muted-text hover:text-primary hover:bg-muted'"
                        @click="setMode('carousel')"
                        aria-label="Carousel view"
                        :aria-pressed="mode === 'carousel'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 012-2h4a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm10-1h8m-8 5h8m-8 5h8"/>
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="mode === 'list'" x-cloak x-transition.opacity>
            <div class="space-y-4">
                @foreach($announcements as $index => $announcement)
                @php
                    $announcementTitle = $announcement->titleForLocale();
                    $announcementDescription = $announcement->descriptionForLocale();
                    $announcementPhotoUrl = $announcement->photoUrlForLocale();
                @endphp
                <article class="rounded-2xl shadow-lg border border-border overflow-hidden bg-card">
                    <a href="{{ route('member.announcement.show', $announcement) }}{{ $navToken }}" class="block group">
                        @if($announcementPhotoUrl)
                            <div class="relative w-full aspect-[16/9] overflow-hidden bg-muted">
                                <img src="{{ $announcementPhotoUrl }}" alt=""
                                     class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                                @if($announcement->hasYoutubeVideo())
                                    <div class="absolute bottom-2 right-2 flex items-center gap-1 px-2 py-1 rounded-lg bg-black/60 text-white text-xs font-medium">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        {{ __('app.watch') }}
                                    </div>
                                @endif
                            </div>
                        @elseif($announcement->hasYoutubeVideo())
                            <div class="relative w-full aspect-video overflow-hidden bg-muted">
                                <img src="https://img.youtube.com/vi/{{ $announcement->youtubeVideoId() }}/mqdefault.jpg" alt=""
                                     class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-14 h-14 rounded-full bg-black/60 flex items-center justify-center text-white">
                                        <svg class="w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="p-4 sm:p-5">
                            <h3 class="text-lg sm:text-xl font-bold text-primary group-hover:text-accent transition">
                                {{ $announcementTitle }}
                            </h3>
                            @if($announcementDescription)
                                <p class="mt-2 text-sm text-secondary leading-relaxed line-clamp-2 sm:line-clamp-3">
                                    {{ $announcementDescription }}
                                </p>
                                <span class="mt-3 text-accent font-semibold text-sm inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                                    {{ __('app.read_more') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </a>
                </article>
                @endforeach
            </div>
        </div>

        <div x-show="mode === 'carousel'" x-cloak x-transition.opacity>
            <div class="relative w-full pt-5" :style="'height: ' + containerHeight + 'px'"
                 @mouseenter="pauseAutoplay()"
                 @mouseleave="resumeAutoplay()"
                 @touchstart.passive="onTouchStart($event)"
                 @touchend.passive="onTouchEnd($event)">
                @foreach($announcements as $index => $announcement)
                @php
                    $announcementTitle = $announcement->titleForLocale();
                    $announcementDescription = $announcement->descriptionForLocale();
                    $announcementPhotoUrl = $announcement->photoUrlForLocale();
                @endphp
                <article class="carousel-card absolute top-0 left-0 w-full rounded-2xl shadow-lg border border-border overflow-hidden transition-all duration-500 ease-out"
                         :class="getCardClasses({{ $index }})"
                         :style="getCardStyles({{ $index }})"
                         @click="handleCardClick({{ $index }}, '{{ route('member.announcement.show', $announcement) }}{{ $navToken }}')">
                    <a href="{{ route('member.announcement.show', $announcement) }}{{ $navToken }}"
                       class="block group"
                       @click.prevent>
                        @if($announcementPhotoUrl)
                            <div class="relative w-full aspect-[16/9] overflow-hidden bg-muted">
                                <img src="{{ $announcementPhotoUrl }}" alt=""
                                     class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                                @if($announcement->hasYoutubeVideo())
                                    <div class="absolute bottom-2 right-2 flex items-center gap-1 px-2 py-1 rounded-lg bg-black/60 text-white text-xs font-medium">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                        {{ __('app.watch') }}
                                    </div>
                                @endif
                            </div>
                        @elseif($announcement->hasYoutubeVideo())
                            <div class="relative w-full aspect-video overflow-hidden bg-muted">
                                <img src="https://img.youtube.com/vi/{{ $announcement->youtubeVideoId() }}/mqdefault.jpg" alt=""
                                     class="absolute inset-0 w-full h-full object-cover object-center group-hover:scale-[1.02] transition-transform duration-300"
                                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-14 h-14 rounded-full bg-black/60 flex items-center justify-center text-white">
                                        <svg class="w-7 h-7 ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="p-4 sm:p-5">
                            <h3 class="text-lg sm:text-xl font-bold text-primary group-hover:text-accent transition">
                                {{ $announcementTitle }}
                            </h3>
                            @if($announcementDescription)
                                <p class="mt-2 text-sm text-secondary leading-relaxed line-clamp-2 sm:line-clamp-3">
                                    {{ $announcementDescription }}
                                </p>
                                <span class="mt-3 text-accent font-semibold text-sm inline-flex items-center gap-1 group-hover:gap-2 transition-all">
                                    {{ __('app.read_more') }}
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </a>
                </article>
                @endforeach
            </div>

            @if($announcements->count() > 1)
            <div class="flex items-center justify-center gap-2 mt-5">
                @foreach($announcements as $index => $announcement)
                <button class="carousel-dot transition-all duration-300"
                        :class="current === {{ $index }}
                            ? 'w-6 h-2 rounded-full bg-accent'
                            : 'w-2 h-2 rounded-full bg-border hover:bg-muted-text'"
                        @click="goTo({{ $index }})"
                        aria-label="Go to announcement {{ $index + 1 }}">
                </button>
                @endforeach
            </div>
            @endif
        </div>
    </section>
    @endif

    @if($today)
        {{-- Weekly theme banner --}}
        @if($weekTheme)
        <div class="relative overflow-hidden rounded-3xl shadow-2xl border border-white/10 bg-gradient-to-br from-[#0a6286] via-[#134e5e] to-[#0a6286] cursor-pointer hover:shadow-[0_20px_60px_-12px_rgba(10,98,134,0.5)] transition-all duration-300"
             x-data="{showDetails: false}"
             @click="showDetails = !showDetails">

            {{-- Ambient glows --}}
            <div class="absolute -top-20 -right-20 w-56 h-56 rounded-full bg-easter-gold/15 blur-[70px] pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-48 h-48 rounded-full bg-white/5 blur-[70px] pointer-events-none"></div>

            <div class="relative px-4 py-3 sm:px-6 sm:py-4 text-white">
                {{-- Week label + name --}}
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-0.5 rounded-md bg-easter-gold/20 text-easter-gold font-bold text-xs tracking-wide">{{ __('app.week', ['number' => $weekTheme->week_number]) }}</span>
                    <span class="text-white/40">|</span>
                    <span class="text-sm text-white/80 font-medium">{{ localized($weekTheme, 'name') ?? $weekTheme->name_en ?? $weekTheme->name_geez ?? '-' }}</span>
                </div>
                <h3 class="font-black text-lg text-white drop-shadow-sm">{{ app()->getLocale() === 'am' && $weekTheme->meaning_am ? $weekTheme->meaning_am : $weekTheme->meaning }}</h3>

                {{-- Short description (always visible) --}}
                @php
                    $description = app()->getLocale() === 'am' && $weekTheme->description_am ? $weekTheme->description_am : $weekTheme->description;
                @endphp
                @if($description)
                    <p class="text-sm text-white/75 mt-2 line-clamp-2" x-show="!showDetails">{{ $description }}</p>
                @endif

                {{-- Expanded details (click to toggle) --}}
                <div x-show="showDetails" x-transition class="mt-3 space-y-2">
                    @if($description)
                        <p class="text-sm text-white/85">{{ $description }}</p>
                    @endif

                    @php
                        $summary = app()->getLocale() === 'am' && $weekTheme->summary_am ? $weekTheme->summary_am : $weekTheme->theme_summary;
                    @endphp
                    @if($summary)
                        <p class="text-sm text-white/85 border-t border-white/10 pt-2">{{ $summary }}</p>
                    @endif

                    @if($weekTheme->gospel_reference || $weekTheme->epistles_reference || $weekTheme->liturgy)
                        <div class="text-sm text-white/60 border-t border-white/10 pt-2 space-y-0.5">
                            @if($weekTheme->gospel_reference)
                                <p>{{ __('app.gospel_reference') }}: {{ $weekTheme->gospel_reference }}</p>
                            @endif
                            @if($weekTheme->epistles_reference)
                                <p>{{ __('app.epistles_reference') }}: {{ $weekTheme->epistles_reference }}</p>
                            @endif
                            @if($weekTheme->liturgy)
                                <p class="italic">{{ $weekTheme->liturgy }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Click hint --}}
                <div class="text-xs text-white/50 mt-2 flex items-center gap-1">
                    <span x-show="!showDetails">{{ __('app.tap_for_details') }}</span>
                    <span x-show="showDetails">{{ __('app.tap_to_collapse') }}</span>
                    <svg class="w-4 h-4 transition-transform" :class="showDetails && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </div>
        </div>
        @endif

        {{-- Day content (Bible, Mezmur, checklist, etc.) is shown on member.day view only --}}

    @else
        {{-- No daily content — announcements (if any) are shown at top --}}
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('announcementDisplay', function(total) {
        return {
            total: total,
            current: 0,
            mode: 'carousel',
            autoplayInterval: null,
            autoplayDelay: 5000,
            touchStartX: 0,
            touchStartY: 0,
            containerHeight: 380,
            modeStorageKey: 'announcement-view-mode',

            init: function() {
                var savedMode = null;
                try {
                    savedMode = window.localStorage.getItem(this.modeStorageKey);
                } catch (error) {
                    savedMode = null;
                }

                if (savedMode === 'list' || savedMode === 'carousel') {
                    this.mode = savedMode;
                }

                this.$watch('mode', function(value) {
                    try {
                        window.localStorage.setItem(this.modeStorageKey, value);
                    } catch (error) {}

                    if (value === 'carousel') {
                        this.startAutoplay();
                    } else {
                        this.pauseAutoplay();
                    }
                }.bind(this));

                this.updateHeight();
                if (this.mode === 'carousel') {
                    this.startAutoplay();
                }
            },

            setMode: function(mode) {
                this.mode = mode;
            },

            updateHeight: function() {
                var self = this;
                self.$nextTick(function() {
                    var cards = self.$el.querySelectorAll('.carousel-card');
                    if (cards[self.current]) {
                        var h = cards[self.current].offsetHeight;
                        self.containerHeight = h + 24;
                    }
                });
            },

            startAutoplay: function() {
                if (this.total <= 1 || this.mode !== 'carousel') return;

                var self = this;
                this.pauseAutoplay();
                this.autoplayInterval = setInterval(function() {
                    self.next();
                }, this.autoplayDelay);
            },

            pauseAutoplay: function() {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            },

            resumeAutoplay: function() {
                if (this.mode === 'carousel') {
                    this.startAutoplay();
                }
            },

            next: function() {
                this.current = (this.current + 1) % this.total;
                this.updateHeight();
            },

            prev: function() {
                this.current = (this.current - 1 + this.total) % this.total;
                this.updateHeight();
            },

            goTo: function(index) {
                this.current = index;
                if (this.mode === 'carousel') {
                    this.pauseAutoplay();
                    this.resumeAutoplay();
                }
                this.updateHeight();
            },

            handleCardClick: function(index, url) {
                if (index === this.current) {
                    window.location.href = url;
                    return;
                }
                this.goTo(index);
            },

            onTouchStart: function(event) {
                this.touchStartX = event.touches[0].clientX;
                this.touchStartY = event.touches[0].clientY;
                this.pauseAutoplay();
            },

            onTouchEnd: function(event) {
                var deltaX = event.changedTouches[0].clientX - this.touchStartX;
                var deltaY = event.changedTouches[0].clientY - this.touchStartY;

                if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 40) {
                    if (deltaX < 0) {
                        this.next();
                    } else {
                        this.prev();
                    }
                }

                if (this.mode === 'carousel') {
                    this.resumeAutoplay();
                }
            },

            getOffset: function(index) {
                return index - this.current;
            },

            getCardClasses: function(index) {
                if (index === this.current) {
                    return 'carousel-card--active z-30 bg-card';
                }

                var offset = this.getOffset(index);
                if (offset === 1 || offset === -(this.total - 1)) {
                    return 'carousel-card--next z-20 bg-card';
                }
                if (offset === 2 || offset === -(this.total - 2)) {
                    return 'carousel-card--behind z-10 bg-card';
                }

                return 'carousel-card--hidden z-0 bg-card';
            },

            getCardStyles: function(index) {
                if (index === this.current) {
                    return 'transform: translate(0,0) scale(1) rotate(0deg); opacity: 1; pointer-events: auto;';
                }

                var offset = this.getOffset(index);
                var absOffset = Math.min(Math.abs(offset), this.total - Math.abs(offset));
                if (absOffset === 1) {
                    return 'transform: translate(6px,-8px) scale(0.97) rotate(1.5deg); opacity: 0.5; pointer-events: auto;';
                }
                if (absOffset === 2) {
                    return 'transform: translate(12px,-16px) scale(0.94) rotate(2.5deg); opacity: 0.25; pointer-events: none;';
                }

                return 'transform: translate(16px,-20px) scale(0.91) rotate(3deg); opacity: 0; pointer-events: none;';
            }
        };
    });

    Alpine.data('easterCountdown', function(easterIso, lentStartIso) {
        var target = new Date(easterIso);
        var lentStart = new Date(lentStartIso);
        var totalWindowSeconds = Math.max(1, (target - lentStart) / 1000);
        return {
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0,
            totalSeconds: 1,
            progressPct: 100,
            animatedPct: 0,
            pad: function(n) {
                return String(n).padStart(2, '0');
            },
            animationDone: false,
            animatePercentage: function() {
                var self = this;
                var completedPct = 100 - this.progressPct;
                var duration = 1500;
                var startTime = null;

                function step(timestamp) {
                    if (!startTime) startTime = timestamp;
                    var progress = Math.min((timestamp - startTime) / duration, 1);
                    self.animatedPct = Math.floor(progress * completedPct);
                    if (progress < 1) {
                        window.requestAnimationFrame(step);
                    } else {
                        self.animationDone = true;
                    }
                }
                window.requestAnimationFrame(step);
            },
            tick: function() {
                var now = new Date();
                var diff = Math.max(0, Math.floor((target - now) / 1000));
                this.totalSeconds = diff;
                this.days = Math.floor(diff / 86400);
                this.hours = Math.floor((diff % 86400) / 3600);
                this.minutes = Math.floor((diff % 3600) / 60);
                this.seconds = diff % 60;
                var secondsRemaining = diff;
                this.progressPct = Math.min(100, Math.max(0, (secondsRemaining / totalWindowSeconds) * 100));

                if (this.animationDone) {
                    this.animatedPct = Math.round(100 - this.progressPct);
                }
            },
            init: function() {
                var self = this;
                self.tick();
                self.animatePercentage();
                setInterval(function() { self.tick(); }, 1000);
            }
        };
    });
});

</script>
@endpush


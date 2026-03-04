@extends('layouts.member')

@php
    $locale = app()->getLocale();
    $publicPreview = (bool) ($publicPreview ?? false);
    $backUrl = $backUrl ?? ($publicPreview ? route('home') : route('member.calendar'));
    $weekName = $daily->weeklyTheme ? (localized($daily->weeklyTheme, 'name') ?? $daily->weeklyTheme->name_en ?? '-') : '';
    $dayTitle = localized($daily, 'day_title') ?? __('app.day_x', ['day' => $daily->day_number]);
    $sinksarUrl = $daily->sinksarUrl($locale);
    $sinksarText = $daily->sinksarText($locale);
    $hasSinksarRead = !empty($sinksarText);
    $hasSinksarListen = !empty($sinksarUrl);
    $sinksarImages = $daily->sinksarImages ?? collect();
    $hasSinksarImages = $sinksarImages->isNotEmpty();
    $shareTitle = $weekName ? ($weekName . ' - ' . $dayTitle) : $dayTitle;
    $shareDescription = __('app.share_day_description');
    // Use public share URL so social crawlers can read OG meta tags
    $shareUrl = route('share.day', $daily);
@endphp

@section('title', $shareTitle . ' - ' . __('app.app_name'))

@section('og_title', $shareTitle)
@section('og_description', $shareDescription)

@section('content')
<div x-data="dayPage()" class="px-4 pt-4 space-y-4">

    {{-- "Copied!" toast --}}
    <div x-show="linkCopied"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         x-cloak
         class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] px-4 py-2.5 bg-success text-white text-sm font-semibold rounded-xl shadow-lg flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ __('app.link_copied') }}
    </div>

    {{-- Back + day info + share --}}
    <div data-tour="day-header" class="flex items-center gap-3">
        <a href="{{ $backUrl }}" class="p-2 rounded-lg bg-muted shrink-0">
            <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-lg font-bold text-primary">
                {{ __('app.day_of', ['day' => $daily->day_number, 'total' => 55]) }}
            </h1>
            <p class="text-xs text-muted-text">{{ $daily->date->locale('en')->translatedFormat('l, F j, Y') }}</p>
        </div>
        <div class="relative shrink-0" x-data="{ shareOpen: false }" @click.outside="shareOpen = false">
            <button type="button"
                    @click="shareOpen = !shareOpen"
                    class="p-2.5 rounded-xl bg-accent/10 hover:bg-accent/20 transition touch-manipulation"
                    :aria-label="'{{ __('app.share') }}'">
                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                </svg>
            </button>
            {{-- Share dropdown --}}
            <div x-show="shareOpen" x-transition
                 x-cloak
                 class="absolute right-0 top-full mt-2 w-44 bg-card rounded-xl shadow-xl border border-border z-50 overflow-hidden">
                <button type="button"
                        @click="shareOpen = false; shareDay()"
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-primary hover:bg-muted transition text-left">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    {{ __('app.share') }}
                </button>
                <button type="button"
                        @click="shareOpen = false; copyLink()"
                        class="flex items-center gap-3 w-full px-4 py-3 text-sm text-primary hover:bg-muted transition text-left border-t border-border">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                    </svg>
                    {{ __('app.copy_link_btn') }}
                </button>
            </div>
        </div>
    </div>

    @if(!empty($ethDateInfo['ethiopian_date_formatted'] ?? null))
    <div class="rounded-2xl border border-[#0a6286]/25 bg-gradient-to-r from-[#0a6286]/10 via-white/5 to-[#e2ca18]/10 p-4 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="shrink-0 w-10 h-10 rounded-xl bg-[#0a6286]/12 text-[#0a6286] flex items-center justify-center border border-[#0a6286]/25">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs uppercase tracking-wide text-[#0a6286]/80 font-semibold">{{ __('app.ethiopian_date_label') }}</p>
                <p class="mt-1 text-sm sm:text-base font-bold text-[#0a6286] leading-tight">
                    {{ $ethDateInfo['ethiopian_date_formatted'] }}
                </p>
            </div>
            <span class="shrink-0 text-xs font-bold text-[#e2ca18] bg-[#e2ca18]/10 border border-[#e2ca18]/40 rounded-lg px-2 py-1">E.C.</span>
        </div>
    </div>
    @endif

    {{-- Weekly theme link --}}
    @if($daily->weeklyTheme)
    <a href="{{ route('member.week', $daily->weeklyTheme) }}" class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-card border border-border shadow-sm hover:shadow-md hover:border-accent/30 active:scale-[0.98] transition-all group">
        <div class="shrink-0 w-9 h-9 rounded-xl bg-accent/10 flex items-center justify-center">
            <i class="bi bi-calendar-week text-accent text-sm"></i>
        </div>
        <div class="flex-1 min-w-0">
            <span class="block text-sm font-bold text-primary group-hover:text-accent transition-colors">{{ __('app.week', ['number' => $daily->weeklyTheme->week_number]) }} &mdash; {{ localized($daily->weeklyTheme, 'name') ?? $daily->weeklyTheme->name_en ?? $daily->weeklyTheme->name_geez ?? '-' }}</span>
            <span class="block text-[11px] text-muted-text mt-0.5">{{ __('app.week_tap_to_read') }}</span>
        </div>
        <svg class="w-4 h-4 text-muted-text group-hover:text-accent group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
    @endif

    {{-- Today's celebrations (Ethiopian Synaxarium) --}}
    @if(!empty($ethDateInfo['celebrations']) && $ethDateInfo['celebrations']->isNotEmpty())
        {{-- Main celebration card --}}
        @php $mainCelebration = $ethDateInfo['main_celebration']; @endphp
        @if($mainCelebration)
        <div class="flex items-center gap-3 px-4 py-3 rounded-2xl bg-card border border-border shadow-sm">
            @if($mainCelebration->imageUrl())
                <img src="{{ $mainCelebration->imageUrl() }}" alt="" class="w-10 h-10 rounded-xl object-cover shrink-0">
            @else
                <div class="shrink-0 w-10 h-10 rounded-xl bg-sinksar/10 flex items-center justify-center">
                    <svg class="w-5 h-5 text-sinksar" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
            @endif
            <div class="flex-1 min-w-0">
                <span class="block text-sm font-bold text-primary">{{ localized($mainCelebration, 'celebration') }}</span>
                <span class="block text-[11px] text-muted-text mt-0.5">
                    {{ $ethDateInfo['is_annual_feast'] ? __('app.synaxarium_annual_feast') : __('app.synaxarium_daily_saint') }}
                </span>
                @if($ethDateInfo['is_annual_feast'] && ($mainCelebration->description_en || $mainCelebration->description_am))
                    <p class="text-xs text-secondary mt-1 whitespace-pre-line">{{ localized($mainCelebration, 'description') }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- Secondary saints --}}
        @php $secondarySaints = $ethDateInfo['celebrations']->where('is_main', '!=', true); @endphp
        @if($secondarySaints->isNotEmpty())
        <div class="flex flex-col gap-1.5">
            @foreach($secondarySaints as $saint)
            <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-surface border border-border/50">
                @if($saint->imageUrl())
                    <img src="{{ $saint->imageUrl() }}" alt="" class="w-7 h-7 rounded-lg object-cover shrink-0">
                @else
                    <div class="shrink-0 w-7 h-7 rounded-lg bg-sinksar/5 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-sinksar/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                @endif
                <span class="text-xs font-medium text-secondary truncate">{{ localized($saint, 'celebration') }}</span>
            </div>
            @endforeach
        </div>
        @endif
    @endif

    {{-- Day title --}}
    @if(localized($daily, 'day_title'))
        <h2 class="text-lg font-semibold text-primary">{{ localized($daily, 'day_title') }}</h2>
    @endif

    {{-- Bible Reading --}}
    @if(localized($daily, 'bible_reference'))
    @php
        $bibleText = localized($daily, 'bible_text');
    @endphp
    <div data-tour="day-bible" class="bg-card rounded-2xl p-4 shadow-sm border border-border">
        <h3 class="font-semibold text-sm text-accent mb-1">{{ __('app.bible_reading') }}</h3>
        <p class="font-medium text-primary">{{ localized($daily, 'bible_reference') }}</p>
        @if(localized($daily, 'bible_summary'))
            <p class="text-sm text-muted-text mt-2 leading-relaxed">{{ localized($daily, 'bible_summary') }}</p>
        @endif
        @if($bibleText)
            <div class="mt-2" x-data="{ open: false }">
                <button type="button"
                        @click="open = !open"
                        class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-accent/10 text-accent font-medium text-sm hover:bg-accent/20 transition">
                    <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span x-text="open ? '{{ __('app.close') }}' : '{{ __('app.read') }}'"></span>
                </button>
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-cloak
                     class="mt-3 pt-3 border-t border-border">
                    <p class="text-sm text-secondary leading-relaxed whitespace-pre-wrap">{{ $bibleText }}</p>
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- Mezmur (multiple) — exclusive accordion: when one opens, others collapse --}}
    @if($daily->mezmurs->isNotEmpty())
    <div data-tour="day-mezmur" class="bg-card rounded-2xl p-4 shadow-sm border border-border" x-data="{ openId: null }">
        <h3 class="font-semibold text-sm text-accent-secondary mb-3">{{ __('app.mezmur') }}</h3>
        <div class="space-y-2">
            @foreach($daily->mezmurs as $mezmur)
            <div class="rounded-xl overflow-hidden" :class="openId === {{ $mezmur->id }} ? 'ring-2 ring-accent-secondary' : ''">
                <button type="button"
                        @click="openId = openId === {{ $mezmur->id }} ? null : {{ $mezmur->id }}"
                        class="w-full flex items-center justify-between gap-2 py-3 px-4 rounded-xl bg-accent-secondary/10 text-left hover:bg-accent-secondary/20 transition">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openId === {{ $mezmur->id }} ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span class="font-medium text-primary truncate">{{ localized($mezmur, 'title') }}</span>
                    </div>
                    <span class="text-sm text-muted-text shrink-0">{{ __('app.listen') }}</span>
                </button>
                <div x-show="openId === {{ $mezmur->id }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-cloak
                     class="border-t border-accent-secondary/20 bg-muted/30">
                    <div class="p-3 space-y-2">
                        @if(localized($mezmur, 'description'))
                            <p class="text-sm text-muted-text leading-relaxed">{{ localized($mezmur, 'description') }}</p>
                        @endif
                @php
                    $mezmurUrl = $mezmur->mediaUrl($locale);
                @endphp
                @if($mezmurUrl)
                            <x-embedded-media :url="$mezmurUrl" play-label="{{ __('app.listen') }}" :open-label="__('app.open_in_youtube')" />
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Sinksar (Synaxarium) — Read / Listen toggle with immersive reader --}}
    @if(localized($daily, 'sinksar_title'))
    <div data-tour="day-sinksar"
         class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
         x-data="{
            mode: '{{ $hasSinksarRead ? 'read' : ($hasSinksarListen ? 'listen' : 'read') }}',
            fontSize: parseInt(localStorage.getItem('sinksarFontSize') || '16'),
            readerTheme: localStorage.getItem('sinksarReaderTheme') || 'default',
            readerFont: localStorage.getItem('sinksarReaderFont') || 'default',
            fullscreen: false,
            readOpen: false,
            themeMenuOpen: false,
            fontMenuOpen: false,
            inlineFontOpen: false,
            _shelfLock: false,
            toggleThemeMenu() {
                if (this._shelfLock) return;
                this._shelfLock = true;
                this.themeMenuOpen = !this.themeMenuOpen;
                this.fontMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            toggleFontMenu() {
                if (this._shelfLock) return;
                this._shelfLock = true;
                this.fontMenuOpen = !this.fontMenuOpen;
                this.themeMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            closeFontMenu() {
                this._shelfLock = true;
                this.fontMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            closeThemeMenu() {
                this._shelfLock = true;
                this.themeMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            fontFamily() {
                if (this.readerFont === 'benaiah') return 'Benaiah,sans-serif';
                if (this.readerFont === 'kiros') return 'Kiros,sans-serif';
                if (this.readerFont === 'handwriting') return 'Handwriting,sans-serif';
                return 'inherit';
            },
            setFontSize(size) {
                this.fontSize = Math.min(28, Math.max(12, size));
                localStorage.setItem('sinksarFontSize', this.fontSize);
            },
            setReaderTheme(theme) {
                this.readerTheme = theme;
                localStorage.setItem('sinksarReaderTheme', theme);
                this._shelfLock = true;
                this.themeMenuOpen = false;
                this.fontMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            setReaderFont(font) {
                this.readerFont = font;
                localStorage.setItem('sinksarReaderFont', font);
                this._shelfLock = true;
                this.fontMenuOpen = false;
                this.themeMenuOpen = false;
                setTimeout(() => { this._shelfLock = false; }, 350);
            },
            openFullscreen() {
                this.fullscreen = true;
                document.body.style.overflow = 'hidden';
                const nav = document.querySelector('nav.fixed.bottom-0');
                if (nav) nav.style.display = 'none';
            },
            closeFullscreen() {
                this.fullscreen = false;
                this.themeMenuOpen = false;
                this.fontMenuOpen = false;
                document.body.style.overflow = '';
                const nav = document.querySelector('nav.fixed.bottom-0');
                if (nav) nav.style.display = '';
            }
         }"
         @keydown.escape.window="if(fullscreen) closeFullscreen()">

        {{-- Header --}}
        <div class="px-4 pt-4 pb-3">
            <h3 class="font-semibold text-sm text-sinksar mb-1">{{ __('app.sinksar') }}</h3>
            <p class="font-medium text-primary">{{ localized($daily, 'sinksar_title') }}</p>
            @if(localized($daily, 'sinksar_description'))
                <p class="text-sm text-muted-text mt-1.5 leading-relaxed whitespace-pre-line">{{ localized($daily, 'sinksar_description') }}</p>
            @endif
        </div>

        {{-- Saint images carousel --}}
        @if($hasSinksarImages)
        <div class="px-4 pb-3"
             x-data="{
                imgCurrent: 0,
                imgTotal: {{ $sinksarImages->count() }},
                _touchX: 0, _touchY: 0,
                _autoTimer: null,
                imgNext() { this.imgCurrent = (this.imgCurrent + 1) % this.imgTotal; },
                imgPrev() { this.imgCurrent = (this.imgCurrent - 1 + this.imgTotal) % this.imgTotal; },
                startAuto() { this._autoTimer = setInterval(() => this.imgNext(), 5000); },
                stopAuto() { if (this._autoTimer) { clearInterval(this._autoTimer); this._autoTimer = null; } },
                imgTouchStart(e) { this.stopAuto(); this._touchX = e.touches[0].clientX; this._touchY = e.touches[0].clientY; },
                imgTouchEnd(e) {
                    var dx = e.changedTouches[0].clientX - this._touchX;
                    var dy = e.changedTouches[0].clientY - this._touchY;
                    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) { dx < 0 ? this.imgNext() : this.imgPrev(); }
                },
                init() { if (this.imgTotal > 1) this.startAuto(); }
             }">

            <div class="relative rounded-xl overflow-hidden"
                 style="aspect-ratio:4/3;background:#1a1a2e"
                 @touchstart.passive="imgTouchStart($event)"
                 @touchend.passive="imgTouchEnd($event)">
                @foreach($sinksarImages as $idx => $img)
                <div class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                     :style="imgCurrent === {{ $idx }}
                         ? 'opacity:1;transform:translateX(0);z-index:10'
                         : {{ $idx }} > imgCurrent
                             ? 'opacity:0;transform:translateX(100%);z-index:1;pointer-events:none'
                             : 'opacity:0;transform:translateX(-100%);z-index:1;pointer-events:none'">
                    <img src="{{ $img->imageUrl() }}"
                         alt="{{ localized($img, 'caption') ?? '' }}"
                         class="w-full h-full object-contain"
                         loading="{{ $idx === 0 ? 'eager' : 'lazy' }}">
                    @if(localized($img, 'caption'))
                    <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent px-3 py-2">
                        <p class="text-white text-xs font-medium">{{ localized($img, 'caption') }}</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            @if($sinksarImages->count() > 1)
            <div class="flex items-center justify-center gap-2 mt-2">
                <button type="button" @click="stopAuto(); imgPrev()" class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-text hover:text-primary transition touch-manipulation">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="flex items-center gap-1">
                    @foreach($sinksarImages as $idx => $img)
                    <button type="button" @click="stopAuto(); imgCurrent = {{ $idx }}"
                            class="transition-all duration-300 touch-manipulation"
                            :class="imgCurrent === {{ $idx }} ? 'w-4 h-1.5 rounded-full bg-sinksar' : 'w-1.5 h-1.5 rounded-full bg-border hover:bg-muted-text'">
                    </button>
                    @endforeach
                </div>
                <button type="button" @click="stopAuto(); imgNext()" class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-text hover:text-primary transition touch-manipulation">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            @endif
        </div>
        @endif

        @if($hasSinksarRead || $hasSinksarListen)
        {{-- Mode toggle --}}
        @if($hasSinksarRead && $hasSinksarListen)
        <div class="px-4 pb-3">
            <div class="flex bg-muted rounded-xl p-1 gap-1">
                <button type="button" @click="mode = 'read'; readOpen = false"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                        :class="mode === 'read' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ __('app.reading_mode') }}
                </button>
                <button type="button" @click="mode = 'listen'; readOpen = false"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                        :class="mode === 'listen' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    </svg>
                    {{ __('app.listening_mode') }}
                </button>
            </div>
        </div>
        @endif

        {{-- Read mode --}}
        @if($hasSinksarRead)
        <div x-show="mode === 'read'" x-transition.opacity class="px-4 pb-4">
            <button type="button"
                    @click="readOpen = !readOpen"
                    class="w-full flex items-center justify-between gap-2 py-2.5 px-3 rounded-xl bg-muted/70 hover:bg-muted transition mb-3">
                <div class="flex items-center gap-1.5 min-w-0">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="readOpen ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <div class="min-w-0">
                        <span class="text-sm font-semibold text-primary">{{ __('app.read') }}</span>
                        <p x-show="!readOpen" class="text-[11px] text-muted-text mt-0.5">
                            {{ app()->getLocale() === 'am' ? 'ለማንበብ እዚህ ላይ ይንኩ' : 'Click here to read' }}
                        </p>
                    </div>
                </div>
                <span x-show="readOpen" class="text-[11px] font-semibold text-muted-text uppercase tracking-wider">{{ __('app.close') }}</span>
            </button>

            <div x-show="readOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="space-y-3">
                {{-- Accessibility toolbar --}}
                <div class="flex items-center justify-between gap-2 py-2 px-3 rounded-xl bg-muted/60">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="setFontSize(fontSize - 2)"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation"
                                :disabled="fontSize <= 12"
                                :class="fontSize <= 12 && 'opacity-30 cursor-not-allowed'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                        </button>
                        <span class="text-xs font-bold text-primary tabular-nums w-6 text-center" x-text="fontSize"></span>
                        <button type="button" @click="setFontSize(fontSize + 2)"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation"
                                :disabled="fontSize >= 28"
                                :class="fontSize >= 28 && 'opacity-30 cursor-not-allowed'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        {{-- Font dropdown --}}
                        <div class="relative" @click.outside="inlineFontOpen = false">
                            <button type="button"
                                    @click="inlineFontOpen = !inlineFontOpen"
                                    class="h-7 px-2.5 rounded-lg border transition touch-manipulation flex items-center gap-1"
                                    :class="inlineFontOpen ? 'bg-accent border-accent text-on-accent' : 'bg-card border-border text-secondary hover:bg-muted'">
                                <span class="text-[13px] font-bold"
                                      :style="readerFont === 'benaiah' ? 'font-family:Benaiah,sans-serif' : readerFont === 'kiros' ? 'font-family:Kiros,sans-serif' : readerFont === 'handwriting' ? 'font-family:Handwriting,sans-serif' : ''">ሀ</span>
                                <svg class="w-2.5 h-2.5 opacity-60 transition-transform" :class="inlineFontOpen && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            {{-- Dropdown panel --}}
                            <div x-show="inlineFontOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 x-cloak
                                 class="absolute right-0 top-full mt-1.5 w-52 bg-card border border-border rounded-xl shadow-xl overflow-hidden z-50"
                                 style="display:none">
                                @foreach([['default','Default','inherit','ሀ'],['benaiah','Benaiah','Benaiah,sans-serif','ሀ'],['kiros','Kiros','Kiros,sans-serif','ሀ'],['handwriting','Handwriting','Handwriting,sans-serif','ሀ']] as [$val,$label,$ff,$glyph])
                                <button type="button"
                                        @click="inlineFontOpen = false; setReaderFont('{{ $val }}')"
                                        class="w-full px-4 py-3 text-left transition touch-manipulation flex items-center justify-between gap-3 border-b border-border last:border-0"
                                        :class="readerFont === '{{ $val }}' ? 'bg-accent/10' : 'hover:bg-muted'">
                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider mb-0.5"
                                           :class="readerFont === '{{ $val }}' ? 'text-accent' : 'text-muted-text'">{{ $label }}</p>
                                        <p class="text-sm truncate" style="font-family:{{ $ff }}"
                                           :class="readerFont === '{{ $val }}' ? 'text-primary' : 'text-secondary'">መልካም ንባብ</p>
                                        <p class="text-[11px] truncate" style="font-family:{{ $ff }}"
                                           :class="readerFont === '{{ $val }}' ? 'text-accent' : 'text-muted-text'">Happy Reading</p>
                                    </div>
                                    <svg x-show="readerFont === '{{ $val }}'" class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <button type="button" @click="openFullscreen()"
                                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-card border border-border text-secondary hover:bg-muted transition touch-manipulation">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                            </svg>
                            <span class="text-[10px] font-semibold uppercase tracking-wider hidden sm:inline">{{ __('app.fullscreen') }}</span>
                        </button>
                    </div>
                </div>

                {{-- Inline reader --}}
                <div class="rounded-xl border border-border bg-surface/50 p-4"
                     :style="'font-size:' + fontSize + 'px;line-height:' + (fontSize < 20 ? '1.8' : '1.7') + ';max-height:60vh;overflow-y:scroll;-webkit-overflow-scrolling:touch;font-family:' + fontFamily()">
                    <div class="text-secondary whitespace-pre-line break-words">{{ $sinksarText }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Listen mode --}}
        @if($hasSinksarListen)
        <div x-show="mode === 'listen'" x-transition.opacity class="px-4 pb-4">
            <x-embedded-media :url="$sinksarUrl" play-label="{{ __('app.listen_synaxarium') }}" :open-label="__('app.open_in_youtube')" />
        </div>
        @endif

        {{-- Fullscreen reader overlay --}}
        @if($hasSinksarRead)
        <template x-teleport="body">
            <div x-show="fullscreen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak
                 class="fixed inset-0 z-[9999] flex flex-col"
                 :class="{ 'bg-surface text-secondary': readerTheme === 'default' }"
                 :style="readerTheme === 'sepia' ? 'background-color:#f4ecd8;color:#5b4636' : readerTheme === 'dark' ? 'background-color:#1a1a2e;color:#e0e0e0' : ''">

                {{-- Fullscreen top bar --}}
                <div class="flex items-center justify-between gap-3 px-4 py-3 border-b shrink-0"
                     :class="{ 'bg-card border-border': readerTheme === 'default' }"
                     :style="readerTheme === 'sepia' ? 'background-color:#ede3cc;border-color:#d4c5a9' : readerTheme === 'dark' ? 'background-color:#16162a;border-color:#2a2a4a' : ''">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button type="button" @click="closeFullscreen()"
                                class="p-2 rounded-lg transition touch-manipulation shrink-0"
                                :class="{ 'bg-muted hover:bg-border text-primary': readerTheme === 'default' }"
                                :style="readerTheme === 'sepia' ? 'background-color:#e8dcc6;color:#5b4636' : readerTheme === 'dark' ? 'background-color:#2a2a4a;color:#e0e0e0' : ''">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate"
                               :class="{ 'text-primary': readerTheme === 'default' }"
                               :style="readerTheme === 'sepia' ? 'color:#3e2c1c' : readerTheme === 'dark' ? 'color:#f0f0f0' : ''">{{ localized($daily, 'sinksar_title') }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider"
                               :class="{ 'text-muted-text': readerTheme === 'default' }"
                               :style="readerTheme === 'sepia' ? 'color:#8b7355' : readerTheme === 'dark' ? 'color:#8888aa' : ''">{{ __('app.sinksar') }}</p>
                        </div>
                    </div>
                </div>

                {{-- Fullscreen content --}}
                <div class="flex-1 overflow-y-auto overscroll-contain px-5 py-6 pb-6 sm:px-8 sm:py-8">
                    {{-- Saint images in fullscreen reader --}}
                    @if($hasSinksarImages)
                    <div class="max-w-2xl mx-auto mb-6"
                         x-data="{
                            fsCurrent: 0, fsTotal: {{ $sinksarImages->count() }},
                            _fsTX: 0, _fsTY: 0,
                            _fsAutoTimer: null,
                            fsNext() { this.fsCurrent = (this.fsCurrent + 1) % this.fsTotal; },
                            fsPrev() { this.fsCurrent = (this.fsCurrent - 1 + this.fsTotal) % this.fsTotal; },
                            fsStartAuto() { this._fsAutoTimer = setInterval(() => this.fsNext(), 5000); },
                            fsStopAuto() { if (this._fsAutoTimer) { clearInterval(this._fsAutoTimer); this._fsAutoTimer = null; } },
                            fsTouchStart(e) { this.fsStopAuto(); this._fsTX = e.touches[0].clientX; this._fsTY = e.touches[0].clientY; },
                            fsTouchEnd(e) {
                                var dx = e.changedTouches[0].clientX - this._fsTX;
                                var dy = e.changedTouches[0].clientY - this._fsTY;
                                if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) { dx < 0 ? this.fsNext() : this.fsPrev(); }
                            },
                            init() { if (this.fsTotal > 1) this.fsStartAuto(); }
                         }">
                        <div class="relative rounded-xl overflow-hidden"
                             style="aspect-ratio:4/3;background:#1a1a2e"
                             @touchstart.passive="fsTouchStart($event)"
                             @touchend.passive="fsTouchEnd($event)">
                            @foreach($sinksarImages as $idx => $img)
                            <div class="absolute inset-0 flex items-center justify-center transition-all duration-500 ease-out"
                                 :style="fsCurrent === {{ $idx }}
                                     ? 'opacity:1;transform:translateX(0);z-index:10'
                                     : {{ $idx }} > fsCurrent
                                         ? 'opacity:0;transform:translateX(100%);z-index:1'
                                         : 'opacity:0;transform:translateX(-100%);z-index:1'">
                                <img src="{{ $img->imageUrl() }}" alt="{{ localized($img, 'caption') ?? '' }}"
                                     class="w-full h-full object-contain" loading="lazy">
                                @if(localized($img, 'caption'))
                                <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/70 to-transparent px-3 py-2">
                                    <p class="text-white text-xs font-medium">{{ localized($img, 'caption') }}</p>
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                        @if($sinksarImages->count() > 1)
                        <div class="flex items-center justify-center gap-1.5 mt-2">
                            @foreach($sinksarImages as $idx => $img)
                            <button type="button" @click="fsStopAuto(); fsCurrent = {{ $idx }}"
                                    class="transition-all duration-300 touch-manipulation"
                                    :class="fsCurrent === {{ $idx }} ? 'w-4 h-1.5 rounded-full bg-sinksar' : 'w-1.5 h-1.5 rounded-full bg-white/30 hover:bg-white/50'"
                                    :style="fsCurrent === {{ $idx }} && readerTheme === 'default' ? 'background-color:var(--color-sinksar,#9333ea)' : ''">
                            </button>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif

                    <div class="max-w-2xl mx-auto whitespace-pre-line break-words"
                         :style="'font-size:' + fontSize + 'px;line-height:' + (fontSize < 20 ? '1.85' : '1.75') + ';font-family:' + fontFamily()">
                        {{ $sinksarText }}
                    </div>
                </div>

                {{-- Fixed bottom area: overlays + toolbar --}}
                <div class="shrink-0 relative">
                    {{-- Font shelf — absolute overlay, does NOT push toolbar --}}
                    <div x-show="fontMenuOpen" @click.outside="fontMenuOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-cloak
                         class="absolute bottom-full left-0 right-0 border-t px-4 py-3"
                         :class="{ 'bg-card border-border': readerTheme === 'default' }"
                         :style="readerTheme === 'sepia' ? 'background-color:#e8dcc6;border-color:#d4c5a9' : readerTheme === 'dark' ? 'background-color:#12122a;border-color:#2a2a4a' : ''">
                        <div class="flex items-center justify-center gap-4 max-w-xs mx-auto">
                            @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Writing','Handwriting,sans-serif']] as [$fVal,$fLabel,$fFam])
                            <button type="button" @click="setReaderFont('{{ $fVal }}')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold transition-all"
                                      style="font-family:{{ $fFam }}"
                                      :style="readerFont === '{{ $fVal }}' ? 'border:3px solid var(--color-accent);transform:scale(1.1);box-shadow:0 0 0 4px rgba(10,98,134,0.2)' : 'border:2px solid ' + (readerTheme === 'dark' ? '#4a4a6a' : readerTheme === 'sepia' ? '#c4a87c' : '#d1d5db') + ';background:' + (readerTheme === 'dark' ? '#1a1a2e' : readerTheme === 'sepia' ? '#f4ecd8' : '#fff')">
                                    ሀ
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerFont === '{{ $fVal }}' ? 'color:var(--color-accent)' : readerTheme === 'sepia' ? 'color:#5b4636' : readerTheme === 'dark' ? 'color:#8888aa' : 'color:#6b7280'">{{ $fLabel }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Theme shelf — absolute overlay, does NOT push toolbar --}}
                    <div x-show="themeMenuOpen" @click.outside="themeMenuOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-cloak
                         class="absolute bottom-full left-0 right-0 border-t px-4 py-3"
                         :class="{ 'bg-card border-border': readerTheme === 'default' }"
                         :style="readerTheme === 'sepia' ? 'background-color:#e8dcc6;border-color:#d4c5a9' : readerTheme === 'dark' ? 'background-color:#12122a;border-color:#2a2a4a' : ''">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            <button type="button" @click="setReaderTheme('default')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center transition-all"
                                      :style="'border:3px solid ' + (readerTheme === 'default' ? 'var(--color-accent)' : '#d1d5db') + (readerTheme === 'default' ? ';box-shadow:0 0 0 4px rgba(10,98,134,0.2);transform:scale(1.1)' : '')">
                                    <span class="text-xs font-bold text-gray-700">A</span>
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerTheme === 'default' ? 'color:var(--color-accent)' : readerTheme === 'sepia' ? 'color:#5b4636' : 'color:#8888aa'">{{ __('app.reader_theme_default') }}</span>
                            </button>
                            <button type="button" @click="setReaderTheme('sepia')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                                      :style="'background-color:#f4ecd8;border:3px solid ' + (readerTheme === 'sepia' ? '#8b5e3c' : '#c4a87c') + (readerTheme === 'sepia' ? ';box-shadow:0 0 0 4px rgba(139,94,60,0.3);transform:scale(1.1)' : '')">
                                    <span class="text-xs font-bold" style="color:#5b4636">A</span>
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerTheme === 'sepia' ? 'color:#8b5e3c' : readerTheme === 'dark' ? 'color:#8888aa' : ''">{{ __('app.reader_theme_sepia') }}</span>
                            </button>
                            <button type="button" @click="setReaderTheme('dark')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                                      :style="'background-color:#1a1a2e;border:3px solid ' + (readerTheme === 'dark' ? '#7b9fff' : '#4a4a6a') + (readerTheme === 'dark' ? ';box-shadow:0 0 0 4px rgba(123,159,255,0.3);transform:scale(1.1)' : '')">
                                    <span class="text-xs font-bold" style="color:#e0e0e0">A</span>
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerTheme === 'dark' ? 'color:#7b9fff' : readerTheme === 'sepia' ? 'color:#8b7355' : ''">{{ __('app.reader_theme_dark') }}</span>
                            </button>
                        </div>
                    </div>

                    {{-- Bottom toolbar — always stays in place --}}
                    <div class="border-t safe-area-bottom"
                         :class="{ 'bg-card border-border': readerTheme === 'default' }"
                         :style="readerTheme === 'sepia' ? 'background-color:#ede3cc;border-color:#d4c5a9' : readerTheme === 'dark' ? 'background-color:#16162a;border-color:#2a2a4a' : ''">
                        <div class="flex items-center justify-around h-16 max-w-lg mx-auto px-2">
                            {{-- Close --}}
                            <button type="button" @click="closeFullscreen()"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :class="{ 'text-accent hover:bg-accent/10': readerTheme === 'default' }"
                                    :style="readerTheme === 'sepia' ? 'color:#8b5e3c' : readerTheme === 'dark' ? 'color:#7b9fff' : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.close') }}</span>
                            </button>

                            {{-- Font decrease --}}
                            <button type="button" @click="setFontSize(fontSize - 2)"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :disabled="fontSize <= 12"
                                    :class="fontSize <= 12 ? 'opacity-30 cursor-not-allowed' : { 'text-secondary hover:bg-muted': readerTheme === 'default' }"
                                    :style="fontSize > 12 ? (readerTheme === 'sepia' ? 'color:#5b4636' : readerTheme === 'dark' ? 'color:#c0c0d0' : '') : ''">
                                <span class="text-base font-bold leading-none">A</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M5 12h14"/></svg>
                            </button>

                            {{-- Font size indicator --}}
                            <div class="flex flex-col items-center gap-0.5 px-1">
                                <span class="text-sm font-bold tabular-nums" x-text="fontSize"
                                      :class="{ 'text-primary': readerTheme === 'default' }"
                                      :style="readerTheme === 'sepia' ? 'color:#3e2c1c' : readerTheme === 'dark' ? 'color:#f0f0f0' : ''"></span>
                                <span class="text-[8px] font-semibold uppercase tracking-wider"
                                      :class="{ 'text-muted-text': readerTheme === 'default' }"
                                      :style="readerTheme === 'sepia' ? 'color:#8b7355' : readerTheme === 'dark' ? 'color:#8888aa' : ''">{{ __('app.font_size') }}</span>
                            </div>

                            {{-- Font increase --}}
                            <button type="button" @click="setFontSize(fontSize + 2)"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :disabled="fontSize >= 28"
                                    :class="fontSize >= 28 ? 'opacity-30 cursor-not-allowed' : { 'text-secondary hover:bg-muted': readerTheme === 'default' }"
                                    :style="fontSize < 28 ? (readerTheme === 'sepia' ? 'color:#5b4636' : readerTheme === 'dark' ? 'color:#c0c0d0' : '') : ''">
                                <span class="text-xl font-bold leading-none">A</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/></svg>
                            </button>

                            {{-- Theme toggle --}}
                            <button type="button" @click="themeMenuOpen = !themeMenuOpen; fontMenuOpen = false"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :class="{
                                        'text-secondary hover:bg-muted': readerTheme === 'default' && !themeMenuOpen,
                                        'text-accent bg-accent/10': readerTheme === 'default' && themeMenuOpen
                                    }"
                                    :style="readerTheme === 'sepia' ? (themeMenuOpen ? 'color:#8b5e3c;background-color:#d4c5a9' : 'color:#5b4636') : readerTheme === 'dark' ? (themeMenuOpen ? 'color:#7b9fff;background-color:#2a2a4a' : 'color:#c0c0d0') : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                                <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.reader_theme') }}</span>
                            </button>

                            {{-- Font toggle --}}
                            <button type="button" @click="fontMenuOpen = !fontMenuOpen; themeMenuOpen = false"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :class="{
                                        'text-secondary hover:bg-muted': readerTheme === 'default' && !fontMenuOpen,
                                        'text-accent bg-accent/10': readerTheme === 'default' && fontMenuOpen
                                    }"
                                    :style="readerTheme === 'sepia' ? (fontMenuOpen ? 'color:#8b5e3c;background-color:#d4c5a9' : 'color:#5b4636') : readerTheme === 'dark' ? (fontMenuOpen ? 'color:#7b9fff;background-color:#2a2a4a' : 'color:#c0c0d0') : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                                <span class="text-[9px] font-semibold uppercase tracking-wider">Font</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        @endif

        @endif
    </div>
    @endif

    {{-- Spiritual books (multiple per day) --}}
    @if($daily->books && $daily->books->isNotEmpty())
    <div data-tour="day-book" class="space-y-3">
        <h3 class="font-semibold text-sm text-book">{{ __('app.spiritual_book') }}</h3>
        @foreach($daily->books as $book)
            @php
                $bookUrl = $book->mediaUrl($locale);
                $bookIsPdf = $bookUrl ? $book->isPdf($locale) : false;
                $bookTitle = (string) localized($book, 'title');
            @endphp
            @if(localized($book, 'title'))
                <div class="bg-card rounded-2xl p-4 shadow-sm border border-border">
                    <p class="font-medium text-primary">{{ $bookTitle }}</p>
                    @if(localized($book, 'description'))
                        <p class="text-sm text-muted-text mt-1 leading-relaxed">{{ localized($book, 'description') }}</p>
                    @endif
                    @if($bookUrl)
                        @if($bookIsPdf)
                            <div x-data="{ readerOpen: window.matchMedia('(min-width: 768px)').matches }"
                                 @resize.window="readerOpen = window.matchMedia('(min-width: 768px)').matches"
                                 class="mt-2 space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        @click="readerOpen = !readerOpen"
                                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-book/10 hover:bg-book/20 text-book px-3 py-2 text-sm font-medium transition touch-manipulation"
                                    >
                                        <svg x-show="!readerOpen" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 3h11l5 5v13a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2zm0 0v18M9 3v6h6M9 9l3 3m0 0l3-3m-3 3V9"/>
                                        </svg>
                                        <svg x-show="readerOpen" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7v10a2 2 0 01-2 2H7m6-8V5m0 0l3 3m-3-3L7 5m10 6H7"/>
                                        </svg>
                                        <span x-text="readerOpen ? '{{ __('app.close') }}' : '{{ __('app.read_now') }}'"></span>
                                    </button>
                                    <a
                                        href="{{ $bookUrl }}"
                                        download
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-muted hover:bg-border text-secondary px-3 py-2 text-sm font-medium transition touch-manipulation"
                                    >
                                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v7m0 0l-3-3m3 3l3-3M5 19h14M9 9V4h6v5"/>
                                        </svg>
                                        {{ __('app.get_book') }} &rarr;
                                    </a>
                                </div>
                                <div
                                    x-show="readerOpen"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                >
                                    <div class="mt-2 rounded-xl border border-border overflow-hidden bg-surface/20">
                                        <iframe
                                            src="{{ $bookUrl }}#toolbar=1"
                                            title="{{ $bookTitle }}"
                                            class="w-full h-[60vh] min-h-[260px]"
                                            loading="lazy"
                                        ></iframe>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ $bookUrl }}" target="_blank" rel="noopener" class="text-sm text-accent font-medium mt-2 inline-block">
                                <span class="inline-flex items-center gap-1.5">
                                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-width="2" d="M18 13v6H6V7h6M15 3h6v6m0-6L10 14"/>
                                    </svg>
                                    <span>{{ __('app.open_externally') }}</span>
                                </span>
                                &rarr;
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Reflection --}}
    @if(localized($daily, 'reflection'))
    <div class="bg-reflection-bg border border-reflection-border rounded-2xl p-4">
        <h3 class="font-semibold text-sm text-primary mb-2">{{ __('app.reflection') }}</h3>
        <p class="text-sm text-secondary leading-relaxed">{{ localized($daily, 'reflection') }}</p>
    </div>
    @endif

    {{-- References (know more) — accordion with name + Read more per link --}}
    @if($daily->references->isNotEmpty())
    <div data-tour="day-references" class="bg-card rounded-2xl p-4 shadow-sm border border-border" x-data="{ open: false }">
        <button type="button"
                @click="open = !open"
                class="w-full flex items-center justify-between gap-2 py-2 text-left">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <h3 class="font-semibold text-sm text-primary">{{ __('app.references') }}</h3>
            </div>
            <span class="text-sm text-muted-text" x-text="open ? '{{ __('app.close') }}' : ''"></span>
        </button>
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             class="mt-3 pt-3 border-t border-border space-y-2">
            @foreach($daily->references as $ref)
                @php
                    $refUrl = $ref->mediaUrl($locale);
                @endphp
                @if ($refUrl)
                @php
                $refType = $ref->type ?? 'website';
                $btnLabel = match($refType) {
                    'video' => __('app.view_video'),
                    'file' => __('app.view_file'),
                    default => __('app.read_more'),
                };
            @endphp
            <a href="{{ $refUrl }}" target="_blank" rel="noopener"
               class="flex items-center justify-between gap-2 p-3 rounded-xl bg-muted hover:bg-border transition">
                <span class="text-sm font-medium text-primary">{{ localized($ref, 'name') }}</span>
                <span class="shrink-0 px-3 py-1 bg-accent text-on-accent rounded-lg text-xs font-medium">{{ $btnLabel }}</span>
            </a>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Bottom share prompt (appears when user scrolls near bottom) --}}
    <div x-ref="bottomSentinel" class="h-0"></div>
    <div x-show="showSharePrompt && !sharePromptDismissed"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         x-cloak
         class="flex items-center justify-between gap-3 p-4 rounded-2xl bg-accent/10 border border-accent/20">
        <p class="text-sm font-medium text-primary flex-1 min-w-0">{{ __('app.share_prompt_message') }}</p>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
                    @click="shareDay()"
                    class="px-4 py-2 bg-accent text-on-accent rounded-xl text-sm font-semibold hover:bg-accent-hover transition touch-manipulation">
                {{ __('app.share_btn') }}
            </button>
            <button type="button"
                    @click="copyLink()"
                    class="p-2 rounded-xl bg-accent/10 hover:bg-accent/20 transition touch-manipulation"
                    :aria-label="'{{ __('app.copy_link_btn') }}'">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                </svg>
            </button>
            <button type="button"
                    @click="sharePromptDismissed = true"
                    class="p-1.5 rounded-lg hover:bg-muted transition touch-manipulation"
                    aria-label="{{ __('app.close') }}">
                <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    {{-- Checklist (show when there are activities, custom activities, or member can add) --}}
    @php
        $customChecklistCompleted = ($customChecklist ?? collect())->mapWithKeys(fn ($c) => [(string) $c->member_custom_activity_id => $c->completed])->all();
    @endphp
    @if(!$publicPreview && ($activities->isNotEmpty() || ($customActivities ?? collect())->isNotEmpty() || $member))
    <div data-tour="day-checklist" class="rounded-2xl p-5 shadow-sm border-2 transition-all duration-300"
         x-data="{
             allDone: false,
             checkAllDone() {
                 this.$nextTick(() => {
                     const cbs = this.$refs?.checklistItems?.querySelectorAll('input[type=checkbox]');
                     this.allDone = cbs?.length > 0 && Array.from(cbs).every(c => c.checked);
                 });
             }
         }"
         x-init="$nextTick(() => checkAllDone())"
         @checklist-updated="checkAllDone()"
         :class="allDone ? 'bg-success-bg/30 border-success ring-2 ring-success/50' : 'bg-card border-border'">
        <div class="flex items-center justify-between gap-3 mb-4">
            <h3 class="font-bold text-sm text-primary">{{ __('app.checklist') }}</h3>
            <p x-show="allDone" x-transition class="text-sm font-bold text-success flex items-center gap-1.5">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                {{ __('app.well_done') }}
            </p>
        </div>
        <div class="space-y-2.5" x-ref="checklistItems">
            @foreach($activities as $activity)
                <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all duration-200"
                       :class="checked ? 'bg-success-bg/50 border border-success/30' : 'bg-muted hover:bg-border border border-transparent'"
                       x-data="{ checked: {{ isset($checklist[$activity->id]) && $checklist[$activity->id]->completed ? 'true' : 'false' }} }">
                    <input type="checkbox" x-model="checked"
                           @change="toggleChecklist({{ $daily->id }}, {{ $activity->id }}, checked); $dispatch('checklist-updated')"
                           class="w-5 h-5 rounded-md border-2 border-border accent-success focus:ring-2 focus:ring-success focus:ring-offset-0">
                    <span class="text-sm font-semibold" :class="checked ? 'line-through text-muted-text' : 'text-primary'">
                        {{ localized($activity, 'name') }}
                    </span>
                </label>
            @endforeach
            <template x-for="activity in customActivities" :key="activity.id">
                <label class="flex items-center gap-3 p-3.5 rounded-xl cursor-pointer transition-all duration-200"
                       :class="customChecklistCompleted[activity.id] ? 'bg-success-bg/50 border border-success/30' : 'bg-muted hover:bg-border border border-transparent'">
                    <input type="checkbox" :checked="customChecklistCompleted[activity.id]"
                           @change="toggleCustomChecklist({{ $daily->id }}, activity.id, $event.target.checked); customChecklistCompleted[activity.id] = $event.target.checked; $dispatch('checklist-updated')"
                           class="w-5 h-5 rounded-md border-2 border-border accent-success focus:ring-2 focus:ring-success focus:ring-offset-0">
                    <span class="text-sm font-semibold block min-w-0 truncate" :class="customChecklistCompleted[activity.id] ? 'line-through text-muted-text' : 'text-primary'" x-text="activity.name"></span>
                </label>
            </template>
        </div>
        @if($member)
        <div data-tour="day-custom" class="mt-4 pt-4 border-t border-border">
            <p class="text-xs text-muted-text mb-3">{{ __('app.custom_activities_desc') }}</p>
            <form @submit.prevent="addActivity().then(() => $dispatch('checklist-updated'))" class="flex flex-wrap gap-2">
                <input type="text" x-model="addActivityName" maxlength="255"
                       :placeholder="'{{ __('app.custom_activity_placeholder') }}'"
                       class="min-w-0 flex-1 basis-24 px-4 py-2.5 border border-border rounded-xl bg-muted text-primary text-sm outline-none focus:ring-2 focus:ring-accent">
                <button type="submit" :disabled="!addActivityName.trim() || addActivityLoading"
                        class="shrink-0 px-4 py-2.5 bg-accent text-on-accent rounded-xl font-medium text-sm disabled:opacity-50 transition">
                    <span x-show="!addActivityLoading">{{ __('app.add_activity_day_btn') }}</span>
                    <span x-show="addActivityLoading" x-cloak>{{ __('app.loading') }}...</span>
                </button>
            </form>
            <p x-show="addActivityMsg" x-text="addActivityMsg" class="text-sm mt-2" :class="addActivityMsgError ? 'text-error' : 'text-success'"></p>
        </div>
        @endif
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function dayPage() {
    return {
        showSharePrompt: false,
        sharePromptDismissed: false,
        linkCopied: false,
        _observer: null,

        shareTitle: @js($shareTitle),
        shareDescription: @js($shareDescription),
        shareUrl: @js($shareUrl),

        customActivities: @js(($customActivities ?? collect())->values()->all()),
        customChecklistCompleted: @js($customChecklistCompleted ?? []),

        addActivityName: '',
        addActivityLoading: false,
        addActivityMsg: '',
        addActivityMsgError: false,

        init() {
            this.$nextTick(() => {
                const sentinel = this.$refs.bottomSentinel;
                if (!sentinel) return;
                this._observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting && !this.sharePromptDismissed) {
                            this.showSharePrompt = true;
                        }
                    });
                }, { threshold: 0.1 });
                this._observer.observe(sentinel);
            });
        },

        destroy() {
            if (this._observer) this._observer.disconnect();
        },

        async shareDay() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        text: this.shareTitle + '\n' + this.shareDescription + '\n' + this.shareUrl,
                    });
                } catch (_e) {
                    // User cancelled or share failed
                }
            } else {
                this.copyLink();
            }
        },

        async copyLink() {
            try {
                await navigator.clipboard.writeText(this.shareUrl);
            } catch (_e) {
                const ta = document.createElement('textarea');
                ta.value = this.shareUrl;
                ta.style.cssText = 'position:fixed;opacity:0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.linkCopied = true;
            setTimeout(() => { this.linkCopied = false; }, 2000);
        },

        async toggleChecklist(dailyContentId, activityId, completed) {
            await AbiyTsom.api('/api/member/checklist/toggle', {
                daily_content_id: dailyContentId,
                activity_id: activityId,
                completed: completed,
            });
        },
        async toggleCustomChecklist(dailyContentId, customActivityId, completed) {
            await AbiyTsom.api('/api/member/checklist/custom-toggle', {
                daily_content_id: dailyContentId,
                member_custom_activity_id: customActivityId,
                completed: completed,
            });
        },

        async addActivity() {
            const name = this.addActivityName.trim();
            if (!name || this.addActivityLoading) return;
            this.addActivityLoading = true;
            this.addActivityMsg = '';
            const data = await AbiyTsom.api('/api/member/custom-activities', { name });
            this.addActivityLoading = false;
            if (data.success) {
                this.customActivities.push(data.activity);
                this.customChecklistCompleted = { ...this.customChecklistCompleted, [data.activity.id]: false };
                this.addActivityName = '';
                this.addActivityMsg = '{{ __("app.custom_activity_added") }}';
                this.addActivityMsgError = false;
                setTimeout(() => { this.addActivityMsg = ''; }, 3000);
            } else {
                this.addActivityMsg = data.message || '{{ __("app.failed_to_add") }}';
                this.addActivityMsgError = true;
            }
        }
    };
}
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => { window.AbiyTsomContinueTour?.('day'); }, 500);
});
</script>
@endpush

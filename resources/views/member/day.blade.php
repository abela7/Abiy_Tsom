@extends('layouts.member')

@php
    $locale = app()->getLocale();
    $publicPreview = (bool) ($publicPreview ?? false);
    $guestAccess = (bool) ($guestAccess ?? false);
    $isHimamatDaily = isset($himamatDay) && $himamatDay !== null;
    $backUrl = $backUrl ?? ($publicPreview ? route('home') : memberUrl('/calendar'));
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
    $memberTokenForLinks = $guestAccess ? ($currentMember->token ?? null) : null;
    $prevDayHref = $prevDay
        ? ($prevDayUrl ?? ($guestAccess
            ? $prevDay->memberDayUrl($memberTokenForLinks)
            : route('old.member.day.show', ['dayNumber' => $prevDay->day_number, 'daily' => $prevDay])))
        : null;
    $nextDayHref = $nextDay
        ? ($nextDayUrl ?? ($guestAccess
            ? $nextDay->memberDayUrl($memberTokenForLinks)
            : route('old.member.day.show', ['dayNumber' => $nextDay->day_number, 'daily' => $nextDay])))
        : null;
    $commemorationsHref = $commemorationsUrl ?? ($guestAccess
        ? $daily->memberCommemorationsUrl($memberTokenForLinks)
        : route('old.member.commemorations.show', ['dayNumber' => $daily->day_number, 'daily' => $daily]));
@endphp

@section('title', $shareTitle . ' - ' . __('app.app_name'))

@section('og_title', $shareTitle)
@section('og_description', $shareDescription)

@section('content')
@if($isGoodFriday ?? false)
<style>
    html.dark body { background: transparent !important; }
    html.dark { background: #030303 !important; }
    .good-friday-page {
        --color-card: rgba(10, 10, 18, 0.52);
        --color-muted: rgba(15, 15, 25, 0.45);
        --color-border: rgba(255, 255, 255, 0.09);
    }
    .good-friday-page > * {
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
</style>
<div style="position:fixed;inset:0;z-index:0;
     background-image:url('https://abiytsom.abuneteklehaymanot.org/images/Jesus-.jpg');
     background-size:cover;background-position:center center;background-repeat:no-repeat;">
    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.42);"></div>
</div>
<script>
    window.addEventListener('alpine:initialized', function () {
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
    }, { once: true });
</script>
@endif
<div x-data="dayPage()" class="px-4 pt-4 space-y-4 @if($isGoodFriday ?? false) good-friday-page @endif" @if($isGoodFriday ?? false) style="position:relative;z-index:1" @endif>

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

    @php
        $hasEthDate = !empty($ethDateInfo['ethiopian_date_formatted'] ?? null);
        $annuals = $ethDateInfo['annual_celebrations'] ?? collect();
        $monthlies = $ethDateInfo['monthly_celebrations'] ?? collect();
        $hasCelebrations = $annuals->isNotEmpty() || $monthlies->isNotEmpty();

        $slides = collect();
        foreach ($annuals as $s) {
            $slides->push(['type' => __('app.synaxarium_yearly_commemorations'), 'name' => localized($s, 'celebration'), 'image' => $s->imageUrl()]);
        }
        foreach ($monthlies as $s) {
            $slides->push(['type' => __('app.synaxarium_monthly_commemorations'), 'name' => localized($s, 'celebration'), 'image' => $s->imageUrl()]);
        }
    @endphp

    {{-- Day title with prev/next navigation --}}
    <div class="flex items-center justify-between">
        @if($prevDay && !($isGoodFriday ?? false))
        <a href="{{ $prevDayHref }}" class="shrink-0 w-10 h-10 rounded-xl bg-muted hover:bg-border flex items-center justify-center text-muted-text hover:text-primary transition-all active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        @else
        <div class="w-10"></div>
        @endif

        <div class="text-center flex-1 min-w-0">
            <h1 class="text-2xl font-black text-primary">
                {{ __('app.day_of', ['day' => $daily->day_number, 'total' => 55]) }}
            </h1>
            <p class="text-sm text-muted-text mt-0.5">{{ $daily->date->locale('en')->translatedFormat('l, F j, Y') }}</p>
        </div>

        @if($nextDay && !($isGoodFriday ?? false))
        <a href="{{ $nextDayHref }}" class="shrink-0 w-10 h-10 rounded-xl bg-muted hover:bg-border flex items-center justify-center text-muted-text hover:text-primary transition-all active:scale-95">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @else
        <div class="w-10"></div>
        @endif
    </div>

    {{-- Day header card --}}
    <div data-tour="day-header" class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">

        {{-- Ethiopian Calendar row --}}
        @if($hasEthDate)
        <div class="flex items-center gap-3 px-4 py-3 bg-muted/30">
            <img src="{{ asset('images/EOTC_Logo.jpg') }}" alt="" loading="eager" decoding="async" width="40" height="40" class="w-10 h-10 rounded-lg object-cover shrink-0 shadow-sm">
            <div class="flex-1 min-w-0">
                <span class="block text-[10px] font-semibold text-muted-text uppercase tracking-wider">{{ __('app.ethiopian_calendar_title') }}</span>
                <span class="block text-lg font-bold text-primary mt-0.5">{{ $ethDateInfo['ethiopian_date_formatted'] }}</span>
            </div>
        </div>
        @endif

        {{-- Commemorations carousel row --}}
        @if(!($isGoodFriday ?? false) && $slides->isNotEmpty() && (($commemorationsUrl ?? null) !== null || !($publicPreview ?? false)))
        <a href="{{ $commemorationsHref }}"
           class="flex items-center gap-3 px-4 py-3 bg-accent/5 hover:bg-accent/10 active:scale-[0.98] transition-all group"
           x-data="{ current: 0, total: {{ $slides->count() }}, images: {{ $slides->map(fn($s) => $s['image'] ?? null)->toJson() }}, fallback: '{{ asset('images/Saints.png') }}' }"
           x-init="setInterval(() => current = (current + 1) % total, 3000)">
            <div class="shrink-0 w-12 h-12 rounded-xl overflow-hidden shadow-sm relative ring-1 ring-border">
                <img :src="images[current] || fallback" alt="" loading="eager" decoding="async" width="48" height="48" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 min-w-0 relative h-11 overflow-hidden">
                @foreach($slides as $i => $slide)
                <div x-show="current === {{ $i }}"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-2"
                     class="absolute inset-0 flex flex-col justify-center"
                     x-cloak>
                    <span class="text-[10px] font-semibold text-muted-text uppercase tracking-wider leading-none">{{ $slide['type'] }}</span>
                    <span class="text-base font-bold text-accent mt-1 truncate leading-tight">{{ $slide['name'] }}</span>
                </div>
                @endforeach
            </div>
            <svg class="w-5 h-5 text-accent group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div class="px-4 pb-2 bg-accent/5">
            <span class="text-[10px] text-muted-text">{{ __('app.synaxarium_tap_more_detail') }}</span>
        </div>
        @endif

        {{-- Weekly theme link --}}
        @if(!($isGoodFriday ?? false) && $daily->weeklyTheme)
        <a href="{{ memberUrl('/week/' . $daily->weeklyTheme->id) }}" class="flex items-center gap-3 px-4 py-3 bg-accent/5 hover:bg-accent/10 active:scale-[0.98] transition-all group">
            <div class="shrink-0 w-9 h-9 rounded-lg bg-accent/15 flex items-center justify-center">
                <i class="bi bi-calendar-week text-accent text-sm"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="block text-sm font-bold text-accent">{{ __('app.week', ['number' => $daily->weeklyTheme->week_number]) }} &mdash; {{ localized($daily->weeklyTheme, 'name') ?? $daily->weeklyTheme->name_en ?? $daily->weeklyTheme->name_geez ?? '-' }}</span>
                <span class="block text-[11px] text-muted-text mt-0.5">{{ __('app.week_tap_to_read') }}</span>
            </div>
            <svg class="w-5 h-5 text-accent group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endif
    </div>

    {{-- Bible Reading --}}
    @if(!$isHimamatDaily && localized($daily, 'bible_reference'))
    @php
        $bibleText     = localized($daily, 'bible_text');
        $bibleAudioAm  = $daily->bible_audio_url_am ?: null;
        $bibleAudioEn  = $daily->bible_audio_url_en ?: null;
        $bibleAudioUrl = $daily->bibleAudioUrl();
        $bibleAudioInitLocale = ($bibleAudioAm && $locale === 'am') ? 'am' : ($bibleAudioEn ? 'en' : 'am');
    @endphp
    <div data-tour="day-bible" class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
         x-data="{
            open: false,
            fontSize: parseInt(localStorage.getItem('bibleFontSize') || '16'),
            readerTheme: localStorage.getItem('bibleReaderTheme') || 'sepia',
            readerFont: localStorage.getItem('bibleReaderFont') || 'default',
            fullscreen: false,
            activeShelf: null,
            shelfTapLock: false,
            shelfTapLockTimer: null,
            lockShelfTap(ms=650){ this.shelfTapLock=true; if(this.shelfTapLockTimer) clearTimeout(this.shelfTapLockTimer); this.shelfTapLockTimer=setTimeout(()=>{this.shelfTapLock=false;this.shelfTapLockTimer=null;},ms); },
            toggleShelf(n){ if(this.shelfTapLock) return; this.activeShelf=this.activeShelf===n?null:n; },
            pickTheme(t){ this.readerTheme=t; localStorage.setItem('bibleReaderTheme',t); this.activeShelf=null; this.lockShelfTap(); },
            pickFont(f){ this.readerFont=f; localStorage.setItem('bibleReaderFont',f); this.activeShelf=null; this.lockShelfTap(); },
            fontFamily(){ if(this.readerFont==='benaiah') return 'Benaiah,sans-serif'; if(this.readerFont==='kiros') return 'Kiros,sans-serif'; if(this.readerFont==='handwriting') return 'Handwriting,sans-serif'; return 'inherit'; },
            setFontSize(s){ this.fontSize=Math.min(28,Math.max(12,s)); localStorage.setItem('bibleFontSize',this.fontSize); },
            openFullscreen(){ this.fullscreen=true; document.body.style.overflow='hidden'; const n=document.querySelector('nav.fixed.bottom-0'); if(n) n.style.display='none'; },
            closeFullscreen(){ this.fullscreen=false; this.activeShelf=null; this.shelfTapLock=false; if(this.shelfTapLockTimer){clearTimeout(this.shelfTapLockTimer);this.shelfTapLockTimer=null;} document.body.style.overflow=''; const n=document.querySelector('nav.fixed.bottom-0'); if(n) n.style.display=''; }
         }"
         @keydown.escape.window="if(fullscreen) closeFullscreen()">

        {{-- Card header --}}
        <div class="px-4 pt-4 pb-3">
            <h3 class="font-semibold text-sm text-accent mb-1">{{ __('app.bible_reading') }}</h3>
            <p class="font-medium text-primary">{{ localized($daily, 'bible_reference') }}</p>
            @if(localized($daily, 'bible_summary'))
            <p class="text-sm text-muted-text mt-1.5 leading-relaxed">{{ localized($daily, 'bible_summary') }}</p>
            @endif
        </div>

        @if($bibleAudioAm || $bibleAudioEn)
        <div class="px-4 pb-4"
             x-data="{
                audioOpen: false,
                playing: false,
                buffering: false,
                currentTime: 0,
                duration: 0,
                buffered: 0,
                muted: false,
                speed: 1,
                loaded: false,
                rafId: null,
                activeLocale: '{{ $bibleAudioInitLocale }}',
                urls: { am: @js($bibleAudioAm), en: @js($bibleAudioEn) },
                get hasBoth() { return !!(this.urls.am && this.urls.en); },
                get progress() { return this.duration ? (this.currentTime / this.duration) * 100 : 0; },
                get bufferProgress() { return this.duration ? (this.buffered / this.duration) * 100 : 0; },
                fmt(s) {
                    if (!s || isNaN(s)) return '0:00';
                    const m = Math.floor(s / 60), sec = Math.floor(s % 60);
                    return m + ':' + String(sec).padStart(2, '0');
                },
                getSrc() {
                    return this.urls[this.activeLocale] || this.urls.am || this.urls.en || '';
                },
                loadAudio() {
                    if (this.loaded) return;
                    const a = this.$refs.audio;
                    const src = this.getSrc();
                    if (!src) return;
                    a.src = src;
                    a.preload = 'auto';
                    a.load();
                    this.loaded = true;
                },
                init() {
                    this.$watch('activeLocale', () => {
                        const a = this.$refs.audio;
                        if (!this.loaded) return;
                        const src = this.getSrc();
                        if (!src) return;
                        const wasPlaying = this.playing;
                        a.pause();
                        this.playing = false; this.buffering = false;
                        this.currentTime = 0; this.duration = 0; this.buffered = 0;
                        if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; }
                        a.src = src;
                        a.preload = 'auto';
                        a.load();
                        if (wasPlaying) a.play().catch(() => {});
                    });
                },
                openPlayer() {
                    this.audioOpen = true;
                    this.$nextTick(() => this.loadAudio());
                },
                async togglePlay() {
                    this.loadAudio();
                    const a = this.$refs.audio;
                    if (this.playing) {
                        a.pause();
                    } else {
                        this.buffering = true;
                        try { await a.play(); } catch(_) {}
                    }
                },
                onPlay()    { this.playing = true; this.buffering = false; this.tick(); },
                onPause()   { this.playing = false; this.buffering = false; if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; } },
                onEnded()   { this.playing = false; this.buffering = false; this.currentTime = 0; if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; } },
                onWaiting() { this.buffering = true; },
                onCanPlay() { this.buffering = false; },
                onMeta()    { this.duration = this.$refs.audio.duration || 0; },
                onProgress() {
                    const a = this.$refs.audio;
                    if (a.buffered.length > 0) {
                        this.buffered = a.buffered.end(a.buffered.length - 1);
                    }
                },
                tick() {
                    const a = this.$refs.audio;
                    this.currentTime = a.currentTime;
                    if (a.buffered.length > 0) {
                        this.buffered = a.buffered.end(a.buffered.length - 1);
                    }
                    if (this.playing) this.rafId = requestAnimationFrame(() => this.tick());
                },
                seek(e) {
                    const a = this.$refs.audio;
                    a.currentTime = (e.target.value / 100) * (this.duration || 0);
                    this.currentTime = a.currentTime;
                },
                setSpeed(s) { this.speed = s; this.$refs.audio.playbackRate = s; },
                toggleMute() { this.muted = !this.muted; this.$refs.audio.muted = this.muted; },
                skipBy(sec) { const a = this.$refs.audio; a.currentTime = Math.min(Math.max(a.currentTime + sec, 0), this.duration || 0); this.currentTime = a.currentTime; }
             }">

            <button type="button" @click="audioOpen ? (audioOpen = false) : openPlayer()"
                    class="w-full flex items-center justify-between gap-2 py-2.5 px-3 rounded-xl bg-muted/70 hover:bg-muted transition mb-3">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="audioOpen ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ $locale === 'am' ? 'ድምፅ ያዳምጡ' : 'Listen to Audio' }}</span>
                        <p x-show="!audioOpen" class="text-[11px] text-muted-text mt-0.5">{{ $locale === 'am' ? 'ለማዳመጥ እዚህ ላይ ይንኩ' : 'Tap here to listen' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span x-show="playing" x-cloak class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-50"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-accent"></span>
                    </span>
                    <span x-show="audioOpen" class="text-[11px] font-semibold text-muted-text uppercase tracking-wider">{{ __('app.close') }}</span>
                </div>
            </button>

            <div x-show="audioOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">

                <audio x-ref="audio" preload="none"
                       @play="onPlay()" @pause="onPause()" @ended="onEnded()" @loadedmetadata="onMeta()"
                       @waiting="onWaiting()" @canplay="onCanPlay()" @progress="onProgress()">
                </audio>

                <div class="rounded-2xl border border-border bg-card overflow-hidden">

                    {{-- Title + language toggle --}}
                    <div class="px-4 pt-4 pb-2 text-center">
                        <div class="flex items-center justify-center gap-2 mb-1">
                            <svg class="w-4 h-4 text-accent shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v6.499a2.5 2.5 0 10.99 1.98L7 7.22l8-1.6v4.879a2.5 2.5 0 10.99 1.98L16 5.72V3z"/></svg>
                            <span class="text-sm font-bold text-primary">{{ localized($daily, 'bible_reference') }}</span>
                        </div>
                        <p class="text-[11px] text-muted-text">{{ $locale === 'am' ? 'የመጽሐፍ ቅዱስ ንባብ' : 'Bible Reading' }}</p>
                        <div x-show="hasBoth" class="flex justify-center mt-2.5">
                            <div class="inline-flex bg-muted rounded-lg p-0.5 gap-0.5">
                                <button type="button" @click="activeLocale='am'"
                                        :class="activeLocale==='am' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'"
                                        class="px-3 py-1 rounded-md text-[11px] font-bold transition touch-manipulation">{{ $locale === 'am' ? 'አማርኛ' : 'አማ' }}</button>
                                <button type="button" @click="activeLocale='en'"
                                        :class="activeLocale==='en' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'"
                                        class="px-3 py-1 rounded-md text-[11px] font-bold transition touch-manipulation">EN</button>
                            </div>
                        </div>
                    </div>

                    {{-- Seek bar with buffer indicator --}}
                    <div class="px-5 pt-2 pb-1">
                        <div class="relative h-8 flex items-center cursor-pointer">
                            <div class="absolute w-full rounded-full" style="height:4px;background:color-mix(in srgb, var(--color-primary) 15%, transparent);"></div>
                            <div class="absolute rounded-full transition-none" style="height:4px;left:0;background:color-mix(in srgb, var(--color-primary) 35%, transparent);" :style="'width:'+bufferProgress+'%'"></div>
                            <div class="absolute rounded-full bg-primary transition-none" style="height:4px;left:0;" :style="'width:'+progress+'%'"></div>
                            <div class="absolute w-4 h-4 rounded-full bg-primary shadow-md -translate-x-1/2 transition-none"
                                 :style="'left:'+Math.min(Math.max(progress,0),100)+'%'"></div>
                            <input type="range" min="0" max="100" step="0.1" :value="progress"
                                   @input="seek($event)"
                                   class="absolute inset-0 w-full opacity-0 cursor-pointer" style="height:100%">
                        </div>
                        <div class="flex justify-between text-[10px] font-medium text-muted-text tabular-nums select-none">
                            <span x-text="fmt(currentTime)">0:00</span>
                            <span x-text="duration ? fmt(duration) : '--:--'">--:--</span>
                        </div>
                    </div>

                    {{-- Transport controls --}}
                    <div class="flex items-center justify-center gap-5 py-3">
                        <button type="button" @click="toggleMute()"
                                class="w-10 h-10 rounded-full flex items-center justify-center hover:bg-muted transition touch-manipulation"
                                :class="muted ? 'text-muted-text' : 'text-secondary'">
                            <svg x-show="!muted" class="w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M11.383 3.076A1 1 0 0112 4v16a1 1 0 01-1.707.707L6.586 17H4a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z"/>
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="1.5" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728"/>
                            </svg>
                            <svg x-show="muted" x-cloak class="w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <path fill="currentColor" d="M11.383 3.076A1 1 0 0112 4v16a1 1 0 01-1.707.707L6.586 17H4a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217z"/>
                                <path stroke="currentColor" stroke-linecap="round" stroke-width="1.5" d="M17 14l4-4m0 4l-4-4"/>
                            </svg>
                        </button>

                        <button type="button" @click="skipBy(-10)"
                                class="w-10 h-10 rounded-full flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation active:scale-95">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M9.195 18.44c1.25.714 2.805-.189 2.805-1.629v-2.34l6.945 3.968c1.25.715 2.805-.188 2.805-1.628V7.19c0-1.44-1.555-2.343-2.805-1.628L12 9.53V7.19c0-1.44-1.555-2.343-2.805-1.628l-7.108 4.061c-1.26.72-1.26 2.536 0 3.256l7.108 4.061z"/></svg>
                        </button>

                        <button type="button" @click="togglePlay()" :disabled="buffering"
                                class="w-14 h-14 rounded-full bg-accent flex items-center justify-center shrink-0 active:scale-95 transition touch-manipulation shadow-lg hover:opacity-90">
                            <svg x-show="buffering" x-cloak class="w-6 h-6 text-white animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg x-show="!buffering && !playing" class="w-6 h-6 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5.14v14.72a1 1 0 001.5.86l11-7.36a1 1 0 000-1.72l-11-7.36A1 1 0 008 5.14z"/>
                            </svg>
                            <svg x-show="!buffering && playing" x-cloak class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M6.75 4a.75.75 0 00-.75.75v14.5c0 .414.336.75.75.75h2.5a.75.75 0 00.75-.75V4.75A.75.75 0 009.25 4h-2.5zM14.75 4a.75.75 0 00-.75.75v14.5c0 .414.336.75.75.75h2.5a.75.75 0 00.75-.75V4.75a.75.75 0 00-.75-.75h-2.5z"/>
                            </svg>
                        </button>

                        <button type="button" @click="skipBy(10)"
                                class="w-10 h-10 rounded-full flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation active:scale-95">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M14.805 5.56c-1.25-.714-2.805.189-2.805 1.629v2.34L5.055 5.56C3.805 4.846 2.25 5.749 2.25 7.189v9.622c0 1.44 1.555 2.343 2.805 1.628L12 14.47v2.34c0 1.44 1.555 2.343 2.805 1.628l7.108-4.061c1.26-.72 1.26-2.536 0-3.256L14.805 5.56z"/></svg>
                        </button>

                        <button type="button" @click="skipBy(30)"
                                class="w-10 h-10 rounded-full flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5"/><path stroke-linecap="round" d="M20.5 9A9 9 0 003.5 9M3.5 15a9 9 0 0017 0"/></svg>
                        </button>
                    </div>

                    {{-- Speed control --}}
                    <div class="flex items-center justify-center gap-1 pb-3">
                        <template x-for="s in [0.75, 1, 1.25, 1.5, 2]">
                            <button type="button" @click="setSpeed(s)"
                                    :class="speed === s ? 'text-accent font-bold' : 'text-muted-text'"
                                    class="px-2 py-0.5 rounded text-[10px] font-semibold transition touch-manipulation hover:text-primary"
                                    x-text="s + '×'"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($bibleText)
        <div class="px-4 pb-4">
            {{-- Read toggle --}}
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between gap-2 py-2.5 px-3 rounded-xl bg-muted/70 hover:bg-muted transition mb-3">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.read') }}</span>
                        <p x-show="!open" class="text-[11px] text-muted-text mt-0.5">{{ $locale === 'am' ? 'ለማንበብ እዚህ ላይ ይንኩ' : 'Click here to read' }}</p>
                    </div>
                </div>
                <span x-show="open" class="text-[11px] font-semibold text-muted-text uppercase tracking-wider shrink-0">{{ __('app.close') }}</span>
            </button>

            {{-- Inline reader --}}
            <div x-show="open" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">

                {{-- Toolbar --}}
                <div class="flex items-center justify-between gap-2 py-2 px-3 rounded-xl bg-muted/60 mb-3">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="setFontSize(fontSize-2)" :disabled="fontSize<=12" :class="fontSize<=12&&'opacity-30 cursor-not-allowed'"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                        </button>
                        <span class="text-xs font-bold text-primary tabular-nums w-6 text-center" x-text="fontSize"></span>
                        <button type="button" @click="setFontSize(fontSize+2)" :disabled="fontSize>=28" :class="fontSize>=28&&'opacity-30 cursor-not-allowed'"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="relative" x-data="{fo:false}" @click.outside="fo=false">
                            <button type="button" @click="fo=!fo" :class="fo?'bg-accent border-accent text-on-accent':'bg-card border-border text-secondary hover:bg-muted'"
                                    class="h-7 px-2.5 rounded-lg border transition touch-manipulation flex items-center gap-1">
                                <span class="text-[13px] font-bold" :style="readerFont==='benaiah'?'font-family:Benaiah,sans-serif':readerFont==='kiros'?'font-family:Kiros,sans-serif':readerFont==='handwriting'?'font-family:Handwriting,sans-serif':''">ሀ</span>
                                <svg class="w-2.5 h-2.5 opacity-60 transition-transform" :class="fo&&'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <div x-show="fo" x-transition x-cloak class="absolute right-0 top-full mt-1.5 w-44 bg-card border border-border rounded-xl shadow-xl overflow-hidden z-50" style="display:none">
                                @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Handwriting','Handwriting,sans-serif']] as [$fv,$fl,$ff])
                                <button type="button" @click="fo=false;pickFont('{{ $fv }}')" :class="readerFont==='{{ $fv }}'?'bg-accent/10':'hover:bg-muted'"
                                        class="w-full px-3 py-2.5 text-left flex items-center gap-3 border-b border-border last:border-0 touch-manipulation">
                                    <span class="text-lg font-bold" style="font-family:{{ $ff }}">ሀ</span>
                                    <span class="text-sm" :class="readerFont==='{{ $fv }}'?'text-accent font-semibold':'text-primary'">{{ $fl }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <button type="button" @click="openFullscreen()"
                                class="h-7 px-2.5 rounded-lg bg-card border border-border text-secondary hover:bg-muted transition touch-manipulation flex items-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Text --}}
                <div class="text-primary whitespace-pre-wrap"
                     :style="'font-size:'+fontSize+'px;line-height:'+(fontSize<20?'1.85':'1.75')+';font-family:'+fontFamily()">{{ $bibleText }}</div>
            </div>
        </div>
        @endif

        {{-- Fullscreen reader --}}
        @if($bibleText)
        <template x-if="fullscreen">
            <div class="fixed inset-0 z-[100] flex flex-col bg-surface"
                 :class="readerTheme==='sepia'?'theme-sepia':readerTheme==='dark'?'dark':'theme-light'"
                 :style="readerTheme==='sepia'?'--color-accent:#78560D;--color-accent-hover:#614409;--app-accent:#78560D;--app-accent-hover:#614409':''">

                <div class="flex-1 overflow-y-auto">
                    {{-- Sticky header --}}
                    <div class="sticky top-0 z-10 px-4 py-3 border-b border-border bg-card flex items-center gap-3">
                        <button type="button" @click="closeFullscreen()" class="w-8 h-8 rounded-lg flex items-center justify-center text-accent touch-manipulation">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-accent">{{ __('app.bible_reading') }}</p>
                            <p class="text-sm font-semibold mt-0.5 text-primary">{{ localized($daily, 'bible_reference') }}</p>
                        </div>
                    </div>

                    {{-- Content --}}
                    <div class="max-w-2xl mx-auto px-5 py-6">
                        @if(localized($daily, 'bible_summary'))
                        <p class="text-sm text-muted-text leading-relaxed mb-5 pb-5 border-b border-border">{{ localized($daily, 'bible_summary') }}</p>
                        @endif
                        <div class="text-primary whitespace-pre-wrap"
                             :style="'font-size:'+fontSize+'px;line-height:'+(fontSize<20?'1.9':'1.8')+';font-family:'+fontFamily()">{{ $bibleText }}</div>
                    </div>
                </div>

                {{-- Font shelf --}}
                <template x-if="activeShelf==='font'">
                    <div class="absolute bottom-16 left-0 right-0 border-t border-border bg-card px-4 py-4 z-[101]">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Writing','Handwriting,sans-serif']] as [$fv,$fl,$ff])
                            <button type="button" @pointerup.stop.prevent="pickFont('{{ $fv }}')" class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold text-primary transition-all border-2 border-border bg-card" style="font-family:{{ $ff }}"
                                      :class="readerFont==='{{ $fv }}'&&'!border-accent !border-3 scale-110'">ሀ</span>
                                <span class="text-[10px] font-semibold text-muted-text" :class="readerFont==='{{ $fv }}'&&'!text-accent'">{{ $fl }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </template>

                {{-- Theme shelf --}}
                <template x-if="activeShelf==='theme'">
                    <div class="absolute bottom-16 left-0 right-0 border-t border-border bg-card px-4 py-4 z-[101]">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            @foreach([['light','A','#f9fafb','#111827','Light'],['sepia','A','#f5edd8','#1c1008','ብራና'],['dark','A','#030712','#f9fafb','Dark']] as [$tv,$tl,$tbg,$tc,$tlabel])
                            <button type="button" @pointerup.stop.prevent="pickTheme('{{ $tv }}')" class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold transition-all border-2 border-border"
                                      style="background-color:{{ $tbg }};color:{{ $tc }}"
                                      :class="readerTheme==='{{ $tv }}'&&'!border-accent !border-3 scale-110'">{{ $tl }}</span>
                                <span class="text-[10px] font-semibold text-muted-text" :class="readerTheme==='{{ $tv }}'&&'!text-accent'">{{ $tlabel }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </template>

                {{-- Bottom toolbar --}}
                <div class="shrink-0 border-t border-border bg-card safe-bottom" :class="{'pointer-events-none':shelfTapLock}">
                    <div class="flex items-center justify-around h-16 max-w-lg mx-auto px-2">
                        <button type="button" @click="closeFullscreen()" class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-accent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.close') }}</span>
                        </button>
                        <button type="button" @click="setFontSize(fontSize-2)" :disabled="fontSize<=12" :class="fontSize<=12?'opacity-30 cursor-not-allowed':''"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary">
                            <span class="text-base font-bold leading-none">A</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M5 12h14"/></svg>
                        </button>
                        <div class="flex flex-col items-center gap-0.5 px-1">
                            <span class="text-sm font-bold tabular-nums text-primary" x-text="fontSize"></span>
                            <span class="text-[8px] font-semibold uppercase tracking-wider text-muted-text">{{ __('app.font_size') }}</span>
                        </div>
                        <button type="button" @click="setFontSize(fontSize+2)" :disabled="fontSize>=28" :class="fontSize>=28?'opacity-30 cursor-not-allowed':''"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary">
                            <span class="text-xl font-bold leading-none">A</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                        <button type="button" @pointerup.stop.prevent="toggleShelf('theme')"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary"
                                :class="activeShelf==='theme'&&'!text-accent bg-muted'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.reader_theme') }}</span>
                        </button>
                        <button type="button" @pointerup.stop.prevent="toggleShelf('font')"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary"
                                :class="activeShelf==='font'&&'!text-accent bg-muted'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">Font</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
        @endif
    </div>
    @endif

    {{-- Mezmur (multiple) — exclusive accordion: when one opens, others collapse --}}
    @if(!$isHimamatDaily && $daily->mezmurs->isNotEmpty())
    @include('member.partials.day-mezmurs', ['daily' => $daily, 'locale' => $locale])
    @endif

    @if($isHimamatDaily)
        {{-- Section divider: Daily → Himamat transition --}}
        <div class="flex items-center gap-3 px-1">
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-accent/25 to-transparent"></div>
            <div class="flex items-center gap-1.5 shrink-0">
                <svg class="w-3.5 h-3.5 text-accent/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-[11px] font-bold uppercase tracking-[0.16em] text-accent/70">{{ __('app.himamat_eyebrow') }}</span>
            </div>
            <div class="h-px flex-1 bg-gradient-to-r from-transparent via-accent/25 to-transparent"></div>
        </div>

        @include('member.partials.himamat-linked-sections', [
            'himamatDay' => $himamatDay,
            'himamatTimeline' => $himamatTimeline ?? [],
            'ethDateInfo' => $ethDateInfo ?? [],
        ])

        {{-- Continue Daily Content bridge --}}
        <div class="flex flex-col items-center gap-2 py-1">
            <div class="h-px w-full bg-gradient-to-r from-transparent via-border to-transparent"></div>
            <div class="flex items-center gap-2 text-muted-text">
                <svg class="w-3.5 h-3.5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                <span class="text-[11px] font-semibold uppercase tracking-[0.14em]">{{ app()->getLocale() === 'am' ? 'ዕለታዊ ይዘት ይቀጥሉ' : 'Continue Daily Content' }}</span>
                <svg class="w-3.5 h-3.5 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
            <div class="h-px w-full bg-gradient-to-r from-transparent via-border to-transparent"></div>
        </div>
    @endif

    {{-- Mezmur for Himamat days — shown here, below "Continue Daily Content" bridge --}}
    @if($isHimamatDaily && $daily->mezmurs->isNotEmpty())
    @include('member.partials.day-mezmurs', ['daily' => $daily, 'locale' => $locale])
    @endif

    {{-- Sinksar (Synaxarium) — Read / Listen toggle with immersive reader --}}
    @if(!($isGoodFriday ?? false) && localized($daily, 'sinksar_title'))
    <div data-tour="day-sinksar"
         class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
         x-data="{
            mode: '{{ $hasSinksarRead ? 'read' : ($hasSinksarListen ? 'listen' : 'read') }}',
            fontSize: parseInt(localStorage.getItem('sinksarFontSize') || '16'),
            readerTheme: localStorage.getItem('sinksarReaderTheme') || 'default',
            readerFont: localStorage.getItem('sinksarReaderFont') || 'default',
            fullscreen: false,
            readOpen: false,
            inlineFontOpen: false,
            activeShelf: null,
            shelfTapLock: false,
            shelfTapLockTimer: null,
            lockShelfTap(ms = 650) {
                this.shelfTapLock = true;
                if (this.shelfTapLockTimer) clearTimeout(this.shelfTapLockTimer);
                this.shelfTapLockTimer = setTimeout(() => {
                    this.shelfTapLock = false;
                    this.shelfTapLockTimer = null;
                }, ms);
            },
            toggleShelf(name) {
                if (this.shelfTapLock) return;
                this.activeShelf = this.activeShelf === name ? null : name;
            },
            pickTheme(t) {
                this.readerTheme = t;
                localStorage.setItem('sinksarReaderTheme', t);
                this.activeShelf = null;
                this.lockShelfTap();
            },
            pickFont(f) {
                this.readerFont = f;
                localStorage.setItem('sinksarReaderFont', f);
                this.activeShelf = null;
                this.lockShelfTap();
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
            openFullscreen() {
                this.fullscreen = true;
                document.body.style.overflow = 'hidden';
                const nav = document.querySelector('nav.fixed.bottom-0');
                if (nav) nav.style.display = 'none';
            },
            closeFullscreen() {
                this.fullscreen = false;
                this.activeShelf = null;
                this.shelfTapLock = false;
                if (this.shelfTapLockTimer) {
                    clearTimeout(this.shelfTapLockTimer);
                    this.shelfTapLockTimer = null;
                }
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
             x-data='{
                imgCurrent: 0,
                imgTotal: {{ $sinksarImages->count() }},
                _touchX: 0, _touchY: 0,
                _autoTimer: null,
                imgNext() { this.imgCurrent = (this.imgCurrent + 1) % this.imgTotal; },
                imgPrev() { this.imgCurrent = (this.imgCurrent - 1 + this.imgTotal) % this.imgTotal; },
                startAuto() { this.stopAuto(); this._autoTimer = setInterval(() => this.imgNext(), 3000); },
                stopAuto() { if (this._autoTimer) { clearInterval(this._autoTimer); this._autoTimer = null; } },
                imgTouchStart(e) { this.stopAuto(); this._touchX = e.touches[0].clientX; this._touchY = e.touches[0].clientY; },
                imgTouchEnd(e) {
                    var dx = e.changedTouches[0].clientX - this._touchX;
                    var dy = e.changedTouches[0].clientY - this._touchY;
                    if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 40) { dx > 0 ? this.imgPrev() : this.imgNext(); }
                    this.startAuto();
                },
                init() { if (this.imgTotal > 1) this.startAuto(); }
             }'>

            <div class="relative rounded-xl overflow-hidden"
                 style="aspect-ratio:4/3;background:#1a1a2e"
                 @touchstart.passive="imgTouchStart($event)"
                 @touchend.passive="imgTouchEnd($event)">
                @foreach($sinksarImages as $idx => $img)
                <div class="absolute inset-0 overflow-hidden flex items-center justify-center transition-all duration-500 ease-out"
                     :style="imgCurrent === {{ $idx }}
                         ? 'opacity:1;transform:translateX(0);z-index:10'
                         : {{ $idx }} > imgCurrent
                             ? 'opacity:0;transform:translateX(100%);z-index:1;pointer-events:none'
                             : 'opacity:0;transform:translateX(-100%);z-index:1;pointer-events:none'">
                    {{-- Blurred ambient background --}}
                    <img src="{{ $img->imageUrl() }}" alt=""
                         class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none"
                         loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                         decoding="async" width="400" height="300">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-900/25 via-transparent to-black/35 pointer-events-none"></div>
                    {{-- Main image --}}
                    <img src="{{ $img->imageUrl() }}"
                         alt="{{ localized($img, 'caption') ?? '' }}"
                         class="relative z-10 w-full h-full object-contain drop-shadow-[0_4px_20px_rgba(0,0,0,0.55)]"
                         loading="{{ $idx === 0 ? 'eager' : 'lazy' }}"
                         decoding="async" width="400" height="300">
                    @if(localized($img, 'caption'))
                    <div class="absolute bottom-0 inset-x-0 z-20 bg-gradient-to-t from-black/70 to-transparent px-3 py-2">
                        <p class="text-white text-xs font-medium">{{ localized($img, 'caption') }}</p>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>

            @if($sinksarImages->count() > 1)
            <div class="flex items-center justify-center gap-2 mt-2">
                <button type="button" @click="imgPrev(); startAuto()" class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-text hover:text-primary transition touch-manipulation">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="flex items-center gap-1">
                    @foreach($sinksarImages as $idx => $img)
                    <button type="button" @click="imgCurrent = {{ $idx }}; startAuto()"
                            class="transition-all duration-300 touch-manipulation"
                            :class="imgCurrent === {{ $idx }} ? 'w-4 h-1.5 rounded-full bg-sinksar' : 'w-1.5 h-1.5 rounded-full bg-border hover:bg-muted-text'">
                    </button>
                    @endforeach
                </div>
                <button type="button" @click="imgNext(); startAuto()" class="w-6 h-6 rounded-full bg-muted flex items-center justify-center text-muted-text hover:text-primary transition touch-manipulation">
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
                                        @click="inlineFontOpen = false; pickFont('{{ $val }}')"
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
            <template x-if="fullscreen">
            <div class="fixed inset-0 z-[9999] flex flex-col"
                 :style="readerTheme === 'default' ? 'background-color:#f8fbfd;color:#1f2937' : readerTheme === 'sepia' ? 'background-color:#f4ecd8;color:#5b4636' : 'background-color:#1a1a2e;color:#e0e0e0'">

                {{-- Fullscreen top bar --}}
                <div class="flex items-center justify-between gap-3 px-4 py-3 border-b shrink-0"
                     :style="readerTheme === 'default' ? 'background-color:#ffffff;border-color:#d7e3ea' : readerTheme === 'sepia' ? 'background-color:#ede3cc;border-color:#d4c5a9' : 'background-color:#16162a;border-color:#2a2a4a'">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <button type="button" @click="closeFullscreen()"
                                class="p-2 rounded-lg transition touch-manipulation shrink-0"
                                :style="readerTheme === 'default' ? 'background-color:#edf4f7;color:#0a6286' : readerTheme === 'sepia' ? 'background-color:#e8dcc6;color:#5b4636' : 'background-color:#2a2a4a;color:#e0e0e0'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate"
                               :style="readerTheme === 'default' ? 'color:#0f172a' : readerTheme === 'sepia' ? 'color:#3e2c1c' : 'color:#f0f0f0'">{{ localized($daily, 'sinksar_title') }}</p>
                            <p class="text-[10px] font-medium uppercase tracking-wider"
                               :style="readerTheme === 'default' ? 'color:#64748b' : readerTheme === 'sepia' ? 'color:#8b7355' : 'color:#8888aa'">{{ __('app.sinksar') }}</p>
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
                            fsStartAuto() { this._fsAutoTimer = setInterval(() => this.fsNext(), 3000); },
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
                            <div class="absolute inset-0 overflow-hidden flex items-center justify-center transition-all duration-500 ease-out"
                                 :style="fsCurrent === {{ $idx }}
                                     ? 'opacity:1;transform:translateX(0);z-index:10'
                                     : {{ $idx }} > fsCurrent
                                         ? 'opacity:0;transform:translateX(100%);z-index:1'
                                         : 'opacity:0;transform:translateX(-100%);z-index:1'">
                                {{-- Blurred ambient background --}}
                                <img src="{{ $img->imageUrl() }}" alt=""
                                     class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none" loading="lazy" decoding="async" width="400" height="300">
                                <div class="absolute inset-0 bg-gradient-to-br from-amber-900/25 via-transparent to-black/35 pointer-events-none"></div>
                                {{-- Main image --}}
                                <img src="{{ $img->imageUrl() }}" alt="{{ localized($img, 'caption') ?? '' }}"
                                     class="relative z-10 w-full h-full object-contain drop-shadow-[0_4px_20px_rgba(0,0,0,0.55)]" loading="lazy" decoding="async" width="400" height="300">
                                @if(localized($img, 'caption'))
                                <div class="absolute bottom-0 inset-x-0 z-20 bg-gradient-to-t from-black/70 to-transparent px-3 py-2">
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
                    {{-- Font shelf --}}
                    <template x-if="activeShelf === 'font'">
                        <div class="absolute bottom-full left-0 right-0 border-t px-4 py-4 z-[101]"
                             :style="readerTheme === 'default' ? 'background-color:#ffffff;border-color:#d7e3ea' : readerTheme === 'sepia' ? 'background-color:#e8dcc6;border-color:#d4c5a9' : 'background-color:#12122a;border-color:#2a2a4a'">
                        <div class="flex items-center justify-center gap-4 max-w-xs mx-auto">
                            @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Writing','Handwriting,sans-serif']] as [$fVal,$fLabel,$fFam])
                            <button type="button"
                                    @pointerup.stop.prevent="pickFont('{{ $fVal }}')"
                                    @keyup.enter.prevent="pickFont('{{ $fVal }}')"
                                    @keyup.space.prevent="pickFont('{{ $fVal }}')"
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
                    </template>

                    {{-- Theme shelf --}}
                    <template x-if="activeShelf === 'theme'">
                        <div class="absolute bottom-full left-0 right-0 border-t px-4 py-4 z-[101]"
                             :style="readerTheme === 'default' ? 'background-color:#ffffff;border-color:#d7e3ea' : readerTheme === 'sepia' ? 'background-color:#e8dcc6;border-color:#d4c5a9' : 'background-color:#12122a;border-color:#2a2a4a'">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            <button type="button"
                                    @pointerup.stop.prevent="pickTheme('default')"
                                    @keyup.enter.prevent="pickTheme('default')"
                                    @keyup.space.prevent="pickTheme('default')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full bg-white flex items-center justify-center transition-all"
                                      :style="'border:3px solid ' + (readerTheme === 'default' ? 'var(--color-accent)' : '#d1d5db') + (readerTheme === 'default' ? ';box-shadow:0 0 0 4px rgba(10,98,134,0.2);transform:scale(1.1)' : '')">
                                    <span class="text-xs font-bold text-gray-700">A</span>
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerTheme === 'default' ? 'color:var(--color-accent)' : readerTheme === 'sepia' ? 'color:#5b4636' : 'color:#8888aa'">{{ __('app.reader_theme_default') }}</span>
                            </button>
                            <button type="button"
                                    @pointerup.stop.prevent="pickTheme('sepia')"
                                    @keyup.enter.prevent="pickTheme('sepia')"
                                    @keyup.space.prevent="pickTheme('sepia')"
                                    class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                                      :style="'background-color:#f4ecd8;border:3px solid ' + (readerTheme === 'sepia' ? '#8b5e3c' : '#c4a87c') + (readerTheme === 'sepia' ? ';box-shadow:0 0 0 4px rgba(139,94,60,0.3);transform:scale(1.1)' : '')">
                                    <span class="text-xs font-bold" style="color:#5b4636">A</span>
                                </span>
                                <span class="text-[10px] font-semibold"
                                      :style="readerTheme === 'sepia' ? 'color:#8b5e3c' : readerTheme === 'dark' ? 'color:#8888aa' : ''">{{ __('app.reader_theme_sepia') }}</span>
                            </button>
                            <button type="button"
                                    @pointerup.stop.prevent="pickTheme('dark')"
                                    @keyup.enter.prevent="pickTheme('dark')"
                                    @keyup.space.prevent="pickTheme('dark')"
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
                    </template>

                    {{-- Bottom toolbar — always stays in place --}}
                    <div class="border-t safe-area-bottom"
                         :class="{ 'pointer-events-none': shelfTapLock }"
                         :style="readerTheme === 'default' ? 'background-color:#ffffff;border-color:#d7e3ea' : readerTheme === 'sepia' ? 'background-color:#ede3cc;border-color:#d4c5a9' : 'background-color:#16162a;border-color:#2a2a4a'">
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
                            <button type="button"
                                    @pointerup.stop.prevent="toggleShelf('theme')"
                                    @keyup.enter.prevent="toggleShelf('theme')"
                                    @keyup.space.prevent="toggleShelf('theme')"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :class="{
                                        'text-secondary hover:bg-muted': readerTheme === 'default' && activeShelf !== 'theme',
                                        'text-accent bg-accent/10': readerTheme === 'default' && activeShelf === 'theme'
                                    }"
                                    :style="readerTheme === 'sepia' ? (activeShelf === 'theme' ? 'color:#8b5e3c;background-color:#d4c5a9' : 'color:#5b4636') : readerTheme === 'dark' ? (activeShelf === 'theme' ? 'color:#7b9fff;background-color:#2a2a4a' : 'color:#c0c0d0') : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                                <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.reader_theme') }}</span>
                            </button>

                            {{-- Font toggle --}}
                            <button type="button"
                                    @pointerup.stop.prevent="toggleShelf('font')"
                                    @keyup.enter.prevent="toggleShelf('font')"
                                    @keyup.space.prevent="toggleShelf('font')"
                                    class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation"
                                    :class="{
                                        'text-secondary hover:bg-muted': readerTheme === 'default' && activeShelf !== 'font',
                                        'text-accent bg-accent/10': readerTheme === 'default' && activeShelf === 'font'
                                    }"
                                    :style="readerTheme === 'sepia' ? (activeShelf === 'font' ? 'color:#8b5e3c;background-color:#d4c5a9' : 'color:#5b4636') : readerTheme === 'dark' ? (activeShelf === 'font' ? 'color:#7b9fff;background-color:#2a2a4a' : 'color:#c0c0d0') : ''">
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
        </template>
        @endif

        @endif
    </div>
    @endif

    {{-- Lectionary (ግጻዌ) --}}
    @if(!$isHimamatDaily && isset($lectionary) && $lectionary && $lectionary->hasContent())
    @php
    $lecReadings = [
        ['key'=>'pauline','num'=>1,'label_key'=>'app.lectionary_pauline',
         'book'   =>$locale==='am'?$lectionary->pauline_book_am:$lectionary->pauline_book_en,
         'chapter'=>$lectionary->pauline_chapter,'verses'=>$lectionary->pauline_verses,
         'text'   =>$locale==='am'?$lectionary->pauline_text_am:$lectionary->pauline_text_en,
         'has'    =>filled($lectionary->pauline_book_am)||filled($lectionary->pauline_chapter)],
        ['key'=>'catholic','num'=>2,'label_key'=>'app.lectionary_catholic',
         'book'   =>$locale==='am'?$lectionary->catholic_book_am:$lectionary->catholic_book_en,
         'chapter'=>$lectionary->catholic_chapter,'verses'=>$lectionary->catholic_verses,
         'text'   =>$locale==='am'?$lectionary->catholic_text_am:$lectionary->catholic_text_en,
         'has'    =>filled($lectionary->catholic_book_am)||filled($lectionary->catholic_chapter)],
        ['key'=>'acts','num'=>3,'label_key'=>'app.lectionary_acts',
         'book'   =>$locale==='am'?'የሐዋርያት ሥራ':'Acts',
         'chapter'=>$lectionary->acts_chapter,'verses'=>$lectionary->acts_verses,
         'text'   =>$locale==='am'?$lectionary->acts_text_am:$lectionary->acts_text_en,
         'has'    =>filled($lectionary->acts_chapter)],
        ['key'=>'mesbak','num'=>4,'label_key'=>'app.lectionary_mesbak',
         'book'   =>$locale==='am'?'መዝሙረ ዳዊት':'Psalm',
         'chapter'=>$lectionary->mesbak_psalm,'verses'=>$lectionary->mesbak_verses,
         'text'   =>null,'has'=>filled($lectionary->mesbak_psalm)],
        ['key'=>'gospel','num'=>5,'label_key'=>'app.lectionary_gospel',
         'label'  =>$locale==='am'
             ? (filled($lectionary->gospel_book_am) ? 'የ'.$lectionary->gospel_book_am.' ወንጌል' : __('app.lectionary_gospel'))
             : (filled($lectionary->gospel_book_en) ? $lectionary->gospel_book_en.' Gospel'    : __('app.lectionary_gospel')),
         'book'   =>$locale==='am'?$lectionary->gospel_book_am:$lectionary->gospel_book_en,
         'chapter'=>$lectionary->gospel_chapter,'verses'=>$lectionary->gospel_verses,
         'text'   =>$locale==='am'?$lectionary->gospel_text_am:$lectionary->gospel_text_en,
         'has'    =>filled($lectionary->gospel_book_am)||filled($lectionary->gospel_chapter)],
        ['key'=>'qiddase','num'=>6,'label_key'=>'app.lectionary_qiddase',
         'label'  =>$locale==='am'
             ? (filled($lectionary->qiddase_am) ? $lectionary->qiddase_am : __('app.lectionary_qiddase'))
             : (filled($lectionary->qiddase_en) ? $lectionary->qiddase_en : __('app.lectionary_qiddase')),
         'book'   =>null,'chapter'=>null,'verses'=>null,
         'text'   =>$locale==='am'?$lectionary->qiddase_am:$lectionary->qiddase_en,
         'has'    =>filled($lectionary->qiddase_am)||filled($lectionary->qiddase_en)],
    ];
    @endphp
    <div class="bg-card rounded-2xl shadow-sm border border-border overflow-hidden"
         x-data="{
            readOpen: false,
            openSections: [],
            allExpanded: false,
            fsOpenSections: [],
            fsAllExpanded: false,
            availableKeys: {{ Js::from(collect($lecReadings)->where('has', true)->pluck('key')->values()) }},
            fontSize: parseInt(localStorage.getItem('lecFontSize') || '16'),
            readerTheme: localStorage.getItem('lecReaderTheme') || 'sepia',
            readerFont: localStorage.getItem('lecReaderFont') || 'default',
            fullscreen: false,
            activeShelf: null,
            shelfTapLock: false,
            shelfTapLockTimer: null,
            isSectionOpen(key) { return this.openSections.includes(key); },
            toggleSection(key) {
                if (this.isSectionOpen(key)) { this.openSections = []; this.allExpanded = false; }
                else if (this.allExpanded) { this.openSections.push(key); }
                else { this.openSections = [key]; }
            },
            toggleAll() {
                if (this.allExpanded) { this.openSections = []; this.allExpanded = false; }
                else { this.openSections = [...this.availableKeys]; this.allExpanded = true; }
            },
            isFsSectionOpen(key) { return this.fsOpenSections.includes(key); },
            toggleFsSection(key) {
                if (this.isFsSectionOpen(key)) { this.fsOpenSections = []; this.fsAllExpanded = false; }
                else if (this.fsAllExpanded) { this.fsOpenSections.push(key); }
                else { this.fsOpenSections = [key]; }
            },
            toggleFsAll() {
                if (this.fsAllExpanded) { this.fsOpenSections = []; this.fsAllExpanded = false; }
                else { this.fsOpenSections = [...this.availableKeys]; this.fsAllExpanded = true; }
            },
            lockShelfTap(ms=650){ this.shelfTapLock=true; if(this.shelfTapLockTimer) clearTimeout(this.shelfTapLockTimer); this.shelfTapLockTimer=setTimeout(()=>{this.shelfTapLock=false;this.shelfTapLockTimer=null;},ms); },
            toggleShelf(n){ if(this.shelfTapLock) return; this.activeShelf=this.activeShelf===n?null:n; },
            pickTheme(t){ this.readerTheme=t; localStorage.setItem('lecReaderTheme',t); this.activeShelf=null; this.lockShelfTap(); },
            pickFont(f){ this.readerFont=f; localStorage.setItem('lecReaderFont',f); this.activeShelf=null; this.lockShelfTap(); },
            fontFamily(){ if(this.readerFont==='benaiah') return 'Benaiah,sans-serif'; if(this.readerFont==='kiros') return 'Kiros,sans-serif'; if(this.readerFont==='handwriting') return 'Handwriting,sans-serif'; return 'inherit'; },
            setFontSize(s){ this.fontSize=Math.min(28,Math.max(12,s)); localStorage.setItem('lecFontSize',this.fontSize); },
            openFullscreen(){ this.fullscreen=true; this.fsOpenSections=[...this.openSections]; this.fsAllExpanded=this.allExpanded; document.body.style.overflow='hidden'; const n=document.querySelector('nav.fixed.bottom-0'); if(n) n.style.display='none'; },
            closeFullscreen(){ this.fullscreen=false; this.activeShelf=null; this.shelfTapLock=false; if(this.shelfTapLockTimer){clearTimeout(this.shelfTapLockTimer);this.shelfTapLockTimer=null;} document.body.style.overflow=''; const n=document.querySelector('nav.fixed.bottom-0'); if(n) n.style.display=''; }
         }"
         @keydown.escape.window="if(fullscreen) closeFullscreen()">

        {{-- Card header --}}
        <div class="px-4 pt-4 pb-3">
            <h3 class="font-semibold text-sm text-accent mb-1">{{ __('app.lectionary') }}</h3>
            @if(filled($lectionary->title_am) || filled($lectionary->title_en))
            <p class="font-medium text-primary">{{ $locale === 'am' ? $lectionary->title_am : $lectionary->title_en }}</p>
            @endif
            @if(filled($lectionary->description_am) || filled($lectionary->description_en))
            <p class="text-sm text-muted-text mt-1.5 leading-relaxed">{{ $locale === 'am' ? $lectionary->description_am : $lectionary->description_en }}</p>
            @endif
        </div>

        {{-- Read button --}}
        <div class="px-4 pb-4">
            <button type="button" @click="readOpen = !readOpen"
                    class="w-full flex items-center justify-between gap-2 py-2.5 px-3 rounded-xl bg-muted/70 hover:bg-muted transition mb-3">
                <div class="flex items-center gap-1.5 min-w-0">
                    <svg class="w-4 h-4 shrink-0 transition-transform duration-200" :class="readOpen ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <div class="min-w-0">
                        <span class="text-sm font-semibold text-primary">{{ __('app.read') }}</span>
                        <p x-show="!readOpen" class="text-[11px] text-muted-text mt-0.5">
                            {{ $locale === 'am' ? 'ለማንበብ እዚህ ላይ ይንኩ' : 'Click here to read' }}
                        </p>
                    </div>
                </div>
                <span x-show="readOpen" class="text-[11px] font-semibold text-muted-text uppercase tracking-wider shrink-0">{{ __('app.close') }}</span>
            </button>

            {{-- Summary list (shown when collapsed) --}}
            <div x-show="!readOpen" class="divide-y divide-border/60 rounded-xl border border-border overflow-hidden">
                @foreach($lecReadings as $r)
                @if($r['has'])
                <div class="flex items-center px-3 py-2.5 gap-2">
                    <span class="text-xs font-bold text-muted-text w-4 shrink-0">{{ $r['num'] }}</span>
                    <div class="min-w-0">
                        <span class="text-xs font-semibold text-primary">{{ $r['label'] ?? __($r['label_key']) }}</span>
                        @if(filled($r['book']))
                        <span class="text-[11px] text-muted-text ml-1.5">{{ $r['book'] }}{{ filled($r['chapter']) ? ' '.$r['chapter'] : '' }}{{ filled($r['verses']) ? ':'.$r['verses'] : '' }}</span>
                        @endif
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            {{-- Inline expanded reader --}}
            <div x-show="readOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-2">

                {{-- Toolbar --}}
                <div class="flex items-center justify-between gap-2 py-2 px-3 rounded-xl bg-muted/60">
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="setFontSize(fontSize-2)" :disabled="fontSize<=12" :class="fontSize<=12&&'opacity-30 cursor-not-allowed'"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14"/></svg>
                        </button>
                        <span class="text-xs font-bold text-primary tabular-nums w-6 text-center" x-text="fontSize"></span>
                        <button type="button" @click="setFontSize(fontSize+2)" :disabled="fontSize>=28" :class="fontSize>=28&&'opacity-30 cursor-not-allowed'"
                                class="w-7 h-7 rounded-lg bg-card border border-border flex items-center justify-center text-secondary hover:bg-muted transition touch-manipulation">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="toggleAll()" :class="allExpanded?'bg-accent border-accent text-on-accent':'bg-card border-border text-secondary hover:bg-muted'"
                                class="h-7 px-2.5 rounded-lg border transition touch-manipulation flex items-center gap-1 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <span x-text="allExpanded ? '{{ $locale === 'am' ? 'ዝጋ' : 'Collapse' }}' : '{{ $locale === 'am' ? 'ሁሉንም' : 'All' }}'"></span>
                        </button>
                        <div class="relative" x-data="{fo:false}" @click.outside="fo=false">
                            <button type="button" @click="fo=!fo" :class="fo?'bg-accent border-accent text-on-accent':'bg-card border-border text-secondary hover:bg-muted'"
                                    class="h-7 px-2.5 rounded-lg border transition touch-manipulation flex items-center gap-1">
                                <span class="text-[13px] font-bold" :style="readerFont==='benaiah'?'font-family:Benaiah,sans-serif':readerFont==='kiros'?'font-family:Kiros,sans-serif':readerFont==='handwriting'?'font-family:Handwriting,sans-serif':''">ሀ</span>
                                <svg class="w-2.5 h-2.5 opacity-60 transition-transform" :class="fo&&'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <div x-show="fo" x-transition x-cloak class="absolute right-0 top-full mt-1.5 w-44 bg-card border border-border rounded-xl shadow-xl overflow-hidden z-50" style="display:none">
                                @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Handwriting','Handwriting,sans-serif']] as [$fv,$fl,$ff])
                                <button type="button" @click="fo=false;pickFont('{{ $fv }}')" :class="readerFont==='{{ $fv }}'?'bg-accent/10':'hover:bg-muted'"
                                        class="w-full px-3 py-2.5 text-left flex items-center gap-3 border-b border-border last:border-0 touch-manipulation">
                                    <span class="text-lg font-bold" style="font-family:{{ $ff }}">ሀ</span>
                                    <span class="text-sm" :class="readerFont==='{{ $fv }}'?'text-accent font-semibold':'text-primary'">{{ $fl }}</span>
                                </button>
                                @endforeach
                            </div>
                        </div>
                        <button type="button" @click="openFullscreen()"
                                class="h-7 px-2.5 rounded-lg bg-card border border-border text-secondary hover:bg-muted transition touch-manipulation flex items-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                        </button>
                    </div>
                </div>

                {{-- Reading accordion --}}
                <div class="space-y-2">
                    @foreach($lecReadings as $r)
                    @if($r['has'])
                    <div x-ref="sec_{{ $r['key'] }}" class="rounded-xl border transition-all duration-200 overflow-hidden"
                         :class="isSectionOpen('{{ $r['key'] }}') ? 'border-accent/30 bg-accent/[0.03] shadow-sm' : 'border-border bg-card'">
                        <button type="button" @click="toggleSection('{{ $r['key'] }}')"
                                class="w-full flex items-center justify-between px-3.5 py-3 text-left transition-colors touch-manipulation"
                                :class="isSectionOpen('{{ $r['key'] }}') ? '' : 'hover:bg-muted/40'">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-colors duration-200"
                                      :class="isSectionOpen('{{ $r['key'] }}') ? 'bg-accent text-on-accent' : 'bg-muted text-muted-text'">{{ $r['num'] }}</span>
                                <div>
                                    <span class="text-sm font-semibold transition-colors duration-200"
                                          :class="isSectionOpen('{{ $r['key'] }}') ? 'text-accent' : 'text-primary'">{{ $r['label'] ?? __($r['label_key']) }}</span>
                                    @if(filled($r['book']))
                                    <span class="block text-xs text-muted-text mt-0.5">{{ $r['book'] }}{{ filled($r['chapter'])?' '.$r['chapter']:'' }}{{ filled($r['verses'])?':'.$r['verses']:'' }}</span>
                                    @endif
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-muted-text shrink-0 transition-transform duration-300" :class="isSectionOpen('{{ $r['key'] }}')&&'rotate-180 text-accent'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="isSectionOpen('{{ $r['key'] }}')" x-cloak
                             x-transition:enter="transition-all ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                             x-transition:leave="transition-all ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                            <div class="px-3.5 pb-4 pt-1 text-primary"
                                 :style="'font-size:'+fontSize+'px;line-height:'+(fontSize<20?'1.85':'1.75')+';font-family:'+fontFamily()">
                                @if($r['key']==='mesbak')
                                    @if(filled($lectionary->mesbak_geez_1)||filled($lectionary->mesbak_geez_2)||filled($lectionary->mesbak_geez_3))
                                    <div class="mb-4">
                                        @if(filled($lectionary->mesbak_geez_1))
                                        <p class="mb-1"><span class="font-semibold">፩</span> {{ $lectionary->mesbak_geez_1 }}</p>
                                        @endif
                                        @if(filled($lectionary->mesbak_geez_2))
                                        <p class="mb-1"><span class="font-semibold">፪</span> {{ $lectionary->mesbak_geez_2 }}</p>
                                        @endif
                                        @if(filled($lectionary->mesbak_geez_3))
                                        <p><span class="font-semibold">፫</span> {{ $lectionary->mesbak_geez_3 }}</p>
                                        @endif
                                    </div>
                                    @endif
                                    @php $mt=$locale==='am'?$lectionary->mesbak_text_am:$lectionary->mesbak_text_en; @endphp
                                    @if(filled($mt))
                                    <div class="whitespace-pre-wrap">{{ $mt }}</div>
                                    @endif
                                @elseif(filled($r['text']))
                                    <div class="whitespace-pre-wrap">{{ $r['text'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Fullscreen reader --}}
        <template x-if="fullscreen">
            <div class="fixed inset-0 z-[100] flex flex-col bg-surface"
                 :class="readerTheme==='sepia'?'theme-sepia':readerTheme==='dark'?'dark':'theme-light'"
                 :style="readerTheme==='sepia'?'--color-accent:#78560D;--color-accent-hover:#614409;--app-accent:#78560D;--app-accent-hover:#614409':''">

                <div class="flex-1 overflow-y-auto">
                    {{-- Sticky header --}}
                    <div class="sticky top-0 z-10 px-4 py-3 border-b border-border bg-card flex items-center gap-3">
                        <button type="button" @click="closeFullscreen()" class="w-8 h-8 rounded-lg flex items-center justify-center text-accent touch-manipulation">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-accent">{{ __('app.lectionary') }}</p>
                            @if(filled($lectionary->title_am)||filled($lectionary->title_en))
                            <p class="text-sm font-semibold mt-0.5 text-primary">
                                {{ $locale==='am'?$lectionary->title_am:$lectionary->title_en }}
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- Expand all toggle --}}
                    <div class="px-4 py-2 flex justify-end">
                        <button type="button" @click="toggleFsAll()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition touch-manipulation"
                                :class="fsAllExpanded?'text-accent bg-accent/10':'text-muted-text'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <span x-text="fsAllExpanded ? '{{ $locale === 'am' ? 'ዝጋ ሁሉንም' : 'Collapse All' }}' : '{{ $locale === 'am' ? 'ሁሉንም ክፈት' : 'Expand All' }}'"></span>
                        </button>
                    </div>

                    {{-- Sections --}}
                    <div class="max-w-2xl mx-auto px-3 pb-8 space-y-2.5">
                        @foreach($lecReadings as $r)
                        @if($r['has'])
                        <div x-ref="fssec_{{ $r['key'] }}" class="rounded-xl overflow-hidden transition-all duration-200 border"
                             :class="isFsSectionOpen('{{ $r['key'] }}') ? 'border-accent/30 bg-accent/[0.03] shadow-sm' : 'border-border bg-card'">
                            <button type="button" @click="toggleFsSection('{{ $r['key'] }}')"
                                    class="w-full flex items-center justify-between px-4 py-3.5 text-left touch-manipulation">
                                <div class="flex items-center gap-3">
                                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-all duration-200"
                                          :class="isFsSectionOpen('{{ $r['key'] }}') ? 'bg-accent text-on-accent' : 'bg-muted text-muted-text'">{{ $r['num'] }}</span>
                                    <div>
                                        <span class="text-sm font-bold transition-colors duration-200"
                                              :class="isFsSectionOpen('{{ $r['key'] }}') ? 'text-accent' : 'text-primary'">
                                            {{ $r['label'] ?? __($r['label_key']) }}
                                        </span>
                                        @if(filled($r['book']))
                                        <span class="block text-xs mt-0.5 text-muted-text">
                                            {{ $r['book'] }}{{ filled($r['chapter'])?' '.$r['chapter']:'' }}{{ filled($r['verses'])?':'.$r['verses']:'' }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <svg class="w-5 h-5 shrink-0 transition-transform duration-300 text-muted-text"
                                     :class="isFsSectionOpen('{{ $r['key'] }}')&&'rotate-180 !text-accent'"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="isFsSectionOpen('{{ $r['key'] }}')" x-cloak
                                 x-transition:enter="transition-all ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                 x-transition:leave="transition-all ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                                <div class="px-4 pb-4 pt-1 text-primary"
                                     :style="'font-size:'+fontSize+'px;line-height:'+(fontSize<20?'1.9':'1.8')+';font-family:'+fontFamily()">
                                    @if($r['key']==='mesbak')
                                        @if(filled($lectionary->mesbak_geez_1)||filled($lectionary->mesbak_geez_2)||filled($lectionary->mesbak_geez_3))
                                        <div class="mb-5">
                                            @if(filled($lectionary->mesbak_geez_1))
                                            <p class="mb-1"><span class="font-semibold">፩</span> {{ $lectionary->mesbak_geez_1 }}</p>
                                            @endif
                                            @if(filled($lectionary->mesbak_geez_2))
                                            <p class="mb-1"><span class="font-semibold">፪</span> {{ $lectionary->mesbak_geez_2 }}</p>
                                            @endif
                                            @if(filled($lectionary->mesbak_geez_3))
                                            <p><span class="font-semibold">፫</span> {{ $lectionary->mesbak_geez_3 }}</p>
                                            @endif
                                        </div>
                                        @endif
                                        @php $mt=$locale==='am'?$lectionary->mesbak_text_am:$lectionary->mesbak_text_en; @endphp
                                        @if(filled($mt))
                                        <div class="whitespace-pre-wrap">{{ $mt }}</div>
                                        @endif
                                    @elseif(filled($r['text']))
                                        <div class="whitespace-pre-wrap">{{ $r['text'] }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>

                {{-- Font shelf --}}
                <template x-if="activeShelf==='font'">
                    <div class="absolute bottom-16 left-0 right-0 border-t border-border bg-card px-4 py-4 z-[101]">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            @foreach([['default','Default','inherit'],['benaiah','Benaiah','Benaiah,sans-serif'],['kiros','Kiros','Kiros,sans-serif'],['handwriting','Writing','Handwriting,sans-serif']] as [$fv,$fl,$ff])
                            <button type="button" @pointerup.stop.prevent="pickFont('{{ $fv }}')" class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-12 h-12 rounded-xl flex items-center justify-center text-xl font-bold text-primary transition-all border-2 border-border bg-card" style="font-family:{{ $ff }}"
                                      :class="readerFont==='{{ $fv }}'&&'!border-accent !border-3 scale-110'">ሀ</span>
                                <span class="text-[10px] font-semibold text-muted-text" :class="readerFont==='{{ $fv }}'&&'!text-accent'">{{ $fl }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </template>

                {{-- Theme shelf --}}
                <template x-if="activeShelf==='theme'">
                    <div class="absolute bottom-16 left-0 right-0 border-t border-border bg-card px-4 py-4 z-[101]">
                        <div class="flex items-center justify-center gap-5 max-w-xs mx-auto">
                            @foreach([['light','A','#f9fafb','#111827','Light'],['sepia','A','#f5edd8','#1c1008','ብራና'],['dark','A','#030712','#f9fafb','Dark']] as [$tv,$tl,$tbg,$tc,$tlabel])
                            <button type="button" @pointerup.stop.prevent="pickTheme('{{ $tv }}')" class="flex flex-col items-center gap-1.5 touch-manipulation">
                                <span class="w-10 h-10 rounded-full flex items-center justify-center text-xs font-bold transition-all border-2 border-border"
                                      style="background-color:{{ $tbg }};color:{{ $tc }}"
                                      :class="readerTheme==='{{ $tv }}'&&'!border-accent !border-3 scale-110'">{{ $tl }}</span>
                                <span class="text-[10px] font-semibold text-muted-text" :class="readerTheme==='{{ $tv }}'&&'!text-accent'">{{ $tlabel }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>
                </template>

                {{-- Bottom toolbar --}}
                <div class="shrink-0 border-t border-border bg-card safe-area-bottom" :class="{'pointer-events-none':shelfTapLock}">
                    <div class="flex items-center justify-around h-16 max-w-lg mx-auto px-2">
                        <button type="button" @click="closeFullscreen()" class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-accent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.close') }}</span>
                        </button>
                        <button type="button" @click="setFontSize(fontSize-2)" :disabled="fontSize<=12" :class="fontSize<=12?'opacity-30 cursor-not-allowed':''"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary">
                            <span class="text-base font-bold leading-none">A</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M5 12h14"/></svg>
                        </button>
                        <div class="flex flex-col items-center gap-0.5 px-1">
                            <span class="text-sm font-bold tabular-nums text-primary" x-text="fontSize"></span>
                            <span class="text-[8px] font-semibold uppercase tracking-wider text-muted-text">{{ __('app.font_size') }}</span>
                        </div>
                        <button type="button" @click="setFontSize(fontSize+2)" :disabled="fontSize>=28" :class="fontSize>=28?'opacity-30 cursor-not-allowed':''"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary">
                            <span class="text-xl font-bold leading-none">A</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2.5" d="M12 5v14m-7-7h14"/></svg>
                        </button>
                        <button type="button" @pointerup.stop.prevent="toggleShelf('theme')"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary"
                                :class="activeShelf==='theme'&&'!text-accent bg-muted'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">{{ __('app.reader_theme') }}</span>
                        </button>
                        <button type="button" @pointerup.stop.prevent="toggleShelf('font')"
                                class="flex flex-col items-center gap-0.5 px-2 py-1.5 rounded-lg transition touch-manipulation text-secondary"
                                :class="activeShelf==='font'&&'!text-accent bg-muted'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                            <span class="text-[9px] font-semibold uppercase tracking-wider">Font</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
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
                            </a>
                        @endif
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    @endif

    {{-- Daily Message --}}
    @if(localized($daily, 'reflection'))
    @php
        $msgTitle = localized($daily, 'reflection_title');
        $msgText  = localized($daily, 'reflection');
    @endphp
    <div class="bg-card rounded-2xl shadow-sm border border-border p-4 text-center" x-data="{ expanded: false }">
        {{-- Label --}}
        <span class="text-xs font-bold tracking-wider uppercase text-accent">{{ __('app.daily_message') }}</span>

        {{-- Title --}}
        @if(filled($msgTitle))
        <h3 class="text-base font-bold text-primary mt-1">{{ $msgTitle }}</h3>
        @endif

        {{-- Divider --}}
        <div class="flex items-center justify-center gap-2 my-2.5">
            <span class="block w-8 h-px bg-accent/30"></span>
            <svg class="w-3.5 h-3.5 text-accent/50" viewBox="0 0 24 24" fill="currentColor"><path d="M6 17h3l2-4V7H5v6h3zm8 0h3l2-4V7h-6v6h3z"/></svg>
            <span class="block w-8 h-px bg-accent/30"></span>
        </div>

        {{-- Message body --}}
        <div class="relative text-left" :class="!expanded && 'max-h-24 overflow-hidden'">
            <p class="text-sm text-secondary leading-relaxed whitespace-pre-line">{{ $msgText }}</p>
            <div x-show="!expanded" class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-card to-transparent pointer-events-none"></div>
        </div>

        {{-- Read more --}}
        <button @click="expanded = !expanded" class="mt-3 inline-flex items-center gap-1 px-4 py-1.5 text-[11px] font-semibold text-accent bg-accent/10 rounded-full transition-colors hover:bg-accent/20">
            <span x-text="expanded ? '{{ __('app.show_less') }}' : '{{ __('app.read_more') }}'"></span>
            <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
        </button>
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
    @if(!$publicPreview && ! $guestAccess && ($activities->isNotEmpty() || ($customActivities ?? collect())->isNotEmpty() || $member))
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

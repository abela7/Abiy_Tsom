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

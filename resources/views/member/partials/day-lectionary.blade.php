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
            <h3 class="font-semibold text-sm text-accent mb-1">{{ $sectionTitle ?? __('app.lectionary') }}</h3>
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
                            <p class="text-xs font-bold uppercase tracking-wider text-accent">{{ $sectionTitle ?? __('app.lectionary') }}</p>
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

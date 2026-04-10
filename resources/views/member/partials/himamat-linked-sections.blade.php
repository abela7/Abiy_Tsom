@php
    $localizedHimamatTitle = localized($himamatDay, 'title') ?? $himamatDay->title_en;
    $localizedDayMeaning = trim((string) (localized($himamatDay, 'spiritual_meaning') ?? ''));
    $localizedRitualIntro = trim((string) (localized($himamatDay, 'ritual_guide_intro') ?? ''));
    $slotLabelKeys = [
        'intro' => 'app.himamat_slot_7am',
        'third' => 'app.himamat_slot_9am',
        'sixth' => 'app.himamat_slot_12pm',
        'ninth' => 'app.himamat_slot_3pm',
        'eleventh' => 'app.himamat_slot_5pm',
    ];
    $slotTimeShort = [
        'intro' => '7:00',
        'third' => '9:00',
        'sixth' => '12:00',
        'ninth' => '3:00',
        'eleventh' => '5:00',
    ];
    $slotTimePeriod = [
        'intro' => 'AM',
        'third' => 'AM',
        'sixth' => 'PM',
        'ninth' => 'PM',
        'eleventh' => 'PM',
    ];
    $clockIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>';
    $slotIcons = [
        'intro'    => $clockIcon,
        'third'    => $clockIcon,
        'sixth'    => $clockIcon,
        'ninth'    => $clockIcon,
        'eleventh' => $clockIcon,
    ];
    $resourceTypeOrder = ['text', 'video', 'photo', 'pdf', 'website'];
@endphp

{{-- ══════════════════════════════════════════════════════════════════════
     HIMAMAT BANNER — Compact hero header
     ══════════════════════════════════════════════════════════════════════ --}}
{{-- ══════════════════════════════════════════════════════════════════════
     Day Theme & Meaning — Full-width standalone card
     ══════════════════════════════════════════════════════════════════════ --}}
@if($localizedDayMeaning !== '')
<div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-border/60 bg-muted/30">
        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </div>
        <h3 class="text-sm font-semibold leading-snug text-primary sm:text-base">{{ $localizedHimamatTitle }}</h3>
    </div>
    <div class="px-4 py-4 sm:px-5">
        <p class="text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedDayMeaning }}</p>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     Ritual Guide / Introduction — Full-width standalone card
     ══════════════════════════════════════════════════════════════════════ --}}
@if($localizedRitualIntro !== '')
@php
    $hasLongRitualIntro = \Illuminate\Support\Str::length($localizedRitualIntro) > 280;
@endphp
<div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden" x-data="{ expanded: false }">
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-border/60 bg-muted/30">
        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }}</h3>
    </div>
    <div class="px-4 py-4 sm:px-5">
        <div class="relative text-left" :class="!expanded && 'max-h-24 overflow-hidden'">
            <p class="text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedRitualIntro }}</p>
            @if($hasLongRitualIntro)
                <div x-show="!expanded" class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-card to-transparent pointer-events-none"></div>
            @endif
        </div>

        @if($hasLongRitualIntro)
            <button @click="expanded = !expanded"
                    class="mt-3 inline-flex items-center gap-1 px-4 py-1.5 text-[11px] font-semibold text-accent bg-accent/10 rounded-full transition-colors hover:bg-accent/20">
                <span x-text="expanded ? '{{ __('app.show_less') }}' : '{{ __('app.read_more') }}'"></span>
                <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
        @endif
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     HIMAMAT TIMELINE — Prayer hours with expandable slots & resources
     ══════════════════════════════════════════════════════════════════════ --}}
@if($himamatTimeline && $himamatDay->slots->isNotEmpty())

    {{-- Timeline Section Header --}}
    <div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
        <div class="px-5 py-4 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-accent/20 to-accent/5 flex items-center justify-center shrink-0">
                <svg class="w-4.5 h-4.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h2 class="text-[15px] font-bold text-primary">{{ app()->getLocale() === 'am' ? 'የቀኑ ዋና ዋና ሰዓታት' : 'Key Hours of the Day' }}</h2>
                <p class="text-[11px] text-muted-text mt-0.5">{{ $localizedHimamatTitle }}</p>
            </div>
        </div>
    </div>

    <section x-data="{
                openSlot: null,
                hashHandler: null,
                slotKeyFromHash(hashValue = null) {
                    const normalizedHash = (hashValue ?? window.location.hash ?? '').replace(/^#/, '').trim();

                    if (!normalizedHash.startsWith('himamat-slot-')) {
                        return null;
                    }

                    return normalizedHash.replace(/^himamat-slot-/, '') || null;
                },
                scrollToSlot(slotKey, behavior = 'smooth') {
                    const target = this.$root.querySelector(`[data-slot-key='${slotKey}']`);

                    if (!target) {
                        return;
                    }

                    target.scrollIntoView({ behavior, block: 'start' });
                },
                openFromHash(hashValue = null, behavior = 'auto') {
                    const slotKey = this.slotKeyFromHash(hashValue);

                    if (!slotKey) {
                        return;
                    }

                    this.openSlot = slotKey;
                    this.$nextTick(() => {
                        window.setTimeout(() => this.scrollToSlot(slotKey, behavior), 80);
                    });
                },
                setOpenSlot(slotKey) {
                    const nextSlot = this.openSlot === slotKey ? null : slotKey;
                    this.openSlot = nextSlot;

                    if (nextSlot) {
                        if (window.location.hash !== `#himamat-slot-${nextSlot}`) {
                            history.replaceState(null, '', `#himamat-slot-${nextSlot}`);
                        }

                        this.$nextTick(() => {
                            window.setTimeout(() => this.scrollToSlot(nextSlot, 'smooth'), 80);
                        });
                        return;
                    }

                    if (window.location.hash === `#himamat-slot-${slotKey}`) {
                        history.replaceState(null, '', window.location.pathname + window.location.search);
                    }
                },
                init() {
                    this.openFromHash(window.location.hash, 'auto');

                    this.hashHandler = () => this.openFromHash(window.location.hash, 'smooth');
                    window.addEventListener('hashchange', this.hashHandler);
                },
                destroy() {
                    if (this.hashHandler) {
                        window.removeEventListener('hashchange', this.hashHandler);
                    }
                }
             }">

        {{-- Timeline with left rail --}}
        <div class="relative">
            @foreach($himamatTimeline['items'] as $item)
                @php
                    $slot = $item['slot'];
                    $state = $item['temporal_state'];
                    $slotKey = $slot->slot_key;
                    $slotHourLabel = __($slotLabelKeys[$slotKey] ?? 'app.himamat_day_view_title');
                    $localizedHeader = localized($slot, 'slot_header') ?? $slot->slot_header_en ?? $slotHourLabel;
                    $localizedReadingRef = trim((string) (localized($slot, 'reading_reference') ?? ''));
                    $localizedReading = trim((string) (localized($slot, 'reading_text') ?? ''));
                    $resourcesByType = $slot->resources->groupBy('type');
                    $availableResourceTypes = collect($resourceTypeOrder)
                        ->filter(fn (string $type): bool => $resourcesByType->has($type))
                        ->values();
                    $defaultResourceType = $availableResourceTypes->first();
                    $timeShort = $slotTimeShort[$slotKey] ?? '';
                    $timePeriod = $slotTimePeriod[$slotKey] ?? '';
                    $iconPath = $slotIcons[$slotKey] ?? $slotIcons['intro'];

                    $cardBorder = match ($state) {
                        'current' => 'border-accent/40 shadow-md shadow-accent/5',
                        'past' => 'border-border/60',
                        default => 'border-border',
                    };
                    $cardBg = match ($state) {
                        'current' => 'bg-gradient-to-br from-accent/[0.04] to-transparent',
                        'past' => 'bg-card/80',
                        default => 'bg-card',
                    };
                    $nodeColor = match ($state) {
                        'current' => 'bg-accent shadow-sm shadow-accent/30',
                        'past' => 'bg-accent/40',
                        default => 'bg-border',
                    };
                    $lineColor = match ($state) {
                        'current' => 'bg-accent/30',
                        'past' => 'bg-accent/20',
                        default => 'bg-border/60',
                    };
                @endphp

                <div class="group relative flex gap-4">
                    {{-- Left timeline rail --}}
                    <div class="flex flex-col items-center w-6 shrink-0 pt-1">
                        {{-- Node dot --}}
                        <div data-timeline-node data-slot-state="{{ $state }}" class="relative z-10 w-3 h-3 rounded-full {{ $nodeColor }} mt-5 transition-all duration-300">
                            @if($state === 'current')
                                <span class="absolute inset-0 rounded-full bg-accent animate-ping opacity-40"></span>
                            @endif
                        </div>
                        {{-- Vertical line --}}
                        @if(!$loop->last)
                            <div data-timeline-line class="flex-1 w-[2px] {{ $lineColor }} mt-1 rounded-full"></div>
                        @endif
                    </div>

                    {{-- Card --}}
                    <div class="flex-1 pb-4 min-w-0">
                        <article id="himamat-slot-{{ $slotKey }}" data-slot-key="{{ $slotKey }}"
                                 class="relative rounded-2xl border transition-all duration-300 overflow-hidden {{ $cardBorder }} {{ $cardBg }}"
                                 :class="openSlot === '{{ $slotKey }}' ? 'ring-1 ring-accent/20 border-accent/30' : ''">

                            {{-- Slot Header (Button) --}}
                            <button type="button"
                                    @click="setOpenSlot('{{ $slotKey }}')"
                                    class="w-full text-left group/btn touch-manipulation">

                                {{-- Top bar: time badge + icon + chevron --}}
                                <div class="flex items-center gap-3 px-4 pt-3.5 pb-2">
                                    {{-- Time badge --}}
                                    <div class="flex items-baseline gap-0.5 tabular-nums">
                                        <span class="text-lg font-black text-accent leading-none">{{ $timeShort }}</span>
                                        <span class="text-[9px] font-bold text-accent/60 uppercase">{{ $timePeriod }}</span>
                                    </div>

                                    {{-- Slot icon --}}
                                    <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0
                                                {{ $state === 'current' ? 'bg-accent/15' : 'bg-muted/60' }}">
                                        <svg class="w-3.5 h-3.5 {{ $state === 'current' ? 'text-accent' : 'text-muted-text' }}"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iconPath !!}</svg>
                                    </div>

                                    {{-- Hour label pill --}}
                                    <span class="text-[10px] font-semibold uppercase tracking-wider px-2 py-0.5 rounded-full
                                                 {{ $state === 'current' ? 'bg-accent/10 text-accent' : 'text-muted-text' }}">{{ $slotHourLabel }}</span>

                                    <div class="ml-auto shrink-0 text-muted-text transition-colors group-hover/btn:text-accent">
                                        <svg class="w-4.5 h-4.5 transition-transform duration-300"
                                             :class="openSlot === '{{ $slotKey }}' ? 'rotate-180 text-accent' : ''"
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Title & reading ref --}}
                                <div class="px-4 pb-3.5">
                                    <h3 class="text-[15px] sm:text-base font-bold text-primary leading-snug group-hover/btn:text-accent transition-colors">{{ $localizedHeader }}</h3>

                                    @if($localizedReadingRef !== '')
                                        <p class="mt-1 text-[13px] text-secondary/80 flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-accent/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                            <span>{{ $localizedReadingRef }}</span>
                                        </p>
                                    @endif
                                </div>
                            </button>

                            {{-- Expanded content --}}
                            <div x-show="openSlot === '{{ $slotKey }}'"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="border-t border-border/30">

                                {{-- Reading Text --}}
                                <div class="px-4 pt-4 pb-3">
                                    @if($localizedReading !== '')
                                        <div class="relative rounded-xl bg-muted/30 px-4 py-4 border border-border/30">
                                            <div class="absolute top-3 left-3 text-accent/15">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                                            </div>
                                            <p class="text-[15px] sm:text-base leading-[2] text-primary/90 whitespace-pre-line break-words pl-4">{{ $localizedReading }}</p>
                                        </div>
                                    @else
                                        <div class="flex items-center gap-2 px-3 py-3 rounded-xl bg-muted/30 border border-dashed border-border/50">
                                            <svg class="w-4 h-4 text-muted-text/60 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <p class="text-sm text-muted-text italic">{{ __('app.himamat_slot_content_pending') }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Optional Resources --}}
                                @if($availableResourceTypes->isNotEmpty())
                                    <div class="mx-4 mb-4 pt-3 border-t border-border/30" x-data="{ resourceTab: '{{ $defaultResourceType }}' }">

                                        {{-- Resource Tabs --}}
                                        <div class="flex gap-1.5 overflow-x-auto no-scrollbar mb-4">
                                            @foreach($availableResourceTypes as $type)
                                                <button @click="resourceTab = '{{ $type }}'"
                                                        class="text-[10px] font-bold uppercase tracking-wider px-3 py-1.5 rounded-full transition-all whitespace-nowrap touch-manipulation"
                                                        :class="resourceTab === '{{ $type }}' ? 'bg-accent text-white shadow-sm' : 'bg-muted/50 text-muted-text hover:bg-muted'">
                                                    {{ __('app.himamat_resource_type_'.$type) }}
                                                    <span class="opacity-70 ml-0.5">{{ $resourcesByType->get($type)->count() }}</span>
                                                </button>
                                            @endforeach
                                        </div>

                                        {{-- Tab Content --}}
                                        <div>
                                            @foreach($availableResourceTypes as $type)
                                                @php
                                                    $typeResources = $resourcesByType->get($type, collect());
                                                @endphp

                                                <div x-show="resourceTab === '{{ $type }}'"
                                                     x-cloak
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100">

                                                    @if($type === 'text')
                                                        <div class="space-y-3">
                                                            @foreach($typeResources as $resource)
                                                                @php
                                                                    $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                                    $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                                @endphp
                                                                <div class="rounded-xl bg-muted/30 border border-border/30 p-3.5">
                                                                    <h4 class="text-sm font-bold text-primary">{{ $resourceTitle }}</h4>
                                                                    @if($resourceText !== '')
                                                                        <p class="mt-2 text-sm leading-relaxed text-secondary whitespace-pre-line">{{ $resourceText }}</p>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @elseif($type === 'photo')
                                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                                            @foreach($typeResources as $resource)
                                                                @php
                                                                    $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                                    $resourceUrl = $resource->resolvedUrl();
                                                                @endphp
                                                                @if($resourceUrl)
                                                                    <a href="{{ $resourceUrl }}" target="_blank" rel="noopener"
                                                                       class="group block overflow-hidden rounded-xl bg-muted/30 border border-border/50 transition-all hover:border-accent/30 active:scale-[0.98]">
                                                                        <img src="{{ $resourceUrl }}" alt="{{ $resourceTitle }}" loading="lazy" class="w-full h-28 object-cover group-hover:scale-105 transition-transform duration-500">
                                                                        <div class="px-2.5 py-2">
                                                                            <p class="text-[11px] font-semibold text-primary truncate">{{ $resourceTitle }}</p>
                                                                        </div>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        {{-- video, pdf, website --}}
                                                        <div class="space-y-2">
                                                            @foreach($typeResources as $resource)
                                                                @php
                                                                    $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                                    $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                                    $resourceUrl = $resource->resolvedUrl();
                                                                @endphp
                                                                @if($resourceUrl)
                                                                    <a href="{{ $resourceUrl }}" target="_blank" rel="noopener"
                                                                       class="flex items-center justify-between gap-3 p-3 rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors group active:scale-[0.99] touch-manipulation">
                                                                        <div class="min-w-0">
                                                                            <p class="text-sm font-semibold text-primary truncate">{{ $resourceTitle }}</p>
                                                                            @if($resourceText !== '')
                                                                                <p class="text-xs text-secondary mt-0.5 truncate">{{ $resourceText }}</p>
                                                                            @endif
                                                                        </div>
                                                                        <svg class="w-4 h-4 text-accent/70 group-hover:text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                    </a>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif

                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </article>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif

@if($himamatDay->faqs->isNotEmpty())
@php
    $allFaqs    = $himamatDay->faqs;
    $previewFaq = $allFaqs->first();
    $modalFaqs  = $allFaqs->sortByDesc('id')->values();
    $isAmharic  = app()->getLocale() === 'am';
@endphp
<section x-data="{ showFaqModal: false, previewOpen: false, activeFaq: null }"
         @keydown.escape.window="showFaqModal = false; activeFaq = null">

    {{-- ── Preview Card ── --}}
    <div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">

        {{-- Header --}}
        <div class="flex items-center gap-3 px-5 py-4 border-b border-border/40">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-accent/20 to-accent/5 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 1.918-2 3.522-2 2.209 0 4 1.567 4 3.5 0 1.418-.964 2.638-2.347 3.188-.74.294-1.153.838-1.153 1.412V16m.01 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <h2 class="text-[15px] font-bold text-primary leading-snug">{{ $isAmharic ? 'በተደጋጋሚ የሚነሱ ጥያዎች' : 'Frequently Asked Questions' }}</h2>
                <p class="text-[11px] text-muted-text mt-0.5">{{ $allFaqs->count() }} {{ $isAmharic ? 'ጥያቄዎች' : 'questions' }}</p>
            </div>
        </div>

        {{-- Single preview FAQ --}}
        <div class="px-5 py-4">
            <button type="button" @click="previewOpen = !previewOpen"
                    class="w-full flex items-start gap-3 text-left group touch-manipulation">
                <div class="shrink-0 w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center text-[11px] font-bold text-accent mt-0.5">1</div>
                <span class="flex-1 text-[14px] sm:text-[15px] font-semibold text-primary leading-snug group-hover:text-accent transition-colors">
                    {{ localized($previewFaq, 'question') ?? $previewFaq->question_en }}
                </span>
                <svg class="w-4 h-4 text-muted-text mt-1 shrink-0 transition-transform duration-200 group-hover:text-accent"
                     :class="previewOpen && 'rotate-180'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="previewOpen" x-cloak x-collapse>
                <div class="mt-3 ml-10 rounded-xl bg-muted/40 border border-border/30 px-4 py-3.5">
                    <p class="text-[13px] sm:text-[14px] leading-[1.9] text-secondary whitespace-pre-line">
                        {{ localized($previewFaq, 'answer') ?? $previewFaq->answer_en }}
                    </p>
                </div>
            </div>
        </div>

        {{-- View More button --}}
        @if($allFaqs->count() > 1)
        <div class="px-5 pb-5">
            <button type="button" @click="showFaqModal = true"
                    class="w-full flex items-center justify-between gap-3 rounded-xl border border-accent/25 bg-accent/5 px-4 py-3.5 text-left transition hover:bg-accent/10 hover:border-accent/40 active:scale-[0.99] touch-manipulation group">
                <div>
                    <p class="text-[13px] font-bold text-accent">
                        {{ $isAmharic ? 'ተጨማሪ ጥያቄዎችን ይመልከቱ' : 'View more questions' }}
                    </p>
                    <p class="text-[11px] text-accent/60 mt-0.5">
                        {{ $isAmharic ? 'በየቀኑ አዳዲስ ጥያቄዎች እንጨምራለን' : 'We add new questions every day' }}
                    </p>
                </div>
                <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </button>
        </div>
        @endif
    </div>

    {{-- ── Modal backdrop ── --}}
    <div x-show="showFaqModal"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
         @click="showFaqModal = false; activeFaq = null">
    </div>

    {{-- ── Modal panel ── --}}
    <div x-show="showFaqModal"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-10"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-10"
         class="fixed inset-x-0 bottom-0 z-50 flex justify-center sm:inset-0 sm:items-center sm:px-4"
         style="pointer-events: none;">

        <div class="w-full sm:max-w-lg bg-card rounded-t-3xl sm:rounded-3xl shadow-2xl flex flex-col max-h-[90vh]"
             style="pointer-events: auto;"
             @click.stop>

            {{-- Drag handle --}}
            <div class="flex justify-center pt-3 pb-1">
                <div class="w-10 h-1 rounded-full bg-border/60"></div>
            </div>

            {{-- Header --}}
            <div class="flex items-center justify-between gap-3 px-5 py-3.5 border-b border-border/40 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 1.918-2 3.522-2 2.209 0 4 1.567 4 3.5 0 1.418-.964 2.638-2.347 3.188-.74.294-1.153.838-1.153 1.412V16m.01 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[15px] font-bold text-primary leading-snug">{{ $isAmharic ? 'ሁሉም ጥያቄዎች' : 'All Questions' }}</p>
                        <p class="text-[11px] text-muted-text">{{ $allFaqs->count() }} {{ $isAmharic ? 'ጥያቄዎች' : 'questions' }}</p>
                    </div>
                </div>
                <button type="button" @click="showFaqModal = false; activeFaq = null"
                        class="w-8 h-8 rounded-xl bg-muted flex items-center justify-center text-muted-text hover:bg-border hover:text-primary transition shrink-0 touch-manipulation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Scrollable list --}}
            <div class="overflow-y-auto overscroll-contain flex-1 px-4 py-4 space-y-2">
                @foreach($modalFaqs as $index => $faq)
                    @php
                        $mq = localized($faq, 'question') ?? $faq->question_en;
                        $ma = localized($faq, 'answer')   ?? $faq->answer_en;
                    @endphp
                    <div class="rounded-2xl border overflow-hidden transition-colors duration-200"
                         :class="activeFaq === {{ $index }} ? 'border-accent/30 bg-accent/[0.03]' : 'border-border bg-card'">

                        <button type="button"
                                @click="activeFaq = (activeFaq === {{ $index }}) ? null : {{ $index }}"
                                class="w-full flex items-start gap-3 px-4 py-3.5 text-left touch-manipulation group">
                            <span class="shrink-0 w-6 h-6 rounded-md flex items-center justify-center text-[11px] font-bold mt-0.5 transition-colors"
                                  :class="activeFaq === {{ $index }} ? 'bg-accent text-white' : 'bg-muted text-muted-text'">
                                {{ $index + 1 }}
                            </span>
                            <span class="flex-1 text-[14px] font-semibold leading-snug text-primary">{{ $mq }}</span>
                            <svg class="w-4 h-4 shrink-0 mt-0.5 text-muted-text transition-transform duration-200"
                                 :class="activeFaq === {{ $index }} && 'rotate-180 text-accent'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="activeFaq === {{ $index }}"
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0">
                            <div class="px-4 pb-4 pt-0">
                                <div class="ml-9 rounded-xl bg-muted/50 border border-border/30 px-4 py-3">
                                    <p class="text-[13px] leading-relaxed text-secondary whitespace-pre-line">{{ $ma }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="px-5 py-4 border-t border-border/40 shrink-0">
                <button type="button" @click="showFaqModal = false; activeFaq = null"
                        class="w-full rounded-xl bg-muted py-3 text-sm font-semibold text-secondary hover:bg-border transition touch-manipulation">
                    {{ $isAmharic ? 'ዝጋ' : 'Close' }}
                </button>
            </div>
        </div>
    </div>

</section>
@endif

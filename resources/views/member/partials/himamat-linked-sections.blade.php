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

    {{-- Elegant Timeline Header --}}
    <div class="px-4 pt-4 pb-6 text-center">
        <h2 class="text-sm font-bold uppercase tracking-[0.2em] text-accent/80">{{ __('app.himamat_day_view_title') }}</h2>
        <div class="h-px w-12 bg-accent/30 mx-auto mt-3"></div>
    </div>

    <section x-data="{
                openSlot: @js($himamatTimeline['target_slot_key']),
                setOpenSlot(slotKey) {
                    this.openSlot = this.openSlot === slotKey ? null : slotKey;
                    this.$nextTick(() => {
                        if (this.openSlot) {
                            const target = this.$root.querySelector(`[data-slot-key='${slotKey}']`);
                            if (target) {
                                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }
                    });
                }
             }"
             x-init="$nextTick(() => { const target = $root.querySelector(`[data-slot-key='${openSlot}']`); if (target) { target.scrollIntoView({ behavior: 'auto', block: 'center' }); } })"
             class="px-1 sm:px-2">

        {{-- Slot list as a vertical timeline --}}
        <div class="flex flex-col">
            @foreach($himamatTimeline['items'] as $item)
                @php
                    $slot = $item['slot'];
                    $state = $item['temporal_state'];
                    $slotHourLabel = __($slotLabelKeys[$slot->slot_key] ?? 'app.himamat_day_view_title');
                    $localizedHeader = localized($slot, 'slot_header') ?? $slot->slot_header_en ?? $slotHourLabel;
                    $localizedReadingRef = trim((string) (localized($slot, 'reading_reference') ?? ''));
                    $localizedReading = trim((string) (localized($slot, 'reading_text') ?? ''));
                    $resourcesByType = $slot->resources->groupBy('type');
                    $availableResourceTypes = collect($resourceTypeOrder)
                        ->filter(fn (string $type): bool => $resourcesByType->has($type))
                        ->values();
                    $defaultResourceType = $availableResourceTypes->first();

                    $stateClasses = match ($state) {
                        'current' => 'bg-accent/[0.03] border-accent/30 shadow-md ring-1 ring-accent/10',
                        'past' => 'opacity-85 border-border bg-card shadow-sm',
                        default => 'border-border bg-card shadow-sm',
                    };
                @endphp

                <div class="relative flex gap-3 sm:gap-4 pb-6 last:pb-0 group">
                    {{-- Timeline Dot & Vertical Line --}}
                    <div class="flex flex-col items-center mt-7 w-4 shrink-0 relative">
                        <div class="w-2.5 h-2.5 rounded-full z-10 transition-all duration-300"
                             :class="openSlot === '{{ $slot->slot_key }}' || '{{ $state }}' === 'current' ? 'bg-accent shadow-[0_0_8px_rgba(10,98,134,0.6)] scale-110' : 'bg-border'"></div>
                        
                        @if(!$loop->last)
                            <div class="absolute top-4 bottom-[-24px] w-[2px] bg-border/40 rounded-full transition-colors group-hover:bg-border/70"></div>
                        @endif
                    </div>

                    {{-- Separate Content Card per Hour --}}
                    <div class="flex-1 min-w-0">
                        <article data-slot-key="{{ $slot->slot_key }}"
                                 class="relative rounded-2xl border transition-all duration-300 overflow-hidden {{ $stateClasses }}"
                                 :class="openSlot === '{{ $slot->slot_key }}' ? 'ring-1 ring-accent/20 border-accent/20' : ''">

                            {{-- Slot Header (Button) --}}
                            <button type="button"
                                    @click="setOpenSlot('{{ $slot->slot_key }}')"
                                    class="w-full px-4 py-4 text-left flex items-start gap-3 sm:gap-4 group/btn touch-manipulation">

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2.5 mb-1.5">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-accent">{{ $slotHourLabel }}</span>
                                        @if($state === 'current')
                                            <span class="flex w-1.5 h-1.5 relative">
                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75"></span>
                                                <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-accent"></span>
                                            </span>
                                        @endif
                                    </div>

                                    <h3 class="text-[15px] sm:text-base font-bold text-primary leading-snug group-hover/btn:text-accent transition-colors">{{ $localizedHeader }}</h3>

                                    @if($localizedReadingRef !== '')
                                        <p class="mt-1.5 text-[13px] font-medium text-secondary">{{ $localizedReadingRef }}</p>
                                    @endif
                                </div>

                                <div class="shrink-0 pt-1 text-muted-text transition-colors group-hover/btn:text-accent">
                                    <svg class="w-5 h-5 transition-transform duration-300"
                                         :class="openSlot === '{{ $slot->slot_key }}' ? 'rotate-180 text-accent' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </button>

                            {{-- Expanded content --}}
                            <div x-show="openSlot === '{{ $slot->slot_key }}'"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="px-4 pb-5 pt-2 border-t border-border/30">

                                {{-- Bible Text --}}
                                <div class="pt-1 pb-2">
                                    @if($localizedReading !== '')
                                        <p class="text-[15px] sm:text-base leading-loose text-primary/95 whitespace-pre-line break-words">{{ $localizedReading }}</p>
                                    @else
                                        <p class="text-sm text-muted-text italic">{{ __('app.himamat_slot_content_pending') }}</p>
                                    @endif
                                </div>

                                {{-- Optional Resources --}}
                                @if($availableResourceTypes->isNotEmpty())
                                    <div class="mt-4 pt-4 border-t border-border/30" x-data="{ resourceTab: '{{ $defaultResourceType }}' }">

                                        {{-- Elegant Tabs --}}
                                        <div class="flex gap-5 overflow-x-auto no-scrollbar pb-px mb-4 border-b border-border/30">
                                            @foreach($availableResourceTypes as $type)
                                                <button @click="resourceTab = '{{ $type }}'"
                                                        class="text-[10px] font-bold uppercase tracking-wider pb-2 border-b-2 transition-colors whitespace-nowrap touch-manipulation"
                                                        :class="resourceTab === '{{ $type }}' ? 'border-accent text-accent' : 'border-transparent text-muted-text hover:text-secondary'">
                                                    {{ __('app.himamat_resource_type_'.$type) }}
                                                    <span class="opacity-60 ml-0.5">({{ $resourcesByType->get($type)->count() }})</span>
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
                                                        <div class="space-y-4">
                                                            @foreach($typeResources as $resource)
                                                                @php
                                                                    $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                                    $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                                @endphp
                                                                <div class="border-l-2 border-accent/30 pl-4 py-1">
                                                                    <h4 class="text-sm font-bold text-primary">{{ $resourceTitle }}</h4>
                                                                    @if($resourceText !== '')
                                                                        <p class="mt-1.5 text-sm leading-relaxed text-secondary whitespace-pre-line">{{ $resourceText }}</p>
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

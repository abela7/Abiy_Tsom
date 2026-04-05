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
<div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="flex items-center gap-3.5 px-4 py-4 sm:px-5">
        <div class="w-10 h-10 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-muted px-2.5 py-1 text-[10px] font-semibold text-muted-text">
                    <span class="h-1.5 w-1.5 rounded-full {{ ($himamatTimeline['is_today'] ?? false) ? 'bg-accent animate-pulse' : 'bg-accent-secondary' }}"></span>
                    {{ ($himamatTimeline['is_today'] ?? false) ? __('app.today') : $himamatDay->date?->format('D') }}
                </span>
            </div>
            <h2 class="mt-1 text-lg font-bold text-primary leading-snug">{{ $localizedHimamatTitle }}</h2>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     Day Theme & Meaning — Full-width standalone card
     ══════════════════════════════════════════════════════════════════════ --}}
@if($localizedDayMeaning !== '')
<div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-border/60 bg-muted/30">
        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
        </div>
        <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-text">{{ __('app.himamat_day_meaning_title') }}</h3>
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
<div class="rounded-2xl border border-border bg-card shadow-sm overflow-hidden">
    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-border/60 bg-muted/30">
        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-3.5 h-3.5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
        </div>
        <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }}</h3>
    </div>
    <div class="px-4 py-4 sm:px-5">
        <p class="text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedRitualIntro }}</p>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════════════════════════════
     HIMAMAT TIMELINE — Prayer hours with expandable slots & resources
     ══════════════════════════════════════════════════════════════════════ --}}
@if($himamatTimeline && $himamatDay->slots->isNotEmpty())
    <section class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm"
             x-data="{
                openSlot: @js($himamatTimeline['target_slot_key']),
                setOpenSlot(slotKey) {
                    this.openSlot = this.openSlot === slotKey ? null : slotKey;
                    this.$nextTick(() => {
                        const target = this.$root.querySelector(`[data-slot-key='${slotKey}']`);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    });
                }
             }"
             x-init="$nextTick(() => { const target = $root.querySelector(`[data-slot-key='${openSlot}']`); if (target) { target.scrollIntoView({ behavior: 'auto', block: 'nearest' }); } })">

        {{-- Timeline header --}}
        <div class="border-b border-border px-5 py-5 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 mb-1.5">
                        <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <h2 class="text-lg font-bold text-primary">{{ __('app.himamat_day_view_title') }}</h2>
                    </div>
                    <p class="mt-1 text-sm leading-relaxed text-secondary">{{ __('app.himamat_preferences_timeline_hint') }}</p>
                </div>
                <div class="hidden rounded-full bg-muted px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-text sm:inline-flex">
                    {{ __('app.himamat_timeline_today_state') }}
                </div>
            </div>

            {{-- Slot pills — horizontal scroll --}}
            <div class="mt-4 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($himamatTimeline['items'] as $item)
                    @php
                        $slot = $item['slot'];
                        $state = $item['temporal_state'];
                        $slotHourLabel = __($slotLabelKeys[$slot->slot_key] ?? 'app.himamat_day_view_title');
                    @endphp
                    <button type="button"
                            @click="setOpenSlot('{{ $slot->slot_key }}')"
                            class="shrink-0 rounded-full border px-4 py-2.5 text-xs font-semibold transition-all duration-200 touch-manipulation active:scale-95"
                            :class="openSlot === '{{ $slot->slot_key }}'
                                ? 'border-accent bg-accent text-on-accent shadow-sm'
                                : '{{ $state === 'current' ? 'border-accent/30 bg-accent/10 text-accent' : ($state === 'past' ? 'border-border bg-muted text-secondary' : 'border-border bg-card text-muted-text hover:bg-muted') }}'">
                        {{ $slotHourLabel }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Slot cards --}}
        <div class="space-y-3 px-4 py-4 sm:px-5 sm:py-5">
            @foreach($himamatTimeline['items'] as $index => $item)
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
                    $resourceSummary = $availableResourceTypes
                        ->map(fn (string $type): string => __('app.himamat_resource_type_'.$type).' '.$resourcesByType->get($type, collect())->count())
                        ->implode(' · ');
                    $stateClasses = match ($state) {
                        'current' => 'border-accent/25 bg-accent/[0.04] shadow-sm',
                        'past' => 'border-border bg-muted/30',
                        default => 'border-border bg-card',
                    };
                    $stateBadgeClasses = match ($state) {
                        'current' => 'bg-accent text-on-accent',
                        'past' => 'bg-accent-secondary/15 text-accent-secondary',
                        default => 'bg-muted text-muted-text',
                    };
                    $dotClasses = match ($state) {
                        'current' => 'bg-accent ring-4 ring-accent/20',
                        'past' => 'bg-accent-secondary ring-4 ring-accent-secondary/15',
                        default => 'bg-border',
                    };
                @endphp

                <article data-slot-key="{{ $slot->slot_key }}"
                         x-data="{ resourceTab: @js($defaultResourceType) }"
                         class="relative overflow-hidden rounded-2xl border transition-all duration-200 {{ $stateClasses }}"
                         :class="openSlot === '{{ $slot->slot_key }}' ? 'ring-1 ring-accent/20 shadow-md' : ''">

                    {{-- Left accent stripe --}}
                    <div class="absolute left-0 top-0 h-full w-1 rounded-l-2xl {{ $state === 'current' ? 'bg-accent' : ($state === 'past' ? 'bg-accent-secondary/40' : 'bg-transparent') }}"></div>

                    {{-- Slot header (clickable) --}}
                    <button type="button"
                            @click="setOpenSlot('{{ $slot->slot_key }}')"
                            class="w-full px-5 py-4 text-left sm:px-6 sm:py-5 transition-colors hover:bg-muted/20 touch-manipulation active:bg-muted/30">
                        <div class="flex items-start gap-3.5">
                            {{-- Timeline dot --}}
                            <div class="mt-1 flex flex-col items-center shrink-0">
                                <span class="h-3 w-3 rounded-full {{ $dotClasses }} transition-all duration-200"></span>
                                @if($index !== count($himamatTimeline['items']) - 1)
                                    <span class="mt-2 h-8 w-px bg-border"></span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                {{-- Badges --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-secondary">
                                        {{ $slotHourLabel }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $stateBadgeClasses }}">
                                        {{ __('app.himamat_state_'.$state) }}
                                    </span>
                                </div>

                                {{-- Slot title & reference --}}
                                <div class="mt-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-base font-bold leading-snug text-primary sm:text-lg">{{ $localizedHeader }}</h3>
                                        @if($localizedReadingRef !== '')
                                            <p class="mt-1.5 text-sm font-medium text-secondary">{{ $localizedReadingRef }}</p>
                                        @endif
                                        @if($resourceSummary !== '')
                                            <p class="mt-1.5 text-xs text-muted-text">{{ $resourceSummary }}</p>
                                        @endif
                                    </div>

                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-muted-text transition-transform duration-200"
                                         :class="openSlot === '{{ $slot->slot_key }}' ? 'rotate-180 text-accent' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>

                    {{-- Expanded content --}}
                    <div x-show="openSlot === '{{ $slot->slot_key }}'"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-border/60 px-5 pb-5 pt-4 sm:px-6 sm:pb-6">

                        {{-- Bible reading card --}}
                        <div class="rounded-xl border border-border bg-card px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="w-6 h-6 rounded-md bg-accent/10 flex items-center justify-center shrink-0">
                                            <svg class="w-3 h-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        </div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-text">{{ __('app.himamat_bible_section_title') }}</p>
                                    </div>
                                    @if($localizedReadingRef !== '')
                                        <p class="text-sm font-semibold text-primary">{{ $localizedReadingRef }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full bg-accent/10 px-2.5 py-1 text-[10px] font-semibold text-accent shrink-0">
                                    {{ $slotHourLabel }}
                                </span>
                            </div>

                            {{-- Reading text --}}
                            <div class="mt-3 rounded-xl bg-muted/50 px-4 py-4">
                                @if($localizedReading !== '')
                                    <p class="text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedReading }}</p>
                                @else
                                    <div class="flex items-center gap-2 py-2">
                                        <svg class="w-4 h-4 text-muted-text shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <p class="text-sm text-muted-text italic">{{ __('app.himamat_slot_content_pending') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Resources section --}}
                        @if($availableResourceTypes->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-border bg-muted/30 px-4 py-4">
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-md bg-accent-secondary/10 flex items-center justify-center shrink-0">
                                                <svg class="w-3 h-3 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                            </div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                        </div>
                                        <p class="mt-1.5 text-xs text-muted-text ml-8">{{ __('app.himamat_hour_resources_hint') }}</p>
                                    </div>
                                </div>

                                {{-- Resource type tabs --}}
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($availableResourceTypes as $type)
                                        <button type="button"
                                                @click="resourceTab = '{{ $type }}'"
                                                :class="resourceTab === '{{ $type }}'
                                                    ? 'border-accent bg-accent text-on-accent shadow-sm'
                                                    : 'border-border bg-card text-secondary hover:bg-muted hover:border-border'"
                                                class="inline-flex items-center gap-1.5 rounded-full border px-3.5 py-2 text-xs font-semibold transition-all duration-150 touch-manipulation active:scale-95">
                                            <span>{{ __('app.himamat_resource_type_'.$type) }}</span>
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold transition-colors"
                                                  :class="resourceTab === '{{ $type }}' ? 'bg-on-accent/15 text-on-accent' : 'bg-muted text-muted-text'">
                                                {{ $resourcesByType->get($type, collect())->count() }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                {{-- Resource content by type --}}
                                @foreach($availableResourceTypes as $type)
                                    @php
                                        $typeResources = $resourcesByType->get($type, collect());
                                    @endphp

                                    <div x-show="resourceTab === '{{ $type }}'"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         class="mt-4">
                                        @if($type === 'text')
                                            <div class="space-y-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                    @endphp
                                                    <article class="overflow-hidden rounded-xl border border-accent/10 bg-card px-4 py-4 shadow-sm">
                                                        <p class="text-sm font-semibold text-primary">{{ $resourceTitle }}</p>
                                                        @if($resourceText !== '')
                                                            <p class="mt-3 text-sm leading-7 text-secondary whitespace-pre-line">{{ $resourceText }}</p>
                                                        @endif
                                                    </article>
                                                @endforeach
                                            </div>
                                        @elseif($type === 'photo')
                                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceUrl = $resource->resolvedUrl();
                                                    @endphp
                                                    @if($resourceUrl)
                                                        <a href="{{ $resourceUrl }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="group overflow-hidden rounded-xl border border-border bg-card shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md active:scale-[0.98]">
                                                            <div class="overflow-hidden">
                                                                <img src="{{ $resourceUrl }}"
                                                                     alt="{{ $resourceTitle }}"
                                                                     loading="lazy"
                                                                     decoding="async"
                                                                     class="h-32 w-full object-cover transition duration-300 group-hover:scale-105 sm:h-36">
                                                            </div>
                                                            <div class="px-3 py-2.5">
                                                                <p class="text-xs font-semibold leading-snug text-primary line-clamp-2">{{ $resourceTitle }}</p>
                                                            </div>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- video, pdf, website --}}
                                            <div class="space-y-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                        $resourceUrl = $resource->resolvedUrl();
                                                    @endphp
                                                    <article class="rounded-xl border border-border bg-card px-4 py-4 shadow-sm transition-all duration-150 hover:shadow-md">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0 flex-1">
                                                                <p class="text-sm font-semibold text-primary">{{ $resourceTitle }}</p>
                                                                @if($resourceText !== '')
                                                                    <p class="mt-1.5 text-sm leading-relaxed text-secondary">{{ $resourceText }}</p>
                                                                @endif
                                                            </div>
                                                            @if($resourceUrl)
                                                                <a href="{{ $resourceUrl }}"
                                                                   target="_blank"
                                                                   rel="noopener"
                                                                   class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-accent/10 px-3.5 py-2 text-xs font-semibold text-accent transition-colors hover:bg-accent/15 active:scale-95 touch-manipulation">
                                                                    {{ __('app.himamat_resource_open') }}
                                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </article>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif

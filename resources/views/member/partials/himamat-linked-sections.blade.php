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

<section class="rounded-2xl border border-accent/15 bg-card shadow-sm overflow-hidden">
    <div class="bg-[linear-gradient(160deg,rgba(10,98,134,0.12),rgba(226,202,24,0.08))] px-5 py-5">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-accent">{{ __('app.himamat_eyebrow') }}</p>
        <h2 class="mt-2 text-xl font-bold text-primary">{{ $localizedHimamatTitle }}</h2>

        @if($localizedDayMeaning !== '')
            <p class="mt-3 text-sm leading-7 text-secondary whitespace-pre-line">{{ $localizedDayMeaning }}</p>
        @endif

        @if($localizedRitualIntro !== '')
            <div class="mt-4 rounded-2xl border border-accent/15 bg-card/70 px-4 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-primary whitespace-pre-line">{{ $localizedRitualIntro }}</p>
            </div>
        @endif
    </div>
</section>

@if($himamatTimeline && $himamatDay->slots->isNotEmpty())
    <section class="rounded-2xl border border-border bg-card px-5 py-5 shadow-sm"
             x-data="{ openSlot: @js($himamatTimeline['target_slot_key']) }">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_view_title') }}</p>
                <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_day_view_title') }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_timeline_hint') }}</p>
            </div>
            <div class="shrink-0 rounded-full bg-muted px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">
                {{ $himamatTimeline['is_today'] ? __('app.today') : $himamatDay->date?->format('D') }}
            </div>
        </div>

        <div class="relative mt-6 space-y-4 before:absolute before:bottom-2 before:left-[1.15rem] before:top-2 before:w-px before:bg-border">
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
                    $resourceSummary = $availableResourceTypes
                        ->map(function (string $type) use ($resourcesByType): string {
                            return __('app.himamat_resource_type_'.$type).' '.$resourcesByType->get($type, collect())->count();
                        })
                        ->implode(' · ');
                @endphp

                <article x-data="{ resourceTab: @js($defaultResourceType) }"
                         class="relative ml-2 rounded-[1.6rem] border transition"
                         :class="openSlot === '{{ $slot->slot_key }}'
                            ? 'border-accent/35 bg-accent/5 shadow-sm'
                            : '{{ $state === 'past' ? 'border-border bg-muted/30' : 'border-border bg-card' }}'">
                    <div class="absolute left-[-0.15rem] top-6 z-10 h-3.5 w-3.5 rounded-full border-2 border-surface {{ $state === 'current' ? 'bg-accent' : ($state === 'past' ? 'bg-accent-secondary' : 'bg-border') }}"></div>

                    <button type="button"
                            @click="openSlot = '{{ $slot->slot_key }}'"
                            class="w-full px-5 py-4 pl-7 text-left">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ $slotHourLabel }}</p>
                                <h3 class="mt-1 text-base font-semibold text-primary">{{ $localizedHeader }}</h3>

                                @if($localizedReadingRef !== '')
                                    <p class="mt-2 text-sm leading-relaxed text-secondary">{{ $localizedReadingRef }}</p>
                                @endif

                                @if($resourceSummary !== '')
                                    <p class="mt-2 text-xs text-muted-text">{{ $resourceSummary }}</p>
                                @endif
                            </div>

                            <div class="shrink-0 text-right">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $state === 'current' ? 'bg-accent text-on-accent' : ($state === 'past' ? 'bg-accent-secondary/15 text-accent-secondary' : 'bg-muted text-muted-text') }}">
                                    {{ __('app.himamat_state_'.$state) }}
                                </span>
                                <svg class="mt-3 ml-auto h-5 w-5 text-muted-text transition"
                                     :class="openSlot === '{{ $slot->slot_key }}' ? 'rotate-180 text-accent' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </div>
                    </button>

                    <div x-show="openSlot === '{{ $slot->slot_key }}'"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-border/70 px-5 pb-5 pt-4 pl-7">
                        <div class="rounded-2xl border border-border/80 bg-card px-4 py-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_section_title') }}</p>
                            @if($localizedReadingRef !== '')
                                <p class="mt-2 text-sm font-semibold text-primary">{{ $localizedReadingRef }}</p>
                            @endif
                            <p class="mt-3 text-sm leading-7 text-secondary whitespace-pre-line">
                                {{ $localizedReading !== '' ? $localizedReading : __('app.himamat_slot_content_pending') }}
                            </p>
                        </div>

                        @if($availableResourceTypes->isNotEmpty())
                            <div class="mt-4 rounded-2xl border border-border/80 bg-muted/35 px-4 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                    <p class="text-xs text-muted-text">{{ __('app.himamat_hour_resources_hint') }}</p>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($availableResourceTypes as $type)
                                        <button type="button"
                                                @click="resourceTab = '{{ $type }}'"
                                                :class="resourceTab === '{{ $type }}'
                                                    ? 'border-accent bg-accent text-on-accent'
                                                    : 'border-border bg-card text-secondary hover:bg-border'"
                                                class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-xs font-semibold transition">
                                            <span>{{ __('app.himamat_resource_type_'.$type) }}</span>
                                            <span class="rounded-full bg-black/10 px-1.5 py-0.5 text-[10px] font-bold"
                                                  :class="resourceTab === '{{ $type }}' ? 'bg-black/15 text-on-accent' : ''">
                                                {{ $resourcesByType->get($type, collect())->count() }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>

                                @foreach($availableResourceTypes as $type)
                                    @php
                                        $typeResources = $resourcesByType->get($type, collect());
                                    @endphp

                                    <div x-show="resourceTab === '{{ $type }}'" x-cloak class="mt-4">
                                        @if($type === 'text')
                                            <div class="space-y-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                    @endphp
                                                    <article class="rounded-2xl border border-border bg-card px-4 py-4">
                                                        <p class="text-sm font-semibold text-primary">{{ $resourceTitle }}</p>
                                                        @if($resourceText !== '')
                                                            <p class="mt-3 text-sm leading-7 text-secondary whitespace-pre-line">{{ $resourceText }}</p>
                                                        @endif
                                                    </article>
                                                @endforeach
                                            </div>
                                        @elseif($type === 'photo')
                                            <div class="grid grid-cols-2 gap-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceUrl = $resource->resolvedUrl();
                                                    @endphp
                                                    @if($resourceUrl)
                                                        <a href="{{ $resourceUrl }}"
                                                           target="_blank"
                                                           rel="noopener"
                                                           class="group overflow-hidden rounded-2xl border border-border bg-card">
                                                            <img src="{{ $resourceUrl }}"
                                                                 alt="{{ $resourceTitle }}"
                                                                 class="h-36 w-full object-cover transition duration-300 group-hover:scale-[1.02]">
                                                            <div class="px-3 py-2">
                                                                <p class="text-xs font-semibold text-primary">{{ $resourceTitle }}</p>
                                                            </div>
                                                        </a>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="space-y-3">
                                                @foreach($typeResources as $resource)
                                                    @php
                                                        $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                                        $resourceText = trim((string) (localized($resource, 'text') ?? $resource->text_en ?? ''));
                                                        $resourceUrl = $resource->resolvedUrl();
                                                    @endphp
                                                    <article class="rounded-2xl border border-border bg-card px-4 py-4">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="text-sm font-semibold text-primary">{{ $resourceTitle }}</p>
                                                                @if($resourceText !== '')
                                                                    <p class="mt-2 text-sm leading-relaxed text-secondary">{{ $resourceText }}</p>
                                                                @endif
                                                            </div>
                                                            @if($resourceUrl)
                                                                <a href="{{ $resourceUrl }}"
                                                                   target="_blank"
                                                                   rel="noopener"
                                                                   class="shrink-0 rounded-xl border border-border bg-muted px-3 py-2 text-xs font-semibold text-secondary transition hover:bg-border">
                                                                    {{ __('app.himamat_resource_open') }}
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

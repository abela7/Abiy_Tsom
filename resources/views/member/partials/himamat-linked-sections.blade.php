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

<section class="overflow-hidden rounded-[1.75rem] border border-accent/15 bg-card shadow-sm">
    <div class="relative overflow-hidden bg-[radial-gradient(circle_at_top_right,rgba(226,202,24,0.16),transparent_32%),linear-gradient(165deg,rgba(10,98,134,0.12),rgba(10,98,134,0.03)_42%,rgba(255,255,255,0)_100%)] px-5 py-5 sm:px-6 sm:py-6">
        <div class="absolute -right-14 top-0 h-28 w-28 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 h-24 w-24 rounded-full bg-accent-secondary/10 blur-3xl"></div>

        <div class="relative">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <span class="inline-flex items-center rounded-full bg-accent/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.22em] text-accent">
                        {{ __('app.himamat_eyebrow') }}
                    </span>
                    <h2 class="mt-3 text-2xl font-black tracking-tight text-primary sm:text-[2rem]">
                        {{ $localizedHimamatTitle }}
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-secondary sm:text-[15px]">
                        {{ __('app.himamat_timeline_hint') }}
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-full border border-border bg-card/80 px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full {{ ($himamatTimeline['is_today'] ?? false) ? 'bg-accent' : 'bg-accent-secondary' }}"></span>
                    <span>{{ ($himamatTimeline['is_today'] ?? false) ? __('app.today') : $himamatDay->date?->format('D') }}</span>
                </div>
            </div>

            @if($localizedDayMeaning !== '' || $localizedRitualIntro !== '')
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @if($localizedDayMeaning !== '')
                        <article class="rounded-2xl border border-white/50 bg-card/80 px-4 py-4 shadow-sm backdrop-blur-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_meaning_title') }}</p>
                            <p class="mt-3 text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedDayMeaning }}</p>
                        </article>
                    @endif

                    @if($localizedRitualIntro !== '')
                        <article class="rounded-2xl border border-accent/10 bg-accent/5 px-4 py-4 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }}</p>
                            <p class="mt-3 text-sm leading-7 text-primary whitespace-pre-line">{{ $localizedRitualIntro }}</p>
                        </article>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>

@if($himamatTimeline && $himamatDay->slots->isNotEmpty())
    <section class="overflow-hidden rounded-[1.75rem] border border-border bg-card shadow-sm"
             x-data="{
                openSlot: @js($himamatTimeline['target_slot_key']),
                setOpenSlot(slotKey) {
                    this.openSlot = slotKey;
                    this.$nextTick(() => {
                        const target = this.$root.querySelector(`[data-slot-key='${slotKey}']`);
                        if (target) {
                            target.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    });
                }
             }"
             x-init="$nextTick(() => { const target = $root.querySelector(`[data-slot-key='${openSlot}']`); if (target) { target.scrollIntoView({ behavior: 'auto', block: 'nearest' }); } })">
        <div class="border-b border-border/80 px-5 py-5 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_view_title') }}</p>
                    <h2 class="mt-1 text-xl font-bold text-primary">{{ __('app.himamat_day_view_title') }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_preferences_timeline_hint') }}</p>
                </div>
                <div class="hidden rounded-full bg-muted px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text sm:inline-flex">
                    {{ __('app.himamat_timeline_today_state') }}
                </div>
            </div>

            <div class="mt-4 -mx-1 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach($himamatTimeline['items'] as $item)
                    @php
                        $slot = $item['slot'];
                        $state = $item['temporal_state'];
                        $slotHourLabel = __($slotLabelKeys[$slot->slot_key] ?? 'app.himamat_day_view_title');
                    @endphp
                    <button type="button"
                            @click="setOpenSlot('{{ $slot->slot_key }}')"
                            class="shrink-0 rounded-full border px-3 py-2 text-xs font-semibold transition"
                            :class="openSlot === '{{ $slot->slot_key }}'
                                ? 'border-accent bg-accent text-on-accent shadow-sm'
                                : '{{ $state === 'current' ? 'border-accent/25 bg-accent/10 text-accent' : ($state === 'past' ? 'border-border bg-muted text-secondary' : 'border-border bg-card text-muted-text') }}'">
                        {{ $slotHourLabel }}
                    </button>
                @endforeach
            </div>
        </div>

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
                        'current' => 'border-accent/30 bg-[linear-gradient(180deg,rgba(10,98,134,0.08),rgba(255,255,255,0))] shadow-sm',
                        'past' => 'border-border bg-muted/35',
                        default => 'border-border bg-card',
                    };
                    $stateBadgeClasses = match ($state) {
                        'current' => 'bg-accent text-on-accent',
                        'past' => 'bg-accent-secondary/15 text-accent-secondary',
                        default => 'bg-muted text-muted-text',
                    };
                    $dotClasses = match ($state) {
                        'current' => 'bg-accent ring-4 ring-accent/15',
                        'past' => 'bg-accent-secondary ring-4 ring-accent-secondary/10',
                        default => 'bg-border',
                    };
                @endphp

                <article data-slot-key="{{ $slot->slot_key }}"
                         x-data="{ resourceTab: @js($defaultResourceType) }"
                         class="relative overflow-hidden rounded-[1.5rem] border transition-all duration-200 {{ $stateClasses }}"
                         :class="openSlot === '{{ $slot->slot_key }}' ? 'ring-1 ring-accent/25 shadow-md shadow-accent/5' : ''">
                    <div class="absolute left-0 top-0 h-full w-1.5 {{ $state === 'current' ? 'bg-accent' : ($state === 'past' ? 'bg-accent-secondary/50' : 'bg-border') }}"></div>

                    <button type="button"
                            @click="setOpenSlot('{{ $slot->slot_key }}')"
                            class="w-full px-5 py-4 text-left sm:px-6 sm:py-5">
                        <div class="flex items-start gap-4">
                            <div class="mt-1 flex flex-col items-center">
                                <span class="h-3 w-3 rounded-full {{ $dotClasses }}"></span>
                                @if($index !== count($himamatTimeline['items']) - 1)
                                    <span class="mt-2 h-10 w-px bg-border/80"></span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full bg-muted px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-secondary">
                                        {{ $slotHourLabel }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $stateBadgeClasses }}">
                                        {{ __('app.himamat_state_'.$state) }}
                                    </span>
                                </div>

                                <div class="mt-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <h3 class="text-base font-bold leading-tight text-primary sm:text-lg">{{ $localizedHeader }}</h3>
                                        @if($localizedReadingRef !== '')
                                            <p class="mt-2 text-sm font-medium text-secondary">{{ $localizedReadingRef }}</p>
                                        @endif
                                        @if($resourceSummary !== '')
                                            <p class="mt-2 text-xs text-muted-text">{{ $resourceSummary }}</p>
                                        @endif
                                    </div>

                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-muted-text transition"
                                         :class="openSlot === '{{ $slot->slot_key }}' ? 'rotate-180 text-accent' : ''"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </button>

                    <div x-show="openSlot === '{{ $slot->slot_key }}'"
                         x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="border-t border-border/70 px-5 pb-5 pt-4 sm:px-6 sm:pb-6">
                        <div class="rounded-2xl border border-border/80 bg-card/90 px-4 py-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_section_title') }}</p>
                                    @if($localizedReadingRef !== '')
                                        <p class="mt-2 text-sm font-semibold text-primary">{{ $localizedReadingRef }}</p>
                                    @endif
                                </div>
                                <span class="inline-flex items-center rounded-full bg-accent/10 px-3 py-1 text-[11px] font-semibold text-accent">
                                    {{ $slotHourLabel }}
                                </span>
                            </div>

                            <div class="mt-3 rounded-2xl bg-muted/40 px-4 py-4">
                                <p class="text-sm leading-7 text-secondary whitespace-pre-line">
                                    {{ $localizedReading !== '' ? $localizedReading : __('app.himamat_slot_content_pending') }}
                                </p>
                            </div>
                        </div>

                        @if($availableResourceTypes->isNotEmpty())
                            <div class="mt-4 rounded-2xl border border-border/80 bg-muted/35 px-4 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                        <p class="mt-1 text-xs text-muted-text">{{ __('app.himamat_hour_resources_hint') }}</p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach($availableResourceTypes as $type)
                                        <button type="button"
                                                @click="resourceTab = '{{ $type }}'"
                                                :class="resourceTab === '{{ $type }}'
                                                    ? 'border-accent bg-accent text-on-accent shadow-sm'
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
                                                    <article class="overflow-hidden rounded-2xl border border-accent/10 bg-[linear-gradient(180deg,rgba(10,98,134,0.04),rgba(255,255,255,0))] px-4 py-4 shadow-sm">
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
                                                           class="group overflow-hidden rounded-2xl border border-border bg-card shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                                                            <div class="overflow-hidden">
                                                                <img src="{{ $resourceUrl }}"
                                                                     alt="{{ $resourceTitle }}"
                                                                     class="h-36 w-full object-cover transition duration-300 group-hover:scale-[1.03]">
                                                            </div>
                                                            <div class="px-3 py-3">
                                                                <p class="text-xs font-semibold leading-relaxed text-primary">{{ $resourceTitle }}</p>
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
                                                    <article class="rounded-2xl border border-border bg-card px-4 py-4 shadow-sm">
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
                                                                   class="shrink-0 rounded-xl bg-accent/10 px-3 py-2 text-xs font-semibold text-accent transition hover:bg-accent/15">
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

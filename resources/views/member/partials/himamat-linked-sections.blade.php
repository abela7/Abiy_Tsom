@php
    $localizedHimamatTitle = localized($himamatDay, 'title') ?? $himamatDay->title_en;
    $localizedDayMeaning = localized($himamatDay, 'spiritual_meaning') ?? '';
    $localizedRitualIntro = localized($himamatDay, 'ritual_guide_intro') ?? '';
    $localizedSynaxariumTitle = localized($himamatDay, 'synaxarium_title') ?? '';
    $localizedSynaxariumText = localized($himamatDay, 'synaxarium_text') ?? '';
    $annualCelebrations = collect($ethDateInfo['annual_celebrations'] ?? [])
        ->map(fn ($celebration) => localized($celebration, 'celebration') ?? $celebration->celebration_en ?? null)
        ->filter()
        ->unique()
        ->values();
    $monthlyCelebrations = collect($ethDateInfo['monthly_celebrations'] ?? [])
        ->map(fn ($celebration) => localized($celebration, 'celebration') ?? $celebration->celebration_en ?? null)
        ->filter()
        ->unique()
        ->values();
    $hasDayLayer = $localizedDayMeaning !== ''
        || $localizedRitualIntro !== ''
        || $localizedSynaxariumTitle !== ''
        || $localizedSynaxariumText !== ''
        || $annualCelebrations->isNotEmpty()
        || $monthlyCelebrations->isNotEmpty();
@endphp

<section class="rounded-2xl border border-accent/15 bg-[linear-gradient(160deg,rgba(10,98,134,0.13),rgba(226,202,24,0.08))] p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-accent">{{ __('app.himamat_eyebrow') }}</p>
    <h2 class="mt-2 text-xl font-bold text-primary">{{ $localizedHimamatTitle }}</h2>
    <p class="mt-2 text-sm leading-relaxed text-secondary">{{ __('app.himamat_timeline_hint') }}</p>
</section>

@if($hasDayLayer)
<section class="rounded-2xl border border-border bg-card px-5 py-5 shadow-sm">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_global_info_title') }}</p>
        <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_global_info_title') }}</h2>
    </div>

    <div class="mt-5 grid gap-4">
        @if($localizedDayMeaning !== '')
            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_day_meaning_title') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-primary whitespace-pre-line">{{ $localizedDayMeaning }}</p>
            </div>
        @endif

        @if($annualCelebrations->isNotEmpty() || $monthlyCelebrations->isNotEmpty() || $localizedSynaxariumTitle !== '' || $localizedSynaxariumText !== '')
            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_synaxarium_title') }}</p>

                @if($localizedSynaxariumTitle !== '' || $localizedSynaxariumText !== '')
                    <div class="mt-3">
                        @if($localizedSynaxariumTitle !== '')
                            <p class="text-sm font-semibold text-primary">{{ $localizedSynaxariumTitle }}</p>
                        @endif
                        @if($localizedSynaxariumText !== '')
                            <p class="mt-2 text-sm leading-relaxed text-secondary whitespace-pre-line">{{ $localizedSynaxariumText }}</p>
                        @endif
                    </div>
                @endif

                @if($annualCelebrations->isNotEmpty())
                    <div class="{{ $localizedSynaxariumTitle !== '' || $localizedSynaxariumText !== '' ? 'mt-4 border-t border-border/70 pt-4' : 'mt-3' }}">
                        <p class="text-sm font-semibold text-primary">{{ __('app.himamat_synaxarium_annual') }}</p>
                        <div class="mt-2 space-y-2">
                            @foreach($annualCelebrations as $celebration)
                                <p class="text-sm leading-relaxed text-secondary">{{ $celebration }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($monthlyCelebrations->isNotEmpty())
                    <div class="{{ $annualCelebrations->isNotEmpty() || $localizedSynaxariumTitle !== '' || $localizedSynaxariumText !== '' ? 'mt-4 border-t border-border/70 pt-4' : 'mt-3' }}">
                        <p class="text-sm font-semibold text-primary">{{ __('app.himamat_synaxarium_monthly') }}</p>
                        <div class="mt-2 space-y-2">
                            @foreach($monthlyCelebrations as $celebration)
                                <p class="text-sm leading-relaxed text-secondary">{{ $celebration }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @if($localizedRitualIntro !== '')
            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_ritual_intro_title') }}</p>
                <p class="mt-2 text-sm leading-relaxed text-primary whitespace-pre-line">{{ $localizedRitualIntro }}</p>
            </div>
        @endif
    </div>
</section>
@endif

@if($himamatDay->faqs->isNotEmpty())
<section class="rounded-2xl border border-border bg-card px-5 py-5 shadow-sm">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_faq_title') }}</p>
        <h2 class="mt-1 text-lg font-bold text-primary">{{ __('app.himamat_faq_title') }}</h2>
    </div>

    <div class="mt-5 space-y-4">
        @foreach($himamatDay->faqs as $faq)
            @php
                $localizedQuestion = localized($faq, 'question') ?? $faq->question_en;
                $localizedAnswer = localized($faq, 'answer') ?? $faq->answer_en;
            @endphp
            <article class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                <p class="text-sm font-semibold text-primary">{{ $localizedQuestion }}</p>
                <p class="mt-2 text-sm leading-relaxed text-secondary whitespace-pre-line">{{ $localizedAnswer }}</p>
            </article>
        @endforeach
    </div>
</section>
@endif

@if($himamatTimeline && $himamatDay->slots->isNotEmpty())
<section class="rounded-2xl border border-border bg-card px-5 py-5 shadow-sm"
         x-data="{ openSlot: @js($himamatTimeline['target_slot_key']) }">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-primary">{{ __('app.himamat_day_view_title') }}</h2>
            <p class="mt-1 text-sm text-secondary">{{ __('app.himamat_timeline_hint') }}</p>
        </div>
        <div class="text-right">
            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_timeline_today_state') }}</p>
            <p class="mt-1 text-sm font-semibold text-primary">
                {{ $himamatTimeline['is_today'] ? __('app.today') : $himamatDay->date?->format('D') }}
            </p>
        </div>
    </div>

    <div class="relative mt-5 space-y-4 before:absolute before:left-5 before:top-1 before:bottom-1 before:w-px before:bg-border">
        @foreach($himamatTimeline['items'] as $item)
            @php
                $slot = $item['slot'];
                $state = $item['temporal_state'];
                $localizedHeader = localized($slot, 'slot_header') ?? $slot->slot_header_en;
                $localizedReadingRef = localized($slot, 'reading_reference') ?? '';
                $localizedReading = localized($slot, 'reading_text') ?? '';
            @endphp
            <article class="relative rounded-[1.75rem] border p-4 transition"
                     :class="openSlot === '{{ $slot->slot_key }}' ? 'border-accent/40 bg-accent/5 shadow-sm' : 'border-border bg-card'">
                <div class="absolute left-4 top-5 z-10 h-3 w-3 rounded-full border-2 border-card {{ $state === 'current' ? 'bg-accent' : ($state === 'past' ? 'bg-accent-secondary' : 'bg-border') }}"></div>

                <button type="button"
                        @click="openSlot = openSlot === '{{ $slot->slot_key }}' ? '{{ $himamatTimeline['target_slot_key'] }}' : '{{ $slot->slot_key }}'"
                        class="w-full pl-7 text-left">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-muted-text">
                                {{ \Carbon\CarbonImmutable::parse($item['scheduled_at'])->format('H:i') }}
                            </p>
                            <h3 class="mt-1 text-base font-semibold text-primary">{{ $localizedHeader }}</h3>
                            @if($localizedReadingRef !== '')
                                <p class="mt-2 text-sm leading-relaxed text-secondary">{{ $localizedReadingRef }}</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $state === 'current' ? 'bg-accent text-on-accent' : ($state === 'past' ? 'bg-accent-secondary/15 text-accent-secondary' : 'bg-muted text-muted-text') }}">
                                {{ __('app.himamat_state_'.$state) }}
                            </span>
                            <svg class="mt-3 ml-auto h-5 w-5 text-muted-text transition"
                                 :class="openSlot === '{{ $slot->slot_key }}' ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </div>
                </button>

                <div x-show="openSlot === '{{ $slot->slot_key }}'" x-cloak x-transition class="pl-7 pt-5 space-y-4">
                    <div class="grid gap-4">
                        <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_bible_section_title') }}</p>
                            @if($localizedReadingRef !== '')
                                <p class="mt-2 text-sm font-semibold text-primary">{{ $localizedReadingRef }}</p>
                            @endif
                            <p class="mt-2 text-sm leading-relaxed text-secondary whitespace-pre-line">
                                {{ $localizedReading !== '' ? $localizedReading : __('app.himamat_slot_content_pending') }}
                            </p>
                        </div>

                        @if($slot->resources->isNotEmpty())
                            <div class="rounded-2xl border border-border/80 bg-muted/40 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">{{ __('app.himamat_hour_resources_title') }}</p>
                                <div class="mt-3 grid gap-3 md:grid-cols-2">
                                    @foreach($slot->resources as $resource)
                                        @php
                                            $resourceTitle = localized($resource, 'title') ?? $resource->title_en ?? __('app.himamat_resource_type_'.$resource->type);
                                            $resourceText = localized($resource, 'text') ?? $resource->text_en ?? '';
                                            $resourceUrl = $resource->resolvedUrl();
                                        @endphp
                                        <article class="rounded-2xl border border-border bg-card p-3">
                                            @if($resource->isPhoto() && $resourceUrl)
                                                <img src="{{ $resourceUrl }}"
                                                     alt="{{ $resourceTitle }}"
                                                     class="h-44 w-full rounded-xl object-cover">
                                            @endif

                                            <div class="{{ $resource->isPhoto() && $resourceUrl ? 'mt-3' : '' }}">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">
                                                    {{ __('app.himamat_resource_type_'.$resource->type) }}
                                                </p>
                                                <p class="mt-2 text-sm font-semibold text-primary">{{ $resourceTitle }}</p>

                                                @if($resource->isText() && $resourceText !== '')
                                                    <p class="mt-3 text-sm leading-relaxed text-secondary whitespace-pre-line">{{ $resourceText }}</p>
                                                @endif

                                                @if($resourceUrl)
                                                    <a href="{{ $resourceUrl }}"
                                                       target="_blank" rel="noopener"
                                                       class="mt-3 inline-flex items-center justify-center rounded-lg border border-border bg-muted px-3 py-2 text-sm font-semibold text-secondary transition hover:bg-border">
                                                        {{ __('app.himamat_resource_open') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endif

@php
    $dayNumber = $dayNumber ?? null;
    $bodyText = $bodyText ?? ($dayNumber
        ? __('app.himamat_daily_linked_body', ['day' => $dayNumber])
        : __('app.himamat_daily_linked_body', ['day' => '']));
    $currentItems = collect($currentItems ?? [])->filter()->values();
    $linkedItems = collect($linkedItems ?? [])->filter()->values();
@endphp

<section class="mb-5">
    <div class="relative overflow-hidden rounded-2xl border border-border bg-gradient-to-br from-accent/8 via-card to-card shadow-sm sm:rounded-2xl">
        {{-- Decorative glow --}}
        <div class="pointer-events-none absolute -right-10 -top-10 h-32 w-32 rounded-full bg-accent/8 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-16 left-10 h-36 w-36 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-40 bg-gradient-to-l from-accent/5 to-transparent lg:block"></div>

        <div class="relative px-5 py-5 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3.5">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-accent text-on-accent shadow-sm">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 8h10"></path>
                            <path d="M7 12h10"></path>
                            <path d="M7 16h6"></path>
                            <path d="m13 5 4 3-4 3"></path>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-accent/10 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.16em] text-accent">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            {{ __('app.himamat_title') }}
                        </span>
                        <h2 class="mt-2.5 text-lg font-bold tracking-tight text-primary sm:text-xl">
                            {{ __('app.himamat_daily_linked_title') }}
                        </h2>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-secondary">
                            {{ $bodyText }}
                        </p>
                    </div>
                </div>

                <a href="{{ $ctaHref }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-accent px-5 py-3 text-sm font-semibold text-on-accent shadow-sm transition-all duration-150 hover:bg-accent-hover active:scale-[0.97] sm:w-auto touch-manipulation">
                    <span>{{ $ctaLabel }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 10h10"></path>
                        <path d="m11 6 4 4-4 4"></path>
                    </svg>
                </a>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="rounded-xl border border-border bg-card px-4 py-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-text">
                        {{ $currentLabel }}
                    </p>

                    @if($currentItems->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($currentItems as $item)
                                <span class="inline-flex items-center rounded-full border border-accent/20 bg-accent/10 px-3 py-1.5 text-xs font-semibold text-accent">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-xl border border-border bg-card px-4 py-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-muted-text">
                                {{ $linkedLabel }}
                            </p>
                            <p class="mt-2.5 text-base font-semibold text-primary">
                                {{ $linkedTitle }}
                            </p>
                            @if(! empty($linkedDate))
                                <p class="mt-1 text-sm text-secondary">{{ $linkedDate }}</p>
                            @endif
                        </div>

                        @if($dayNumber)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-muted px-2.5 py-1.5 text-[11px] font-semibold text-secondary">
                                {{ __('app.day') }} {{ $dayNumber }}
                            </span>
                        @endif
                    </div>

                    @if($linkedItems->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($linkedItems as $item)
                                <span class="inline-flex items-center rounded-full border border-border bg-muted px-3 py-1.5 text-xs font-semibold text-secondary">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

@php
    $dayNumber = $dayNumber ?? null;
    $bodyText = $bodyText ?? ($dayNumber
        ? __('app.himamat_daily_linked_body', ['day' => $dayNumber])
        : __('app.himamat_daily_linked_body', ['day' => '']));
    $currentItems = collect($currentItems ?? [])->filter()->values();
    $linkedItems = collect($linkedItems ?? [])->filter()->values();
@endphp

<section class="mb-5">
    <div class="relative overflow-hidden rounded-2xl border border-accent/15 bg-gradient-to-br from-accent/10 via-card to-card shadow-lg shadow-accent/5 sm:rounded-3xl">
        <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-accent/10 blur-3xl"></div>
        <div class="absolute -bottom-16 left-10 h-36 w-36 rounded-full bg-primary/5 blur-3xl"></div>
        <div class="absolute inset-y-0 right-0 hidden w-40 bg-gradient-to-l from-accent/10 to-transparent lg:block"></div>

        <div class="relative px-4 py-5 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-accent text-on-accent shadow-lg shadow-accent/20 ring-1 ring-white/40">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M7 8h10"></path>
                            <path d="M7 12h10"></path>
                            <path d="M7 16h6"></path>
                            <path d="m13 5 4 3-4 3"></path>
                        </svg>
                    </div>

                    <div class="min-w-0">
                        <p class="inline-flex items-center rounded-full bg-accent/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-accent">
                            {{ __('app.himamat_title') }}
                        </p>
                        <h2 class="mt-3 text-lg font-bold tracking-tight text-primary sm:text-xl">
                            {{ __('app.himamat_daily_linked_title') }}
                        </h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-secondary sm:text-[15px]">
                            {{ $bodyText }}
                        </p>
                    </div>
                </div>

                <a href="{{ $ctaHref }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-accent px-4 py-3 text-sm font-semibold text-on-accent shadow-lg shadow-accent/20 transition hover:bg-accent-hover active:scale-[0.98] sm:w-auto sm:px-5">
                    <span>{{ $ctaLabel }}</span>
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 10h10"></path>
                        <path d="m11 6 4 4-4 4"></path>
                    </svg>
                </a>
            </div>

            <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <div class="rounded-2xl border border-border/70 bg-card/85 px-4 py-4 backdrop-blur-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">
                        {{ $currentLabel }}
                    </p>

                    @if($currentItems->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($currentItems as $item)
                                <span class="inline-flex items-center rounded-full border border-accent/15 bg-accent/10 px-3 py-1.5 text-xs font-semibold text-accent">
                                    {{ $item }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-border/70 bg-card/85 px-4 py-4 backdrop-blur-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-text">
                                {{ $linkedLabel }}
                            </p>
                            <p class="mt-3 text-base font-semibold text-primary">
                                {{ $linkedTitle }}
                            </p>
                            @if(! empty($linkedDate))
                                <p class="mt-1 text-sm text-secondary">{{ $linkedDate }}</p>
                            @endif
                        </div>

                        @if($dayNumber)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-muted px-2.5 py-1 text-[11px] font-semibold text-secondary">
                                {{ __('app.day') }} {{ $dayNumber }}
                            </span>
                        @endif
                    </div>

                    @if($linkedItems->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($linkedItems as $item)
                                <span class="inline-flex items-center rounded-full border border-border bg-muted/70 px-3 py-1.5 text-xs font-semibold text-secondary">
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

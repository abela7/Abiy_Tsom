@props([
    'url',
    'playLabel' => 'Play',
    'openLabel' => 'Open in YouTube',
    'title' => null,
    'variant' => 'default',
    'showExternalLink' => true,
])

@php
$youtubeId = null;
if ($url && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
    $youtubeId = $m[1];
}

$isClean = $variant === 'clean';
$displayTitle = is_string($title) && trim($title) !== '' ? trim($title) : __('app.video_player');
$thumbnailUrl = $youtubeId ? 'https://i.ytimg.com/vi/'.$youtubeId.'/hqdefault.jpg' : null;
$embedUrl = $youtubeId
    ? 'https://www.youtube-nocookie.com/embed/'.$youtubeId.'?'.http_build_query([
        'autoplay' => $isClean ? 1 : 0,
        'controls' => 1,
        'rel' => 0,
        'playsinline' => 1,
        'fs' => 0,
        'modestbranding' => 1,
        'iv_load_policy' => 3,
    ])
    : null;
@endphp

@if($youtubeId)
    @if($isClean)
        <div class="mt-3" x-data="{ playing: false }">
            <div class="overflow-hidden rounded-[1.25rem] border border-border/70 bg-card shadow-sm">
                <template x-if="!playing">
                    <button type="button"
                            @click="playing = true"
                            class="group block w-full text-left">
                        <div class="relative aspect-video w-full overflow-hidden bg-slate-950">
                            <img src="{{ $thumbnailUrl }}"
                                 alt="{{ $displayTitle }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.02]">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/35"></div>

                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 text-slate-950 shadow-lg transition duration-200 group-hover:scale-105">
                                    <svg class="ml-1 h-7 w-7" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </span>
                            </div>

                            <div class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                <p class="text-sm font-semibold leading-snug text-white sm:text-base">{{ $displayTitle }}</p>
                                @if(is_string($playLabel) && trim($playLabel) !== '')
                                    <p class="mt-1 text-xs font-medium uppercase tracking-[0.2em] text-white/70">{{ $playLabel }}</p>
                                @endif
                            </div>
                        </div>
                    </button>
                </template>

                <template x-if="playing">
                    <div class="aspect-video w-full bg-black">
                        <iframe
                            src="{{ $embedUrl }}"
                            title="{{ $displayTitle }}"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            class="h-full w-full border-0"
                        ></iframe>
                    </div>
                </template>
            </div>
        </div>
    @else
        <div class="mt-2" x-data="{ open: false, loaded: false }">
            <button type="button"
                    @click="open = !open; if (!loaded) loaded = true"
                    class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-accent/10 text-accent font-medium text-sm hover:bg-accent/20 transition">
                <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="open ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5v14l11-7z"/>
                </svg>
                <span x-text="open ? '{{ __('app.close') }}' : '{{ $playLabel }}'"></span>
            </button>
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak
                 class="space-y-2 pt-3">
                <template x-if="loaded">
                    <div>
                        <div class="aspect-video w-full rounded-xl overflow-hidden bg-muted">
                            <iframe
                                src="{{ $embedUrl }}"
                                title="{{ $displayTitle }}"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                class="w-full h-full"
                            ></iframe>
                        </div>
                        @if($showExternalLink)
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                               class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-accent transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                {{ $openLabel }}
                            </a>
                        @endif
                    </div>
                </template>
            </div>
        </div>
    @endif
@elseif($url)
    <a href="{{ $url }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1 mt-2 text-sm text-accent font-medium hover:opacity-90">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        {{ __('app.open_externally') }}
    </a>
@endif

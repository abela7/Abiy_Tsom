@props([
    'url',
    'playLabel' => 'Play',
    'openLabel' => 'Open in YouTube',
])

@php
$youtubeId = null;
if ($url && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
    $youtubeId = $m[1];
}
@endphp

@if($youtubeId)
    {{-- Accordion: expand only when Play is pressed, lazy-load iframe --}}
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
                            src="https://www.youtube.com/embed/{{ $youtubeId }}"
                            title="{{ __('app.video_player') }}"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen
                            class="w-full h-full"
                        ></iframe>
                    </div>
                    <a href="{{ $url }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-accent transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        {{ $openLabel }}
                    </a>
                </div>
            </template>
        </div>
    </div>
@elseif($url)
    {{-- Non-YouTube URL — external link only --}}
    <a href="{{ $url }}" target="_blank" rel="noopener"
       class="inline-flex items-center gap-1 mt-2 text-sm text-accent font-medium hover:opacity-90">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        {{ __('app.open_externally') }}
    </a>
@endif

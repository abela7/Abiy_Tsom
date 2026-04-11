{{-- Mezmur (multiple) — exclusive accordion: when one opens, others collapse --}}
<div data-tour="day-mezmur" class="bg-card rounded-2xl p-4 shadow-sm border border-border" x-data="{ openId: null }">
    <h3 class="font-semibold text-sm text-accent-secondary mb-3">{{ $sectionTitle ?? __('app.mezmur') }}</h3>
    <div class="space-y-2">
        @foreach($daily->mezmurs as $mezmur)
        <div class="rounded-xl overflow-hidden" :class="openId === {{ $mezmur->id }} ? 'ring-2 ring-accent-secondary' : ''">
            <button type="button"
                    @click="openId = openId === {{ $mezmur->id }} ? null : {{ $mezmur->id }}"
                    class="w-full flex items-center justify-between gap-2 py-3 px-4 rounded-xl bg-accent-secondary/10 text-left hover:bg-accent-secondary/20 transition">
                <div class="flex items-center gap-2 min-w-0">
                    <svg class="w-5 h-5 shrink-0 transition-transform duration-200" :class="openId === {{ $mezmur->id }} ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    <span class="font-medium text-primary truncate">{{ localized($mezmur, 'title') }}</span>
                </div>
                <span class="text-sm text-muted-text shrink-0">{{ __('app.listen') }}</span>
            </button>
            <div x-show="openId === {{ $mezmur->id }}"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 x-cloak
                 class="border-t border-accent-secondary/20 bg-muted/30">
                <div class="p-3 space-y-2">
                    @if(localized($mezmur, 'description'))
                        <p class="text-sm text-muted-text leading-relaxed">{{ localized($mezmur, 'description') }}</p>
                    @endif
            @php
                $mezmurUrl = $mezmur->mediaUrl($locale);
            @endphp
            @if($mezmurUrl)
                        <x-embedded-media
                            :url="$mezmurUrl"
                            :title="localized($mezmur, 'title')"
                            play-label="{{ __('app.listen') }}"
                            :open-label="__('app.open_in_youtube')"
                            variant="clean"
                            :show-external-link="false"
                        />
                    @endif
                    @if(localized($mezmur, 'lyrics'))
                        <div x-data="{ showLyrics: true }" class="mt-2">
                            <button type="button" @click="showLyrics = !showLyrics"
                                    class="flex items-center gap-1.5 text-xs font-medium text-accent-secondary hover:text-accent-secondary/80 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z"/>
                                </svg>
                                <span x-text="showLyrics ? '{{ __('app.hide_lyrics') }}' : '{{ __('app.show_lyrics') }}'"></span>
                            </button>
                            <div x-show="showLyrics"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 class="mt-2 p-3 rounded-lg bg-accent-secondary/5 border border-accent-secondary/15">
                                <p class="text-sm leading-relaxed text-primary whitespace-pre-line">{{ localized($mezmur, 'lyrics') }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

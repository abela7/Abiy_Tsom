<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#0f172a">
    <meta name="robots" content="noindex">
    <title>{{ $ethDateInfo['ethiopian_date_formatted'] ?? '' }} - {{ __('app.app_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { background: var(--surface, #0f172a); }
    </style>
</head>
<body class="min-h-screen bg-surface text-primary font-sans">
@php
    $ethFormatted = $ethDateInfo['ethiopian_date_formatted'] ?? '';
    $gregorianDate = $daily->date->locale('en')->translatedFormat('l, F j, Y');
    $annualCelebrations = $ethDateInfo['annual_celebrations'] ?? collect();
    $monthlyCelebrations = $ethDateInfo['monthly_celebrations'] ?? collect();
    $hasAnnuals = $annualCelebrations->isNotEmpty();
    $hasMonthlies = $monthlyCelebrations->isNotEmpty();
@endphp

<div x-data="{ showImageModal: false, modalImage: '' }" class="max-w-lg mx-auto px-4 pt-4 pb-8 space-y-5">

    {{-- Header --}}
    <div class="text-center">
        <h1 class="text-xl font-black text-primary">{{ $ethFormatted }}</h1>
        <p class="text-xs text-muted-text mt-1">{{ $gregorianDate }}</p>
    </div>

    {{-- Annual Celebrations --}}
    @if($hasAnnuals)
    <div class="space-y-3">
        <h2 class="text-sm font-black text-accent-secondary text-center uppercase tracking-wider">{{ $locale === 'am' ? 'ዓመታዊ በዓላት' : 'Annual Celebrations' }}</h2>

        @foreach($annualCelebrations as $index => $saint)
        @php $hasImage = (bool) $saint->imageUrl(); $hasDesc = (bool) localized($saint, 'description', $locale); @endphp
        <div class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
            @if($hasImage)
            <div class="relative h-56 overflow-hidden cursor-pointer"
                 @click="showImageModal = true; modalImage = '{{ $saint->imageUrl() }}'">
                <img src="{{ $saint->imageUrl() }}" alt=""
                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async"
                     class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none">
                <div class="absolute inset-0 bg-gradient-to-br from-amber-900/30 via-transparent to-black/40"></div>
                <img src="{{ $saint->imageUrl() }}" alt=""
                     loading="{{ $index === 0 ? 'eager' : 'lazy' }}" decoding="async"
                     class="relative z-10 h-full w-full object-contain drop-shadow-[0_4px_24px_rgba(0,0,0,0.6)]">
                <div class="absolute inset-0 z-20 bg-gradient-to-t from-black/60 via-transparent to-transparent pointer-events-none"></div>
                <div class="absolute bottom-0 left-0 right-0 px-3 pb-2.5 z-30">
                    <span class="inline-block px-1.5 py-0.5 rounded bg-accent-secondary/90 text-[9px] font-bold text-white uppercase tracking-wider">{{ $locale === 'am' ? 'ዓመታዊ' : 'Annual' }}</span>
                </div>
            </div>
            @endif

            <div class="px-4 py-3">
                <div class="flex items-center gap-2.5">
                    @if(!$hasImage)
                    <div class="shrink-0 w-10 h-10 rounded-lg bg-accent-secondary/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-accent-secondary" viewBox="0 0 24 24" fill="currentColor"><path d="M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z"/></svg>
                    </div>
                    @endif
                    <span class="block text-base font-bold text-primary leading-snug">{{ localized($saint, 'celebration', $locale) }}</span>
                </div>

                @if($hasDesc)
                <div class="mt-2.5" x-data="{ expanded: false }">
                    <p class="text-sm text-secondary leading-relaxed whitespace-pre-line"
                       :class="expanded ? '' : 'line-clamp-3'">{{ localized($saint, 'description', $locale) }}</p>
                    <button @click="expanded = !expanded"
                            class="mt-1.5 inline-flex items-center gap-1 text-[11px] font-semibold text-accent hover:text-accent-hover transition-colors">
                        <span x-text="expanded ? '{{ $locale === 'am' ? 'አሳጥር' : 'Show less' }}' : '{{ $locale === 'am' ? 'ተጨማሪ አንብብ' : 'Read more' }}'"></span>
                        <svg class="w-3 h-3 transition-transform" :class="expanded && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Monthly Commemorations --}}
    @if($hasMonthlies)
    <div class="space-y-3">
        <h2 class="text-sm font-black text-primary text-center uppercase tracking-wider">{{ $locale === 'am' ? 'ወርሃዊ በዓላት' : 'Monthly Commemorations' }}</h2>

        @foreach($monthlyCelebrations as $index => $saint)
        @php $monthlyImage = $saint->imageUrl(); $monthlyDesc = localized($saint, 'description', $locale); $hasDetail = $monthlyImage || $monthlyDesc; @endphp
        <div x-data="{ open: false }" class="rounded-2xl bg-card border border-border shadow-sm overflow-hidden">
            <div class="px-4 flex items-center gap-3 py-3 {{ $hasDetail ? 'cursor-pointer' : '' }}" @if($hasDetail) @click="open = !open" @endif>
                @if($monthlyImage)
                    <img src="{{ $monthlyImage }}" alt="" loading="lazy" decoding="async" class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-sm ring-1 ring-border">
                @else
                    <div class="shrink-0 w-11 h-11 rounded-xl bg-muted/50 flex items-center justify-center ring-1 ring-border">
                        <svg class="w-5 h-5 text-muted-text/50" viewBox="0 0 24 24" fill="currentColor"><path d="M10 2h4v6h6v4h-6v10h-4V12H4V8h6V2z"/></svg>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <span class="block text-sm font-bold text-primary leading-snug">{{ localized($saint, 'celebration', $locale) }}</span>
                </div>
                @if($hasDetail)
                <svg class="w-4 h-4 text-muted-text shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                @endif
            </div>

            @if($hasDetail)
            <div x-show="open" x-collapse x-cloak class="px-4 pb-3 space-y-2.5">
                @if($monthlyImage)
                <div class="relative h-48 rounded-xl overflow-hidden cursor-pointer"
                     @click.stop="showImageModal = true; modalImage = '{{ $monthlyImage }}'">
                    <img src="{{ $monthlyImage }}" alt="" loading="lazy" decoding="async"
                         class="absolute inset-0 w-full h-full object-cover scale-110 blur-2xl opacity-70 select-none pointer-events-none">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-900/25 via-transparent to-black/35"></div>
                    <img src="{{ $monthlyImage }}" alt="" loading="lazy" decoding="async"
                         class="relative z-10 h-full w-full object-contain drop-shadow-[0_4px_20px_rgba(0,0,0,0.55)]">
                </div>
                @endif
                @if($monthlyDesc)
                <p class="text-sm text-secondary leading-relaxed whitespace-pre-line">{{ $monthlyDesc }}</p>
                @endif
            </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Empty --}}
    @if(!$hasAnnuals && !$hasMonthlies)
    <div class="rounded-2xl bg-card border border-border shadow-sm p-8 text-center">
        <p class="text-sm text-muted-text font-medium">{{ $locale === 'am' ? 'ለዚህ ቀን ምንም በዓላት የለም' : 'No commemorations for this day' }}</p>
    </div>
    @endif

    {{-- Close button --}}
    <button type="button" onclick="closePage()" class="w-full py-3 rounded-xl bg-muted/60 text-muted-text font-semibold text-sm border border-border hover:bg-muted transition">{{ $locale === 'am' ? 'ዝጋ' : 'Close' }}</button>

    {{-- Fullscreen image modal --}}
    <div x-show="showImageModal" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="showImageModal = false"
         class="fixed inset-0 z-[200] bg-black/90 backdrop-blur-sm flex items-center justify-center p-4">
        <button @click="showImageModal = false" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors z-10">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="modalImage" alt="" class="max-w-full max-h-[85vh] rounded-2xl object-contain shadow-2xl" @click.stop>
    </div>
</div>

<script>
    function closePage() {
        var twa = window.Telegram && window.Telegram.WebApp;
        if (twa && twa.close) { twa.close(); } else { window.history.back(); }
    }
</script>
<script src="https://telegram.org/js/telegram-web-app.js" async onload="(function(){var t=window.Telegram&&window.Telegram.WebApp;if(t){t.expand();t.ready&&t.ready()}})()"></script>
</body>
</html>

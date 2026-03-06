@props(['entry', 'monthNames'])

@php
$monthAm = $monthNames[$entry->month] ? explode(' / ', $monthNames[$entry->month])[1] : '';
$monthEn = $monthNames[$entry->month] ? explode(' / ', $monthNames[$entry->month])[0] : '';
@endphp

<div class="space-y-6 max-w-2xl">
    {{-- Header --}}
    <div class="text-center border-b border-border pb-6">
        <h1 class="text-3xl font-bold text-primary">{{ $monthAm }} {{ $entry->day }}</h1>
        <p class="text-muted-text mt-1">{{ $monthEn }} {{ $entry->day }}</p>
    </div>

    {{-- Title & Description --}}
    @if(filled($entry->title_am) || filled($entry->title_en))
    <div class="space-y-2">
        @if(filled($entry->title_am))
        <div>
            <p class="text-lg font-semibold text-primary">{{ $entry->title_am }}</p>
        </div>
        @endif
        @if(filled($entry->title_en))
        <div>
            <p class="text-lg font-semibold text-primary opacity-80">{{ $entry->title_en }}</p>
        </div>
        @endif
        @if(filled($entry->description_am) || filled($entry->description_en))
        <div class="mt-3 p-3 bg-surface rounded-lg border border-border">
            @if(filled($entry->description_am))
            <p class="text-sm text-primary leading-relaxed">{{ $entry->description_am }}</p>
            @endif
            @if(filled($entry->description_en))
            <p class="text-sm text-primary/80 leading-relaxed mt-2">{{ $entry->description_en }}</p>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- 1. Pauline Epistle --}}
    @if(filled($entry->pauline_book_am) || filled($entry->pauline_book_en))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">1. {{ __('app.lectionary_pauline') }}</h2>
        <div class="text-sm text-muted-text mb-3">
            @if(filled($entry->pauline_book_am))
            <span class="font-medium">{{ $entry->pauline_book_am }}</span>
            @endif
            @if(filled($entry->pauline_chapter))
            {{ $entry->pauline_chapter }}
            @endif
            @if(filled($entry->pauline_verses))
            : {{ $entry->pauline_verses }}
            @endif
            @if(filled($entry->pauline_book_en))
            <span class="ml-2 opacity-75">({{ $entry->pauline_book_en }} @if(filled($entry->pauline_chapter)){{ $entry->pauline_chapter }}@endif @if(filled($entry->pauline_verses)): {{ $entry->pauline_verses }}@endif)</span>
            @endif
        </div>
        @if(filled($entry->pauline_text_am))
        <p class="text-sm text-primary leading-relaxed mb-2">{{ $entry->pauline_text_am }}</p>
        @endif
        @if(filled($entry->pauline_text_en))
        <p class="text-sm text-primary/75 leading-relaxed italic">{{ $entry->pauline_text_en }}</p>
        @endif
    </div>
    @endif

    {{-- 2. Catholic Epistle --}}
    @if(filled($entry->catholic_book_am) || filled($entry->catholic_book_en))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">2. {{ __('app.lectionary_catholic') }}</h2>
        <div class="text-sm text-muted-text mb-3">
            @if(filled($entry->catholic_book_am))
            <span class="font-medium">{{ $entry->catholic_book_am }}</span>
            @endif
            @if(filled($entry->catholic_chapter))
            {{ $entry->catholic_chapter }}
            @endif
            @if(filled($entry->catholic_verses))
            : {{ $entry->catholic_verses }}
            @endif
            @if(filled($entry->catholic_book_en))
            <span class="ml-2 opacity-75">({{ $entry->catholic_book_en }} @if(filled($entry->catholic_chapter)){{ $entry->catholic_chapter }}@endif @if(filled($entry->catholic_verses)): {{ $entry->catholic_verses }}@endif)</span>
            @endif
        </div>
        @if(filled($entry->catholic_text_am))
        <p class="text-sm text-primary leading-relaxed mb-2">{{ $entry->catholic_text_am }}</p>
        @endif
        @if(filled($entry->catholic_text_en))
        <p class="text-sm text-primary/75 leading-relaxed italic">{{ $entry->catholic_text_en }}</p>
        @endif
    </div>
    @endif

    {{-- 3. Acts --}}
    @if(filled($entry->acts_chapter))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">3. {{ __('app.lectionary_acts') }}</h2>
        <div class="text-sm text-muted-text mb-3">
            Acts {{ $entry->acts_chapter }}
            @if(filled($entry->acts_verses))
            : {{ $entry->acts_verses }}
            @endif
        </div>
        @if(filled($entry->acts_text_am))
        <p class="text-sm text-primary leading-relaxed mb-2">{{ $entry->acts_text_am }}</p>
        @endif
        @if(filled($entry->acts_text_en))
        <p class="text-sm text-primary/75 leading-relaxed italic">{{ $entry->acts_text_en }}</p>
        @endif
    </div>
    @endif

    {{-- 4. Mesbak/Psalm --}}
    @if(filled($entry->mesbak_psalm))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">4. {{ __('app.lectionary_mesbak') }}</h2>
        <div class="text-sm text-muted-text mb-3">
            Psalm {{ $entry->mesbak_psalm }}
            @if(filled($entry->mesbak_verses))
            : {{ $entry->mesbak_verses }}
            @endif
        </div>

        {{-- Ge'ez lines --}}
        @if(filled($entry->mesbak_geez_1) || filled($entry->mesbak_geez_2) || filled($entry->mesbak_geez_3))
        <div class="space-y-1 mb-3 font-mono text-sm text-primary leading-relaxed">
            @if(filled($entry->mesbak_geez_1))
            <p>{{ $entry->mesbak_geez_1 }}</p>
            @endif
            @if(filled($entry->mesbak_geez_2))
            <p>{{ $entry->mesbak_geez_2 }}</p>
            @endif
            @if(filled($entry->mesbak_geez_3))
            <p>{{ $entry->mesbak_geez_3 }}</p>
            @endif
        </div>
        @endif

        {{-- Translations --}}
        @if(filled($entry->mesbak_text_am))
        <p class="text-sm text-primary leading-relaxed mb-2">{{ $entry->mesbak_text_am }}</p>
        @endif
        @if(filled($entry->mesbak_text_en))
        <p class="text-sm text-primary/75 leading-relaxed italic">{{ $entry->mesbak_text_en }}</p>
        @endif
    </div>
    @endif

    {{-- 5. Gospel --}}
    @if(filled($entry->gospel_book_am) || filled($entry->gospel_book_en))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">5. {{ __('app.lectionary_gospel') }}</h2>
        <div class="text-sm text-muted-text mb-3">
            @if(filled($entry->gospel_book_am))
            <span class="font-medium">{{ $entry->gospel_book_am }}</span>
            @endif
            @if(filled($entry->gospel_chapter))
            {{ $entry->gospel_chapter }}
            @endif
            @if(filled($entry->gospel_verses))
            : {{ $entry->gospel_verses }}
            @endif
            @if(filled($entry->gospel_book_en))
            <span class="ml-2 opacity-75">({{ $entry->gospel_book_en }} @if(filled($entry->gospel_chapter)){{ $entry->gospel_chapter }}@endif @if(filled($entry->gospel_verses)): {{ $entry->gospel_verses }}@endif)</span>
            @endif
        </div>
        @if(filled($entry->gospel_text_am))
        <p class="text-sm text-primary leading-relaxed mb-2">{{ $entry->gospel_text_am }}</p>
        @endif
        @if(filled($entry->gospel_text_en))
        <p class="text-sm text-primary/75 leading-relaxed italic">{{ $entry->gospel_text_en }}</p>
        @endif
    </div>
    @endif

    {{-- 6. Qiddase --}}
    @if(filled($entry->qiddase_am) || filled($entry->qiddase_en))
    <div class="border-t border-border pt-4">
        <h2 class="text-lg font-semibold text-primary mb-3">6. {{ __('app.lectionary_qiddase') }}</h2>
        <p class="text-sm text-primary mb-1">{{ $entry->qiddase_am }}</p>
        <p class="text-sm text-primary/75">{{ $entry->qiddase_en }}</p>
    </div>
    @endif
</div>

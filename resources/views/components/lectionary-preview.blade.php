@props(['entry', 'monthNames'])

@php
$monthAm = $monthNames[$entry->month] ? explode(' / ', $monthNames[$entry->month])[1] : '';
$monthEn = $monthNames[$entry->month] ? explode(' / ', $monthNames[$entry->month])[0] : '';

// Amharic numerals
$amharicNumerals = [
    '0' => '፩', '1' => '፩', '2' => '፪', '3' => '፫', '4' => '፬', '5' => '፭',
    '6' => '፮', '7' => '፯', '8' => '፰', '9' => '፱', '10' => '፲', '11' => '፲፩',
    '12' => '፲፪', '13' => '፲፫', '14' => '፲፬', '15' => '፲፭', '16' => '፲፮',
    '17' => '፲፯', '18' => '፲፰', '19' => '፲፱', '20' => '፲', '100' => '፻'
];

function toAmharicNumeral($num) {
    global $amharicNumerals;
    $num = (int)$num;

    if ($num <= 0) return '';
    if ($num < 20) return $amharicNumerals[$num] ?? '';
    if ($num < 100) {
        $tens = intval($num / 10);
        $ones = $num % 10;
        return $amharicNumerals[$tens * 10] . ($ones > 0 ? $amharicNumerals[$ones] : '');
    }
    if ($num < 1000) {
        $hundreds = intval($num / 100);
        $remainder = $num % 100;
        $result = str_repeat('፻', $hundreds);
        if ($remainder > 0) {
            $result .= toAmharicNumeral($remainder);
        }
        return $result;
    }

    return $num;
}

// Helper function to parse verses with numbers
function parseVerses($text) {
    if (!filled($text)) return [];

    $verses = [];
    $text = trim($text);

    // Match patterns like "5 verse text" or "5verse text"
    preg_match_all('/(\d+)\s+(.+?)(?=\d+\s+|\Z)/s', $text, $matches, PREG_SET_ORDER);

    if (!empty($matches)) {
        foreach ($matches as $match) {
            $verses[] = [
                'number' => $match[1],
                'text' => trim($match[2])
            ];
        }
    } else {
        // If no verse numbers found, return text as single block
        $verses[] = [
            'number' => null,
            'text' => $text
        ];
    }

    return $verses;
}
@endphp

<div class="space-y-6 max-w-2xl">
    {{-- Header --}}
    <div class="text-center border-b-2 border-accent pb-6">
        <h1 class="text-4xl font-bold text-primary">{{ $monthAm }} {{ $entry->day }}</h1>
        <p class="text-sm text-muted-text mt-2">{{ $monthEn }} {{ $entry->day }}</p>
    </div>

    {{-- Title & Description --}}
    @if(filled($entry->title_am) || filled($entry->title_en))
    <div class="space-y-3 text-center">
        @if(filled($entry->title_am))
        <h2 class="text-2xl font-semibold text-primary">{{ $entry->title_am }}</h2>
        @endif
        @if(filled($entry->title_en))
        <h3 class="text-xl font-semibold text-primary/80">{{ $entry->title_en }}</h3>
        @endif
        @if(filled($entry->description_am) || filled($entry->description_en))
        <div class="mt-4 p-4 bg-surface/50 rounded-lg border border-border/50">
            @if(filled($entry->description_am))
            <p class="text-sm text-primary leading-relaxed">{{ $entry->description_am }}</p>
            @endif
            @if(filled($entry->description_en))
            <p class="text-sm text-primary/75 leading-relaxed mt-2 italic">{{ $entry->description_en }}</p>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- 1. Pauline Epistle --}}
    @if(filled($entry->pauline_book_am) || filled($entry->pauline_chapter))
    <div class="border-t border-border pt-5">
        <div class="mb-4 pb-2 border-b border-border/50">
            <h3 class="text-base font-bold text-primary">1. {{ __('app.lectionary_pauline') }}</h3>
            <p class="text-xs text-muted-text mt-1">
                @if(filled($entry->pauline_book_am))<span class="font-semibold">{{ $entry->pauline_book_am }}</span>@endif
                @if(filled($entry->pauline_chapter))<span>{{ $entry->pauline_chapter }}</span>@endif
                @if(filled($entry->pauline_verses))<span>: {{ $entry->pauline_verses }}</span>@endif
                @if(filled($entry->pauline_book_en))<span class="text-muted-text/70"> ({{ $entry->pauline_book_en }}@if(filled($entry->pauline_chapter)) {{ $entry->pauline_chapter }}@endif@if(filled($entry->pauline_verses)): {{ $entry->pauline_verses }}@endif)</span>@endif
            </p>
        </div>

        {{-- Amharic Text --}}
        @if(filled($entry->pauline_text_am))
        <div class="mb-4 text-sm text-primary leading-loose whitespace-pre-wrap">
            {{ $entry->pauline_text_am }}
        </div>
        @endif

        {{-- English Text --}}
        @if(filled($entry->pauline_text_en))
        <div class="text-sm text-primary/75 leading-loose italic whitespace-pre-wrap">
            {{ $entry->pauline_text_en }}
        </div>
        @endif
    </div>
    @endif

    {{-- 2. Catholic Epistle --}}
    @if(filled($entry->catholic_book_am) || filled($entry->catholic_chapter))
    <div class="border-t border-border pt-5">
        <div class="mb-4 pb-2 border-b border-border/50">
            <h3 class="text-base font-bold text-primary">2. {{ __('app.lectionary_catholic') }}</h3>
            <p class="text-xs text-muted-text mt-1">
                @if(filled($entry->catholic_book_am))<span class="font-semibold">{{ $entry->catholic_book_am }}</span>@endif
                @if(filled($entry->catholic_chapter))<span>{{ $entry->catholic_chapter }}</span>@endif
                @if(filled($entry->catholic_verses))<span>: {{ $entry->catholic_verses }}</span>@endif
                @if(filled($entry->catholic_book_en))<span class="text-muted-text/70"> ({{ $entry->catholic_book_en }}@if(filled($entry->catholic_chapter)) {{ $entry->catholic_chapter }}@endif@if(filled($entry->catholic_verses)): {{ $entry->catholic_verses }}@endif)</span>@endif
            </p>
        </div>

        @if(filled($entry->catholic_text_am))
        <div class="mb-4 text-sm text-primary leading-loose whitespace-pre-wrap">
            {{ $entry->catholic_text_am }}
        </div>
        @endif

        @if(filled($entry->catholic_text_en))
        <div class="text-sm text-primary/75 leading-loose italic whitespace-pre-wrap">
            {{ $entry->catholic_text_en }}
        </div>
        @endif
    </div>
    @endif

    {{-- 3. Acts --}}
    @if(filled($entry->acts_chapter))
    <div class="border-t border-border pt-5">
        <div class="mb-4 pb-2 border-b border-border/50">
            <h3 class="text-base font-bold text-primary">3. {{ __('app.lectionary_acts') }}</h3>
            <p class="text-xs text-muted-text mt-1">
                Acts {{ $entry->acts_chapter }}@if(filled($entry->acts_verses)): {{ $entry->acts_verses }}@endif
            </p>
        </div>

        @if(filled($entry->acts_text_am))
        <div class="mb-4 text-sm text-primary leading-loose whitespace-pre-wrap">
            {{ $entry->acts_text_am }}
        </div>
        @endif

        @if(filled($entry->acts_text_en))
        <div class="text-sm text-primary/75 leading-loose italic whitespace-pre-wrap">
            {{ $entry->acts_text_en }}
        </div>
        @endif
    </div>
    @endif

    {{-- 4. Mesbak/Psalm --}}
    @if(filled($entry->mesbak_psalm))
    <div class="border-t border-border pt-5">
        <div class="mb-3 pb-2 border-b border-border/50">
            <h3 class="text-base font-bold text-primary">4. {{ __('app.lectionary_mesbak') }}</h3>
            <p class="text-xs text-muted-text mt-1">
                Psalm {{ $entry->mesbak_psalm }}@if(filled($entry->mesbak_verses)): {{ $entry->mesbak_verses }}@endif
            </p>
        </div>

        {{-- Ge'ez lines with verse numbers --}}
        @if(filled($entry->mesbak_geez_1) || filled($entry->mesbak_geez_2) || filled($entry->mesbak_geez_3))
        <div class="mb-4 text-sm text-primary leading-relaxed">
            @if(filled($entry->mesbak_geez_1))
            <p class="m-0"><span class="font-semibold">፩</span> {{ $entry->mesbak_geez_1 }}</p>
            @endif
            @if(filled($entry->mesbak_geez_2))
            <p class="m-0"><span class="font-semibold">፪</span> {{ $entry->mesbak_geez_2 }}</p>
            @endif
            @if(filled($entry->mesbak_geez_3))
            <p class="m-0"><span class="font-semibold">፫</span> {{ $entry->mesbak_geez_3 }}</p>
            @endif
        </div>
        @endif

        {{-- Amharic Translation --}}
        @if(filled($entry->mesbak_text_am))
        <div class="mb-3">
            <p class="text-xs text-muted-text/70 mb-1">አማርኛ</p>
            <div class="space-y-1 text-sm text-primary leading-relaxed">
                @foreach(preg_split('/\n+/', trim($entry->mesbak_text_am)) as $line)
                    @if(filled(trim($line)))
                    <p class="m-0">{{ trim($line) }}</p>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

        {{-- English Translation --}}
        @if(filled($entry->mesbak_text_en))
        <div>
            <p class="text-xs text-muted-text/70 mb-1">English</p>
            <div class="space-y-1 text-sm text-primary/75 leading-relaxed italic">
                @foreach(preg_split('/\n+/', trim($entry->mesbak_text_en)) as $line)
                    @if(filled(trim($line)))
                    <p class="m-0">{{ trim($line) }}</p>
                    @endif
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- 5. Gospel --}}
    @if(filled($entry->gospel_book_am) || filled($entry->gospel_chapter))
    <div class="border-t border-border pt-5">
        <div class="mb-4 pb-2 border-b border-border/50">
            <h3 class="text-base font-bold text-primary">5. {{ __('app.lectionary_gospel') }}</h3>
            <p class="text-xs text-muted-text mt-1">
                @if(filled($entry->gospel_book_am))<span class="font-semibold">{{ $entry->gospel_book_am }}</span>@endif
                @if(filled($entry->gospel_chapter))<span>{{ $entry->gospel_chapter }}</span>@endif
                @if(filled($entry->gospel_verses))<span>: {{ $entry->gospel_verses }}</span>@endif
                @if(filled($entry->gospel_book_en))<span class="text-muted-text/70"> ({{ $entry->gospel_book_en }}@if(filled($entry->gospel_chapter)) {{ $entry->gospel_chapter }}@endif@if(filled($entry->gospel_verses)): {{ $entry->gospel_verses }}@endif)</span>@endif
            </p>
        </div>

        @if(filled($entry->gospel_text_am))
        <div class="mb-4 text-sm text-primary leading-loose whitespace-pre-wrap">
            {{ $entry->gospel_text_am }}
        </div>
        @endif

        @if(filled($entry->gospel_text_en))
        <div class="text-sm text-primary/75 leading-loose italic whitespace-pre-wrap">
            {{ $entry->gospel_text_en }}
        </div>
        @endif
    </div>
    @endif

    {{-- 6. Qiddase --}}
    @if(filled($entry->qiddase_am) || filled($entry->qiddase_en))
    <div class="border-t border-border pt-5">
        <h3 class="text-base font-bold text-primary mb-3">6. {{ __('app.lectionary_qiddase') }}</h3>
        <div class="space-y-2 text-sm">
            @if(filled($entry->qiddase_am))
            <p class="text-primary">{{ $entry->qiddase_am }}</p>
            @endif
            @if(filled($entry->qiddase_en))
            <p class="text-primary/75 italic">{{ $entry->qiddase_en }}</p>
            @endif
        </div>
    </div>
    @endif
</div>

@extends('layouts.member')

@section('title', __('app.week_page_title', ['number' => $weeklyTheme->week_number, 'name' => localized($weeklyTheme, 'name') ?? $weeklyTheme->name_en ?? '-']) . ' - ' . __('app.app_name'))

@php
    $locale = app()->getLocale();
    $isAm = $locale === 'am';
    $themeName = localized($weeklyTheme, 'name') ?? $weeklyTheme->name_en ?? $weeklyTheme->name_geez ?? '-';
    $themeMeaning = $isAm && $weeklyTheme->meaning_am ? $weeklyTheme->meaning_am : $weeklyTheme->meaning;
    $themeDescription = $isAm && $weeklyTheme->description_am ? $weeklyTheme->description_am : $weeklyTheme->description;
    $themeSummary = $isAm && $weeklyTheme->summary_am ? $weeklyTheme->summary_am : $weeklyTheme->theme_summary;

    // Date range
    $weekStart = $weeklyTheme->week_start_date ? $weeklyTheme->week_start_date->translatedFormat('M d') : null;
    $weekEnd = $weeklyTheme->week_end_date ? $weeklyTheme->week_end_date->translatedFormat('M d, Y') : null;
    $dateRange = ($weekStart && $weekEnd) ? ($weekStart . ' – ' . $weekEnd) : null;

    // Build grouped content — same logic as home
    $contentGroups = [];

    // Group: Scripture Readings
    $readingItems = [];
    $readingIcons = ['bi-1-circle-fill', 'bi-2-circle-fill', 'bi-3-circle-fill'];
    $readingLabelKeys = [1 => 'app.lectionary_pauline', 2 => 'app.lectionary_catholic', 3 => 'app.lectionary_acts'];
    for ($i = 1; $i <= 3; $i++) {
        $ref = $isAm
            ? ($weeklyTheme->{"reading_{$i}_reference_am"} ?? $weeklyTheme->{"reading_{$i}_reference"})
            : ($weeklyTheme->{"reading_{$i}_reference"} ?? $weeklyTheme->{"reading_{$i}_reference_am"});
        $text = $isAm
            ? ($weeklyTheme->{"reading_{$i}_text_am"} ?? $weeklyTheme->{"reading_{$i}_text_en"})
            : ($weeklyTheme->{"reading_{$i}_text_en"} ?? $weeklyTheme->{"reading_{$i}_text_am"});
        if ($ref || $text) {
            $readingItems[] = ['key' => "reading_{$i}", 'label' => __($readingLabelKeys[$i]), 'ref' => $ref, 'text' => $text, 'icon' => $readingIcons[$i - 1]];
        }
    }
    if (!empty($readingItems)) {
        $contentGroups[] = ['label' => __('app.week_scripture_readings'), 'icon' => 'bi-book', 'items' => $readingItems];
    }

    // Group: Psalm & Gospel
    $pgItems = [];
    $psalmRef = $isAm ? ($weeklyTheme->psalm_reference_am ?? $weeklyTheme->psalm_reference) : ($weeklyTheme->psalm_reference ?? $weeklyTheme->psalm_reference_am);
    $psalmText = $isAm ? ($weeklyTheme->psalm_text_am ?? $weeklyTheme->psalm_text_en) : ($weeklyTheme->psalm_text_en ?? $weeklyTheme->psalm_text_am);
    if ($psalmRef || $psalmText) {
        $pgItems[] = ['key' => 'psalm', 'label' => __('app.psalm'), 'ref' => $psalmRef, 'text' => $psalmText, 'icon' => 'bi-book-half'];
    }
    $gospelRef = $isAm ? ($weeklyTheme->gospel_reference_am ?? $weeklyTheme->gospel_reference) : ($weeklyTheme->gospel_reference ?? $weeklyTheme->gospel_reference_am);
    $gospelText = $isAm ? ($weeklyTheme->gospel_text_am ?? $weeklyTheme->gospel_text_en) : ($weeklyTheme->gospel_text_en ?? $weeklyTheme->gospel_text_am);
    if ($gospelRef || $gospelText) {
        $pgItems[] = ['key' => 'gospel', 'label' => __('app.gospel'), 'ref' => $gospelRef, 'text' => $gospelText, 'icon' => 'bi-journal-text'];
    }
    if (!empty($pgItems)) {
        $contentGroups[] = ['label' => __('app.week_psalm_and_gospel'), 'icon' => 'bi-book-half', 'items' => $pgItems];
    }

    // Group: Liturgy (Anaphora)
    $liturgyItems = [];
    $liturgyName = $isAm ? ($weeklyTheme->liturgy_am ?? $weeklyTheme->liturgy) : ($weeklyTheme->liturgy ?? $weeklyTheme->liturgy_am);
    $liturgyText = $isAm ? ($weeklyTheme->liturgy_text_am ?? $weeklyTheme->liturgy_text_en) : ($weeklyTheme->liturgy_text_en ?? $weeklyTheme->liturgy_text_am);
    if ($liturgyName || $liturgyText) {
        $liturgyItems[] = ['key' => 'liturgy', 'label' => __('app.liturgy'), 'ref' => $liturgyName, 'text' => $liturgyText, 'icon' => 'bi-brightness-high'];
    }
    if (!empty($liturgyItems)) {
        $contentGroups[] = ['label' => __('app.week_liturgy_section'), 'icon' => 'bi-brightness-high', 'items' => $liturgyItems];
    }
@endphp

@section('content')
<div class="max-w-2xl mx-auto px-4 py-4 space-y-4">

    {{-- Back button --}}
    <div>
        <a href="javascript:history.back()" class="inline-flex items-center gap-1.5 text-sm text-muted-text hover:text-primary transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('app.back') }}
        </a>
    </div>

    {{-- Week header card --}}
    <div class="rounded-2xl bg-card shadow-lg overflow-hidden">
        <div class="relative overflow-hidden bg-gradient-to-br from-[#0a6286] via-[#134e5e] to-[#0a6286]">
            <div class="absolute -top-20 -right-20 w-56 h-56 rounded-full bg-easter-gold/15 blur-[70px] pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-40 h-40 rounded-full bg-white/5 blur-[60px] pointer-events-none"></div>
            <div class="relative px-4 py-5 sm:px-5">
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="px-2.5 py-1 rounded-full bg-easter-gold/20 text-easter-gold font-bold text-[11px] tracking-wide uppercase">{{ __('app.week', ['number' => $weeklyTheme->week_number]) }}</span>
                    <span class="text-sm text-white/70 font-semibold">{{ $themeName }}</span>
                </div>
                <h1 class="text-lg font-black text-white leading-snug">{{ $themeMeaning }}</h1>
                @if($dateRange)
                    <p class="text-xs text-white/40 mt-2">{{ $dateRange }}</p>
                @endif
            </div>
        </div>

        {{-- Feature picture --}}
        @if($weeklyTheme->feature_picture)
        <img src="{{ Storage::disk('public')->url($weeklyTheme->feature_picture) }}"
             alt="{{ $themeName }}"
             loading="eager"
             fetchpriority="high"
             decoding="async"
             class="w-full aspect-video object-cover">
        @endif
    </div>

    {{-- Theme description --}}
    @if($themeDescription)
    <div class="rounded-xl bg-accent/5 p-3.5">
        <p class="text-[11px] font-semibold text-muted-text uppercase tracking-wide mb-1.5">{{ __('app.week_about') }}</p>
        <p class="text-sm text-secondary leading-relaxed">{{ $themeDescription }}</p>
    </div>
    @endif

    {{-- Content sections — each group is open by default --}}
    @if(empty($contentGroups) && !$themeDescription && !$themeSummary)
    <div class="rounded-xl bg-muted/20 p-6 text-center">
        <p class="text-sm text-muted-text">{{ __('app.week_no_content') }}</p>
    </div>
    @endif

    <div x-data="{ detail: null }" class="space-y-3">
        @foreach($contentGroups as $group)
        <div class="rounded-2xl bg-card shadow-lg overflow-hidden">
            {{-- Group header --}}
            <div class="flex items-center gap-2 px-4 pt-3.5 pb-2">
                <i class="bi {{ $group['icon'] }} text-accent/70 text-sm"></i>
                <span class="text-xs font-bold text-muted-text uppercase tracking-wider">{{ $group['label'] }}</span>
            </div>
            {{-- Group items --}}
            <div class="px-2.5 pb-2.5 space-y-1">
                @foreach($group['items'] as $item)
                <div class="rounded-lg overflow-hidden">
                    @if($item['text'])
                    {{-- Expandable item --}}
                    <button type="button"
                            class="w-full flex items-center gap-2.5 px-2.5 py-2 text-left rounded-lg transition-colors hover:bg-muted/30"
                            @click="detail = detail === '{{ $item['key'] }}' ? null : '{{ $item['key'] }}'">
                        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                            <i class="bi {{ $item['icon'] }} text-accent text-[13px]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-[13px] font-semibold text-primary">{{ $item['label'] }}</span>
                            @if($item['ref'])
                                <span class="block text-xs text-muted-text truncate mt-0.5">{{ $item['ref'] }}</span>
                            @endif
                        </div>
                        <svg class="w-4 h-4 text-muted-text/50 shrink-0 transition-transform duration-200"
                             :class="detail === '{{ $item['key'] }}' && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="detail === '{{ $item['key'] }}'"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         x-cloak
                         class="mx-2.5 mb-2 mt-1 p-3 rounded-lg bg-muted/15">
                        @if($item['ref'])
                            <p class="text-xs font-medium text-accent mb-1.5">{{ $item['ref'] }}</p>
                        @endif
                        <div class="text-[13px] text-secondary leading-relaxed whitespace-pre-line">{{ $item['text'] }}</div>
                    </div>
                    @else
                    {{-- Reference-only item --}}
                    <div class="flex items-center gap-2.5 px-2.5 py-2">
                        <div class="w-7 h-7 rounded-lg bg-accent/10 flex items-center justify-center shrink-0">
                            <i class="bi {{ $item['icon'] }} text-accent text-[13px]"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <span class="text-[13px] font-semibold text-primary">{{ $item['label'] }}</span>
                            @if($item['ref'])
                                <span class="block text-xs text-muted-text truncate mt-0.5">{{ $item['ref'] }}</span>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    {{-- Theme summary --}}
    @if($themeSummary)
    <div class="rounded-xl bg-muted/20 p-3.5">
        <p class="text-[11px] font-semibold text-muted-text uppercase tracking-wide mb-1.5">{{ __('app.week_summary_label') }}</p>
        <p class="text-sm text-secondary leading-relaxed">{{ $themeSummary }}</p>
    </div>
    @endif

</div>
@endsection

@extends('layouts.admin')

@section('title', __('app.lectionary_admin_title'))

@section('content')
@php
$inputClass = 'w-full px-3 py-2 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent';
$labelClass = 'block text-xs font-medium text-muted-text mb-1';
@endphp

<div class="max-w-3xl" x-data="{ openSection: 'pauline' }">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-primary">{{ __('app.lectionary_admin_title') }}</h1>
            <p class="text-sm text-muted-text mt-0.5">
                {{ __('app.lectionary_progress', ['count' => $totalCount]) }}
            </p>
        </div>
        {{-- Progress bar --}}
        <div class="w-28 text-right">
            <div class="h-2 rounded-full bg-muted overflow-hidden mt-1">
                <div class="h-full bg-accent rounded-full transition-all duration-500"
                     style="width: {{ min(100, round($totalCount / 365 * 100)) }}%"></div>
            </div>
            <p class="text-[11px] text-muted-text mt-1">{{ round($totalCount / 365 * 100) }}%</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Month selector --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm p-4 mb-4">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-3">{{ __('app.lectionary_month') }}</p>
        <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
            @foreach($monthNames as $m => $name)
            @php [$nameEn, $nameAm] = explode(' / ', $name); @endphp
            <a href="{{ route('admin.lectionary.index', ['month' => $m, 'day' => 0]) }}"
               class="flex flex-col items-center py-2 px-1 rounded-xl text-xs font-semibold transition-all duration-150 border
                      {{ $selectedMonth === $m
                         ? 'bg-accent text-on-accent border-accent shadow-md'
                         : 'bg-surface text-primary hover:bg-muted border-border' }}">
                <span>{{ $m }}</span>
                <span class="text-[10px] font-normal mt-0.5 opacity-80 leading-tight text-center">{{ $nameAm }}</span>
            </a>
            @endforeach
        </div>
    </div>

    {{-- Day grid for selected month --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm p-4 mb-4">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-3">
            {{ __('app.lectionary_day') }} —
            @php [$nameEn, $nameAm] = explode(' / ', $monthNames[$selectedMonth]); @endphp
            <span class="text-primary">{{ $nameAm }} / {{ $nameEn }}</span>
        </p>
        <div class="grid grid-cols-6 gap-2">
            @for($d = 1; $d <= $maxDay; $d++)
            @php
                $complete = in_array($d, $completeDays);
                $draft    = in_array($d, $filledDays) && !$complete;
            @endphp
            <a href="{{ route('admin.lectionary.index', ['month' => $selectedMonth, 'day' => $d]) }}"
               class="relative h-11 flex flex-col items-center justify-center rounded-xl text-sm font-semibold transition-all duration-150 border
                      {{ $selectedDay === $d
                         ? 'bg-accent text-on-accent border-accent shadow-md scale-105'
                         : ($complete
                            ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800 hover:border-green-400'
                            : ($draft
                               ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 hover:border-amber-400'
                               : 'bg-surface text-primary hover:bg-muted border-border')) }}">
                {{ $d }}
                @if($selectedDay !== $d)
                    @if($complete)
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mt-0.5"></span>
                    @elseif($draft)
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 mt-0.5"></span>
                    @endif
                @endif
            </a>
            @endfor
        </div>
        {{-- Legend --}}
        <div class="flex items-center gap-4 mt-3">
            <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ count($completeDays) }} complete
            </span>
            <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                <span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ count($filledDays) - count($completeDays) }} draft
            </span>
            <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                <span class="w-2 h-2 rounded-full bg-border"></span> {{ $maxDay - count($filledDays) }} empty
            </span>
        </div>
    </div>

    {{-- Entry area --}}
    @if($selectedDay > 0)

        @php
            $monthAm = explode(' / ', $monthNames[$selectedMonth])[1];
        @endphp

        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden">
            {{-- Entry header --}}
            <div class="px-5 py-3 bg-gradient-to-r from-accent/10 to-transparent border-b border-border flex items-center justify-between">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ $monthAm }} {{ $selectedDay }}
                    @if($entry)
                        @if($entry->hasContent())
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                ✓ Complete
                            </span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                Draft
                            </span>
                        @endif
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ __('app.lectionary_no_entry') }}
                        </span>
                    @endif
                </h2>
                @if($entry)
                    <form method="POST" action="{{ route('admin.lectionary.destroy', $entry) }}"
                          onsubmit="return confirm('{{ __('app.lectionary_delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="{{ __('app.delete') }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                @endif
            </div>

            {{-- Form --}}
            <form method="POST"
                  action="{{ $entry ? route('admin.lectionary.update', $entry) : route('admin.lectionary.store') }}"
                  class="p-5 space-y-3">
                @csrf
                @if($entry) @method('PUT') @endif

                @unless($entry)
                    <input type="hidden" name="month" value="{{ $selectedMonth }}">
                    <input type="hidden" name="day"   value="{{ $selectedDay }}">
                @endunless

                {{-- ── TITLE & DESCRIPTION ── --}}
                <div class="rounded-xl border border-border overflow-hidden">
                    <div class="px-4 py-3 bg-surface border-b border-border">
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_title') }} & {{ __('app.lectionary_description') }}</span>
                    </div>
                    <div class="px-4 pb-4 pt-3 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_title') }} (አማርኛ)</label>
                                <input type="text" name="title_am" value="{{ old('title_am', $entry?->title_am) }}"
                                       placeholder="ለምሳሌ፦ የጌታ ስቅለት" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_title') }} (English)</label>
                                <input type="text" name="title_en" value="{{ old('title_en', $entry?->title_en) }}"
                                       placeholder="e.g. The Crucifixion of our Lord" class="{{ $inputClass }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_description') }} (አማርኛ)</label>
                                <textarea name="description_am" rows="3" placeholder="የዕለቱ ጭብጥ ወይም መግለጫ..."
                                          class="{{ $inputClass }}">{{ old('description_am', $entry?->description_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_description') }} (English)</label>
                                <textarea name="description_en" rows="3" placeholder="Theme or context for the day..."
                                          class="{{ $inputClass }}">{{ old('description_en', $entry?->description_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 1. PAULINE EPISTLE ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">1</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_pauline') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_pauline_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_am') }}</label>
                                <input type="text" name="pauline_book_am" value="{{ old('pauline_book_am', $entry?->pauline_book_am) }}"
                                       placeholder="ሮሜ" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_en') }}</label>
                                <input type="text" name="pauline_book_en" value="{{ old('pauline_book_en', $entry?->pauline_book_en) }}"
                                       placeholder="Romans" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                                <input type="number" name="pauline_chapter" min="1" max="150"
                                       value="{{ old('pauline_chapter', $entry?->pauline_chapter) }}"
                                       placeholder="6" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                                <input type="text" name="pauline_verses" value="{{ old('pauline_verses', $entry?->pauline_verses) }}"
                                       placeholder="5-12" class="{{ $inputClass }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                                <textarea name="pauline_text_am" rows="5" placeholder="ሞቱንም በሚመስል..."
                                          class="{{ $inputClass }}">{{ old('pauline_text_am', $entry?->pauline_text_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                                <textarea name="pauline_text_en" rows="5" placeholder="For if we have been..."
                                          class="{{ $inputClass }}">{{ old('pauline_text_en', $entry?->pauline_text_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 2. CATHOLIC EPISTLE ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">2</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_catholic') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_catholic_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_am') }}</label>
                                <input type="text" name="catholic_book_am" value="{{ old('catholic_book_am', $entry?->catholic_book_am) }}"
                                       placeholder="1ኛ ጴጥሮስ" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_en') }}</label>
                                <input type="text" name="catholic_book_en" value="{{ old('catholic_book_en', $entry?->catholic_book_en) }}"
                                       placeholder="1 Peter" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                                <input type="number" name="catholic_chapter" min="1" max="150"
                                       value="{{ old('catholic_chapter', $entry?->catholic_chapter) }}"
                                       placeholder="2" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                                <input type="text" name="catholic_verses" value="{{ old('catholic_verses', $entry?->catholic_verses) }}"
                                       placeholder="21-25" class="{{ $inputClass }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                                <textarea name="catholic_text_am" rows="5" placeholder="የተጠራችሁለት..."
                                          class="{{ $inputClass }}">{{ old('catholic_text_am', $entry?->catholic_text_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                                <textarea name="catholic_text_en" rows="5" placeholder="For to this you were called..."
                                          class="{{ $inputClass }}">{{ old('catholic_text_en', $entry?->catholic_text_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 3. ACTS ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">3</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_acts') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_acts_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                                <input type="number" name="acts_chapter" min="1" max="28"
                                       value="{{ old('acts_chapter', $entry?->acts_chapter) }}"
                                       placeholder="10" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                                <input type="text" name="acts_verses" value="{{ old('acts_verses', $entry?->acts_verses) }}"
                                       placeholder="36-44" class="{{ $inputClass }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                                <textarea name="acts_text_am" rows="5" placeholder="የሁሉ ጌታ..."
                                          class="{{ $inputClass }}">{{ old('acts_text_am', $entry?->acts_text_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                                <textarea name="acts_text_en" rows="5" placeholder="The word that God sent..."
                                          class="{{ $inputClass }}">{{ old('acts_text_en', $entry?->acts_text_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 4. MESBAK ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">4</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_mesbak') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_mesbak_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_psalm') }}</label>
                                <input type="number" name="mesbak_psalm" min="1" max="151"
                                       value="{{ old('mesbak_psalm', $entry?->mesbak_psalm) }}"
                                       placeholder="73" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                                <input type="text" name="mesbak_verses" value="{{ old('mesbak_verses', $entry?->mesbak_verses) }}"
                                       placeholder="12-13" class="{{ $inputClass }}">
                            </div>
                        </div>
                        {{-- 3 text fields: Ge'ez, Amharic, English --}}
                        <div>
                            <label class="{{ $labelClass }}">{{ __('app.lectionary_geez') }}</label>
                            <textarea name="mesbak_text_geez" rows="3" placeholder="እግዚአብሔርሰ ንጉሥ ውእቱ..."
                                      class="{{ $inputClass }} font-mono">{{ old('mesbak_text_geez', $entry?->mesbak_text_geez) }}</textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                                <textarea name="mesbak_text_am" rows="3" placeholder="እግዚአብሔር ግን ከዓለም..."
                                          class="{{ $inputClass }}">{{ old('mesbak_text_am', $entry?->mesbak_text_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                                <textarea name="mesbak_text_en" rows="3" placeholder="Yet God is my king..."
                                          class="{{ $inputClass }}">{{ old('mesbak_text_en', $entry?->mesbak_text_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 5. GOSPEL ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">5</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_gospel') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_gospel_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_am') }}</label>
                                <input type="text" name="gospel_book_am" value="{{ old('gospel_book_am', $entry?->gospel_book_am) }}"
                                       placeholder="ዮሐንስ" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_book_en') }}</label>
                                <input type="text" name="gospel_book_en" value="{{ old('gospel_book_en', $entry?->gospel_book_en) }}"
                                       placeholder="John" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                                <input type="number" name="gospel_chapter" min="1" max="28"
                                       value="{{ old('gospel_chapter', $entry?->gospel_chapter) }}"
                                       placeholder="19" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                                <input type="text" name="gospel_verses" value="{{ old('gospel_verses', $entry?->gospel_verses) }}"
                                       placeholder="16-24" class="{{ $inputClass }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                                <textarea name="gospel_text_am" rows="5" placeholder="ስለዚህ በዚያን ጊዜ..."
                                          class="{{ $inputClass }}">{{ old('gospel_text_am', $entry?->gospel_text_am) }}</textarea>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                                <textarea name="gospel_text_en" rows="5" placeholder="So he delivered him..."
                                          class="{{ $inputClass }}">{{ old('gospel_text_en', $entry?->gospel_text_en) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── 6. QIDDASE ── --}}
                <div x-data="{ open: true }" class="rounded-xl border border-border overflow-hidden">
                    <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-surface hover:bg-muted transition text-left">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-accent/10 text-accent text-xs font-bold flex items-center justify-center">6</span>
                            <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_qiddase') }}</span>
                            <span class="text-xs text-muted-text">{{ __('app.lectionary_qiddase_am') }}</span>
                        </div>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse class="px-4 pb-4 pt-3 space-y-3 border-t border-border">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_qiddase_am') }}</label>
                                <input type="text" name="qiddase_am" value="{{ old('qiddase_am', $entry?->qiddase_am) }}"
                                       placeholder="የቅዱስ ዮሐንስ አፈወርቅ ቅዳሴ" class="{{ $inputClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">{{ __('app.lectionary_qiddase_en') ?? 'Qiddase (English)' }}</label>
                                <input type="text" name="qiddase_en" value="{{ old('qiddase_en', $entry?->qiddase_en) }}"
                                       placeholder="Anaphora of St. John Chrysostom" class="{{ $inputClass }}">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Save button --}}
                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="px-6 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95 shadow-sm">
                        {{ $entry ? __('app.save_changes') : __('app.lectionary_add') }}
                    </button>
                </div>

            </form>
        </div>

    @else
        {{-- No day selected yet --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-10 text-center text-muted-text text-sm">
            <svg class="w-10 h-10 mx-auto mb-3 text-muted-text/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            {{ __('app.lectionary_select_prompt') }}
        </div>
    @endif

</div>
@endsection

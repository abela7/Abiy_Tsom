@extends('layouts.admin')

@section('title', __('app.lectionary_admin_title'))

@section('content')
@php
$inputClass  = 'w-full px-4 py-3.5 rounded-2xl border border-border bg-surface text-primary text-base focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition';
$smallInput  = 'w-full px-3 py-3 rounded-xl border border-border bg-surface text-primary text-base focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition text-center';
$labelClass  = 'block text-xs font-bold text-muted-text uppercase tracking-wider mb-2';
$taClass     = $inputClass . ' resize-none';

// Section fill status for badge indicators
$paulineFilled  = $entry && filled($entry->pauline_book_am)  && filled($entry->pauline_chapter);
$catholicFilled = $entry && filled($entry->catholic_book_am) && filled($entry->catholic_chapter);
$actsFilled     = $entry && filled($entry->acts_chapter);
$mesbakFilled   = $entry && filled($entry->mesbak_psalm);
$gospelFilled   = $entry && filled($entry->gospel_book_am)   && filled($entry->gospel_chapter);
$qiddaseFilled  = $entry && filled($entry->qiddase_am);
$titleFilled    = $entry && (filled($entry->title_am) || filled($entry->title_en));

$progressPct = min(100, round($totalCount / 365 * 100));
$monthComplete = count($completeDays);
$monthDraft    = count($filledDays) - $monthComplete;
$monthEmpty    = $maxDay - count($filledDays);
@endphp

<style>[x-cloak]{display:none!important}</style>

<div class="max-w-2xl pb-28"
     x-data="{
         monthOpen: {{ $selectedDay > 0 ? 'false' : 'true' }},
         dayOpen:   {{ $selectedDay > 0 ? 'false' : 'true' }},
         sheet: '{{ session('show_preview') ? 'preview' : '' }}',
         submitDelete() { document.getElementById('delete-form')?.submit(); },
         closeSheet() { this.sheet = ''; }
     }"
     @keydown.escape.window="closeSheet()">

    {{-- ── Header ── --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="w-11 h-11 rounded-2xl bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-primary leading-tight">{{ __('app.lectionary_admin_title') }}</h1>
            <p class="text-xs text-muted-text">{{ __('app.lectionary_progress', ['count' => $totalCount]) }}</p>
        </div>
        <div class="shrink-0 text-right">
            <div class="text-lg font-bold text-accent leading-none">{{ $progressPct }}%</div>
            <div class="w-16 h-1.5 rounded-full bg-muted overflow-hidden mt-1">
                <div class="h-full bg-accent rounded-full" style="width: {{ $progressPct }}%"></div>
            </div>
        </div>
    </div>

    {{-- ── Success Banner ── --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-3 px-4 py-3.5 rounded-2xl bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm text-green-700 dark:text-green-400 font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ════════════ MONTH ACCORDION ════════════ --}}
    @php
        [$curMonthEn, $curMonthAm] = explode(' / ', $monthNames[$selectedMonth]);
    @endphp
    <div class="bg-card rounded-2xl border border-border shadow-sm mb-3 overflow-hidden">
        <button type="button" @click="monthOpen = !monthOpen"
                class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/40 transition select-none">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_month') }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-accent px-2.5 py-1 rounded-xl bg-accent/10">{{ $curMonthAm }}</span>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="monthOpen ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>
        <div x-show="monthOpen"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="border-t border-border px-3 pb-3 pt-2">
            <div class="grid grid-cols-4 sm:grid-cols-7 gap-1.5">
                @foreach($monthNames as $m => $name)
                @php [$nameEn, $nameAm] = explode(' / ', $name); @endphp
                <a href="{{ route('admin.lectionary.index', ['month' => $m, 'day' => 0]) }}"
                   class="flex flex-col items-center py-2.5 px-1 rounded-xl text-xs font-semibold transition-all duration-150 border active:scale-95 select-none
                          {{ $selectedMonth === $m
                             ? 'bg-accent text-on-accent border-accent shadow-md'
                             : 'bg-surface text-primary border-border' }}">
                    <span class="text-sm font-bold">{{ $m }}</span>
                    <span class="text-[10px] mt-0.5 opacity-80 leading-tight text-center">{{ $nameAm }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ════════════ DAY ACCORDION ════════════ --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm mb-4 overflow-hidden">
        <button type="button" @click="dayOpen = !dayOpen"
                class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/40 transition select-none">
            <div class="flex items-center gap-3">
                <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_day') }}</span>
            </div>
            <div class="flex items-center gap-2">
                @if($selectedDay > 0)
                <span class="text-sm font-bold text-accent px-2.5 py-1 rounded-xl bg-accent/10">
                    {{ $curMonthAm }} {{ $selectedDay }}
                </span>
                @endif
                {{-- Month progress mini-legend --}}
                <div class="hidden sm:flex items-center gap-2">
                    <span class="flex items-center gap-1 text-[10px] text-muted-text">
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>{{ $monthComplete }}
                    </span>
                    <span class="flex items-center gap-1 text-[10px] text-muted-text">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>{{ $monthDraft }}
                    </span>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="dayOpen ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </button>
        <div x-show="dayOpen"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="border-t border-border px-3 pb-3 pt-2">
            <div class="grid grid-cols-6 sm:grid-cols-10 gap-1.5">
                @for($d = 1; $d <= $maxDay; $d++)
                @php
                    $complete = in_array($d, $completeDays);
                    $draft    = in_array($d, $filledDays) && !$complete;
                    $isActive = $selectedDay === $d;
                @endphp
                <a href="{{ route('admin.lectionary.index', ['month' => $selectedMonth, 'day' => $d]) }}"
                   @click="dayOpen = false"
                   class="relative h-12 flex flex-col items-center justify-center rounded-xl font-bold transition-all duration-150 border active:scale-95 select-none
                          {{ $isActive
                             ? 'bg-accent text-on-accent border-accent shadow-md scale-105 text-sm'
                             : ($complete
                                ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-900/20 dark:text-green-400 dark:border-green-800 text-sm'
                                : ($draft
                                   ? 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800 text-sm'
                                   : 'bg-surface text-primary border-border text-sm')) }}">
                    {{ $d }}
                    @if(!$isActive)
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
            <div class="flex items-center gap-4 mt-2.5 px-1">
                <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                    <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ $monthComplete }} complete
                </span>
                <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span> {{ $monthDraft }} draft
                </span>
                <span class="flex items-center gap-1.5 text-[11px] text-muted-text">
                    <span class="w-2 h-2 rounded-full bg-border"></span> {{ $monthEmpty }} empty
                </span>
            </div>
        </div>
    </div>

    {{-- ════════════ ENTRY FORM ════════════ --}}
    @if($selectedDay > 0)

    @php $monthAm = explode(' / ', $monthNames[$selectedMonth])[1]; @endphp

    {{-- Day card header --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden mb-3">
        <div class="px-4 py-3.5 bg-gradient-to-r from-accent/10 to-transparent flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <div>
                    <h2 class="text-base font-bold text-primary leading-tight">{{ $monthAm }} {{ $selectedDay }}</h2>
                    @if($entry)
                        @if($entry->hasContent() && $paulineFilled && $catholicFilled && $actsFilled && $mesbakFilled && $gospelFilled && $qiddaseFilled)
                            <span class="text-xs font-semibold text-green-600 dark:text-green-400">✓ Complete</span>
                        @else
                            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">In progress</span>
                        @endif
                    @else
                        <span class="text-xs text-muted-text">{{ __('app.lectionary_no_entry') }}</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                @if($entry)
                <button type="button" @click="sheet = 'preview'"
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-primary/60 bg-muted hover:text-primary transition active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </button>
                <button type="button" @click="sheet = 'delete'"
                        class="w-10 h-10 rounded-xl flex items-center justify-center text-red-500 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 transition active:scale-90">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
                @if($entry)
                <form id="delete-form" method="POST" action="{{ route('admin.lectionary.destroy', $entry) }}" class="hidden">
                    @csrf @method('DELETE')
                </form>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST"
          action="{{ $entry ? route('admin.lectionary.update', $entry) : route('admin.lectionary.store') }}"
          class="space-y-3">
        @csrf
        @if($entry) @method('PUT') @endif
        @unless($entry)
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="day"   value="{{ $selectedDay }}">
        @endunless

        {{-- ── Title & Description (flat) ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $titleFilled ? 'true' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $titleFilled ? 'bg-green-500' : 'bg-muted' }} text-white text-xs font-bold flex items-center justify-center shrink-0">
                        @if($titleFilled)
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-muted-text">T</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_title') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">& {{ __('app.lectionary_description') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
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
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_description') }} (አማርኛ)</label>
                    <textarea name="description_am" rows="3" placeholder="የዕለቱ ጭብጥ ወይም መግለጫ..."
                              class="{{ $taClass }}">{{ old('description_am', $entry?->description_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_description') }} (English)</label>
                    <textarea name="description_en" rows="3" placeholder="Theme or context for the day..."
                              class="{{ $taClass }}">{{ old('description_en', $entry?->description_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 1. PAULINE ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $paulineFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $paulineFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($paulineFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">1</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_pauline') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_pauline_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
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
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                        <input type="number" name="pauline_chapter" min="1" max="150"
                               value="{{ old('pauline_chapter', $entry?->pauline_chapter) }}"
                               placeholder="6" class="{{ $smallInput }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                        <input type="text" name="pauline_verses" value="{{ old('pauline_verses', $entry?->pauline_verses) }}"
                               placeholder="5-12" class="{{ $inputClass }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                    <textarea name="pauline_text_am" rows="5" placeholder="ሞቱንም በሚመስል..."
                              class="{{ $taClass }}">{{ old('pauline_text_am', $entry?->pauline_text_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                    <textarea name="pauline_text_en" rows="5" placeholder="For if we have been..."
                              class="{{ $taClass }}">{{ old('pauline_text_en', $entry?->pauline_text_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 2. CATHOLIC ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $catholicFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $catholicFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($catholicFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">2</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_catholic') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_catholic_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
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
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                        <input type="number" name="catholic_chapter" min="1" max="150"
                               value="{{ old('catholic_chapter', $entry?->catholic_chapter) }}"
                               placeholder="2" class="{{ $smallInput }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                        <input type="text" name="catholic_verses" value="{{ old('catholic_verses', $entry?->catholic_verses) }}"
                               placeholder="21-25" class="{{ $inputClass }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                    <textarea name="catholic_text_am" rows="5" placeholder="የተጠራችሁለት..."
                              class="{{ $taClass }}">{{ old('catholic_text_am', $entry?->catholic_text_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                    <textarea name="catholic_text_en" rows="5" placeholder="For to this you were called..."
                              class="{{ $taClass }}">{{ old('catholic_text_en', $entry?->catholic_text_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 3. ACTS ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $actsFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $actsFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($actsFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">3</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_acts') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_acts_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                        <input type="number" name="acts_chapter" min="1" max="28"
                               value="{{ old('acts_chapter', $entry?->acts_chapter) }}"
                               placeholder="10" class="{{ $smallInput }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                        <input type="text" name="acts_verses" value="{{ old('acts_verses', $entry?->acts_verses) }}"
                               placeholder="36-44" class="{{ $inputClass }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                    <textarea name="acts_text_am" rows="5" placeholder="የሁሉ ጌታ..."
                              class="{{ $taClass }}">{{ old('acts_text_am', $entry?->acts_text_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                    <textarea name="acts_text_en" rows="5" placeholder="The word that God sent..."
                              class="{{ $taClass }}">{{ old('acts_text_en', $entry?->acts_text_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 4. MESBAK ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $mesbakFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $mesbakFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($mesbakFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">4</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_mesbak') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_mesbak_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_psalm') }}</label>
                        <input type="number" name="mesbak_psalm" min="1" max="151"
                               value="{{ old('mesbak_psalm', $entry?->mesbak_psalm) }}"
                               placeholder="73" class="{{ $smallInput }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                        <input type="text" name="mesbak_verses" value="{{ old('mesbak_verses', $entry?->mesbak_verses) }}"
                               placeholder="12-13" class="{{ $inputClass }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_geez') }}</label>
                    <div class="space-y-2">
                        @foreach([1, 2, 3] as $line)
                        <input type="text" name="mesbak_geez_{{ $line }}"
                               value="{{ old('mesbak_geez_'.$line, $entry?->{'mesbak_geez_'.$line}) }}"
                               placeholder="{{ $line === 1 ? 'እግዚአብሔርሰ ንጉሥ ውእቱ እምቅድመ ዓለም።' : ($line === 2 ? 'ወገብረ መድኃኒት በማእከለ ምድር።' : 'አንተ አጽናዕካ ለባሕር በኃይልከ።') }}"
                               class="{{ $inputClass }} font-mono">
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                    <textarea name="mesbak_text_am" rows="4"
                              placeholder="እግዚአብሔር ግን ከዓለም አስቀድሞ ንጉሥ ነው..."
                              class="{{ $taClass }}">{{ old('mesbak_text_am', $entry?->mesbak_text_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                    <textarea name="mesbak_text_en" rows="4"
                              placeholder="Yet God is my king from of old..."
                              class="{{ $taClass }}">{{ old('mesbak_text_en', $entry?->mesbak_text_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 5. GOSPEL ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $gospelFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $gospelFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($gospelFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">5</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_gospel') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_gospel_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
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
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_chapter') }}</label>
                        <input type="number" name="gospel_chapter" min="1" max="28"
                               value="{{ old('gospel_chapter', $entry?->gospel_chapter) }}"
                               placeholder="19" class="{{ $smallInput }}">
                    </div>
                    <div>
                        <label class="{{ $labelClass }}">{{ __('app.lectionary_verses') }}</label>
                        <input type="text" name="gospel_verses" value="{{ old('gospel_verses', $entry?->gospel_verses) }}"
                               placeholder="16-24" class="{{ $inputClass }}">
                    </div>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_am') }}</label>
                    <textarea name="gospel_text_am" rows="5" placeholder="ስለዚህ በዚያን ጊዜ..."
                              class="{{ $taClass }}">{{ old('gospel_text_am', $entry?->gospel_text_am) }}</textarea>
                </div>
                <div>
                    <label class="{{ $labelClass }}">{{ __('app.lectionary_text_en') }}</label>
                    <textarea name="gospel_text_en" rows="5" placeholder="So he delivered him..."
                              class="{{ $taClass }}">{{ old('gospel_text_en', $entry?->gospel_text_en) }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── 6. QIDDASE ── --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
             x-data="{ open: {{ $qiddaseFilled ? 'false' : 'true' }} }">
            <button type="button" @click="open = !open"
                    class="w-full flex items-center justify-between px-4 py-3.5 active:bg-muted/30 transition text-left select-none">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-xl {{ $qiddaseFilled ? 'bg-green-500' : 'bg-accent/10' }} flex items-center justify-center shrink-0">
                        @if($qiddaseFilled)
                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                            <span class="text-accent text-xs font-bold">6</span>
                        @endif
                    </span>
                    <div>
                        <span class="text-sm font-semibold text-primary">{{ __('app.lectionary_qiddase') }}</span>
                        <span class="text-xs text-muted-text ml-1.5">{{ __('app.lectionary_qiddase_am') }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0" :class="open ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open"
                 x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="border-t border-border px-4 pb-4 pt-3 space-y-3">
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

        {{-- Spacer so sticky bar doesn't cover last field --}}
        <div class="h-2"></div>

        {{-- ── Sticky Save Bar ── --}}
        <div class="fixed bottom-0 left-0 right-0 z-20 bg-card/95 backdrop-blur-sm border-t border-border px-4 py-3"
             style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom, 0.75rem))">
            <button type="submit"
                    class="w-full py-4 bg-accent text-on-accent text-base font-bold rounded-2xl shadow-lg shadow-accent/20 hover:opacity-90 active:scale-[0.98] transition">
                {{ $entry ? __('app.save_changes') : __('app.lectionary_add') }}
            </button>
        </div>

    </form>

    @else
    {{-- ── No day selected ── --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm p-10 text-center">
        <div class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
        </div>
        <p class="text-sm font-medium text-muted-text mb-4">{{ __('app.lectionary_select_prompt') }}</p>
        <button type="button" @click="dayOpen = true; $nextTick(() => document.querySelector('.day-grid')?.scrollIntoView({behavior:'smooth'}))"
                class="px-5 py-3 bg-accent text-on-accent text-sm font-semibold rounded-2xl shadow-md shadow-accent/20 active:scale-95 transition">
            Choose a day above
        </button>
    </div>
    @endif

    {{-- ═══════════ BOTTOM SHEETS ═══════════ --}}

    {{-- Backdrop --}}
    <div x-show="sheet !== ''" x-cloak
         x-transition:enter="transition duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         @click="closeSheet()"
         class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm"></div>

    {{-- ── DELETE CONFIRM ── --}}
    <div x-show="sheet === 'delete'" x-cloak
         x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-50 bg-card rounded-t-3xl shadow-2xl">
        <div class="flex justify-center pt-3 pb-1"><div class="w-10 h-1 rounded-full bg-border"></div></div>
        <div class="px-5 py-5" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 1.25rem))">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-primary">{{ __('app.lectionary_delete_confirm') }}</h3>
                    <p class="text-sm text-muted-text mt-0.5">This will remove all readings for this day.</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="button" @click="closeSheet()"
                        class="flex-1 py-4 rounded-2xl border border-border text-primary font-semibold text-base active:scale-[0.98] transition">
                    {{ __('app.cancel') }}
                </button>
                <button type="button" @click="submitDelete()"
                        class="flex-1 py-4 rounded-2xl bg-red-500 text-white font-bold text-base active:scale-[0.98] transition hover:bg-red-600">
                    {{ __('app.delete') }}
                </button>
            </div>
        </div>
    </div>

    {{-- ── PREVIEW SHEET ── --}}
    @if($entry)
    <div x-show="sheet === 'preview'" x-cloak
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-50 bg-card rounded-t-3xl shadow-2xl max-h-[92vh] overflow-y-auto">
        <div class="sticky top-0 bg-card flex items-center justify-between px-5 pt-3 pb-3 border-b border-border">
            <div class="flex items-center gap-2">
                <div class="w-10 h-1 rounded-full bg-border absolute left-1/2 -translate-x-1/2 top-3"></div>
            </div>
            <h3 class="text-base font-bold text-primary mx-auto pt-3">{{ $monthAm ?? '' }} {{ $selectedDay }} — Preview</h3>
            <button type="button" @click="closeSheet()"
                    class="w-9 h-9 rounded-xl flex items-center justify-center bg-muted text-muted-text active:scale-90 transition mt-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="px-5 py-5" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem))">
            <x-lectionary-preview :entry="$entry" :monthNames="$monthNames" />
        </div>
    </div>
    @endif

</div>
@endsection

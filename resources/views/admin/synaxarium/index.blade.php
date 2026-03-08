@extends('layouts.admin')

@section('title', __('app.synaxarium_admin_title'))

@section('content')

@php
$monthNames = [
    1 => 'Meskerem', 2 => 'Tikimt', 3 => 'Hidar',
    4 => 'Tahsas', 5 => 'Tir', 6 => 'Yekatit',
    7 => 'Megabit', 8 => 'Miyazia', 9 => 'Ginbot',
    10 => 'Sene', 11 => 'Hamle', 12 => 'Nehase',
    13 => 'Pagumen',
];
$monthNamesAm = [
    1 => 'መስከረም', 2 => 'ጥቅምት', 3 => 'ኅዳር',
    4 => 'ታኅሣሥ', 5 => 'ጥር', 6 => 'የካቲት',
    7 => 'መጋቢት', 8 => 'ሚያዝያ', 9 => 'ግንቦት',
    10 => 'ሰኔ', 11 => 'ሐምሌ', 12 => 'ነሐሴ',
    13 => 'ጳጉሜን',
];
$monthNamesFull = [
    1 => 'Meskerem / መስከረም', 2 => 'Tikimt / ጥቅምት', 3 => 'Hidar / ኅዳር',
    4 => 'Tahsas / ታኅሣሥ', 5 => 'Tir / ጥር', 6 => 'Yekatit / የካቲት',
    7 => 'Megabit / መጋቢት', 8 => 'Miyazia / ሚያዝያ', 9 => 'Ginbot / ግንቦት',
    10 => 'Sene / ሰኔ', 11 => 'Hamle / ሐምሌ', 12 => 'Nehase / ነሐሴ',
    13 => 'Pagumen / ጳጉሜን',
];

// Determine which sheet to auto-open based on URL params or old form data
$autoSheet = '';
if ($editingMonthly) $autoSheet = 'edit-monthly';
elseif ($editingAnnual) $autoSheet = 'edit-annual';
elseif (old('_form') === 'add_monthly') $autoSheet = 'add-monthly';
elseif (old('_form') === 'add_annual') $autoSheet = 'add-annual';
@endphp

<style>[x-cloak]{display:none!important}</style>

<div class="max-w-2xl lg:max-w-full pb-28 lg:pb-6"
     x-data="{
         tab: '{{ request()->query('edit_annual') ? 'annual' : 'monthly' }}',
         selectedDay: {{ $editingMonthly ? $editingMonthly->day : (int)request()->query('day', 1) }},
         sheet: '{{ $autoSheet }}',
         dayPickerOpen: true,
         pendingDeleteId: null,
         imgAdd: null,
         imgEdit: null,
         previewImg(event, key) {
             const file = event.target.files[0];
             if (!file) return;
             const r = new FileReader();
             r.onload = e => { this[key] = e.target.result; };
             r.readAsDataURL(file);
         },
         openSheet(name) { this.sheet = name; },
         closeSheet() { this.sheet = ''; this.imgAdd = null; },
         confirmDelete(id) { this.pendingDeleteId = id; this.sheet = 'delete'; },
         submitDelete() { if (this.pendingDeleteId) document.getElementById(this.pendingDeleteId)?.submit(); }
     }"
     @keydown.escape.window="closeSheet()">

    {{-- ── Page Header ── --}}
    <div class="flex items-center gap-3 mb-5">
        <div class="w-11 h-11 rounded-2xl bg-accent/10 flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-primary leading-tight">{{ __('app.synaxarium_admin_title') }}</h1>
            <p class="text-xs text-muted-text">Monthly & annual celebrations</p>
        </div>
    </div>

    {{-- ── Success Banner ── --}}
    @if(session('success'))
    <div class="mb-4 flex items-center gap-3 px-4 py-3.5 rounded-2xl bg-green-50 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm text-green-700 dark:text-green-400 font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Tab Switcher ── --}}
    <div class="flex bg-muted rounded-2xl lg:rounded-xl p-1 gap-1 mb-5 lg:mb-3 lg:max-w-sm">
        <button type="button" @click="tab = 'monthly'"
                class="flex-1 py-3.5 lg:py-2.5 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="tab === 'monthly' ? 'bg-card text-primary shadow-sm' : 'text-muted-text'">
            {{ __('app.synaxarium_monthly_tab') }}
            <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                  :class="tab === 'monthly' ? 'bg-accent/10 text-accent' : 'opacity-50'">{{ $monthlyCelebrations->count() }}</span>
        </button>
        <button type="button" @click="tab = 'annual'"
                class="flex-1 py-3.5 lg:py-2.5 rounded-xl text-sm font-semibold transition-all duration-200"
                :class="tab === 'annual' ? 'bg-card text-primary shadow-sm' : 'text-muted-text'">
            {{ __('app.synaxarium_annual_tab') }}
            <span class="ml-1.5 text-xs px-1.5 py-0.5 rounded-full"
                  :class="tab === 'annual' ? 'bg-accent/10 text-accent' : 'opacity-50'">{{ $annualCelebrations->count() }}</span>
        </button>
    </div>

    {{-- ════════════════════════ MONTHLY TAB ════════════════════════ --}}
    <div x-show="tab === 'monthly'">

        {{-- Day Grid Accordion --}}
        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm mb-4 lg:mb-3 overflow-hidden">
            {{-- Accordion header --}}
            <button type="button" @click="dayPickerOpen = !dayPickerOpen"
                    class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/40 transition select-none">
                <div class="flex items-center gap-3">
                    <svg class="w-4 h-4 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="text-sm font-semibold text-primary">{{ __('app.synaxarium_day_number') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-accent px-2.5 py-1 rounded-xl bg-accent/10"
                          x-text="'Day ' + selectedDay"></span>
                    <svg class="w-4 h-4 text-muted-text transition-transform duration-200"
                         :class="dayPickerOpen ? 'rotate-180' : ''"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>
            </button>
            {{-- Accordion body --}}
            <div x-show="dayPickerOpen"
                 x-transition:enter="transition duration-200 ease-out"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition duration-150 ease-in"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="px-4 pb-4 border-t border-border">
                <div class="grid grid-cols-5 sm:grid-cols-6 lg:grid-cols-10 gap-2 lg:gap-1.5 pt-3 lg:pt-2">
                    @for($d = 1; $d <= 30; $d++)
                    <button type="button" @click="selectedDay = {{ $d }}; dayPickerOpen = false"
                            class="relative h-14 lg:h-10 rounded-2xl lg:rounded-xl text-base lg:text-sm font-bold transition-all duration-150 active:scale-95 select-none"
                            :class="selectedDay === {{ $d }}
                                ? 'bg-accent text-on-accent shadow-lg shadow-accent/30 scale-105'
                                : 'bg-surface text-primary border border-border'">
                        {{ $d }}
                        @if(isset($monthlyByDay[$d]) && $monthlyByDay[$d]->count() > 0)
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full"
                                  :class="selectedDay === {{ $d }} ? 'bg-white/80' : 'bg-accent'"></span>
                        @endif
                    </button>
                    @endfor
                </div>
            </div>
        </div>

        {{-- Saints for selected day --}}
        <div class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden mb-4 lg:mb-3">
            {{-- Day header --}}
            <div class="px-5 py-4 lg:px-4 lg:py-3 bg-gradient-to-r from-accent/10 to-transparent border-b border-border flex items-center gap-2">
                <svg class="w-5 h-5 text-accent shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <h2 class="font-bold text-primary" x-text="'{{ __('app.synaxarium_day_number_short', ['day' => '']) }}' + selectedDay"></h2>
            </div>

            {{-- Day panels --}}
            @for($d = 1; $d <= 30; $d++)
            <div x-show="selectedDay === {{ $d }}" x-cloak>
                @php $saints = $monthlyByDay[$d] ?? collect(); @endphp

                @forelse($saints as $item)
                <div class="flex items-center gap-3 px-4 py-3.5 lg:px-3 lg:py-2.5 {{ !$loop->last ? 'border-b border-border/50' : '' }} hover:bg-muted/30 transition">
                    {{-- Thumbnail --}}
                    @if($item->image_path)
                        <img src="{{ $item->imageUrl() }}" alt="" class="w-12 h-12 rounded-xl object-cover shrink-0">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                    @endif
                    {{-- Name --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-semibold text-primary text-sm leading-snug">{{ $item->celebration_en }}</span>
                            @if($item->is_main)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('app.synaxarium_main_badge') }}</span>
                            @endif
                        </div>
                        @if($item->celebration_am)
                            <p class="text-sm text-muted-text leading-snug mt-0.5">{{ $item->celebration_am }}</p>
                        @endif
                    </div>
                    {{-- Actions — always visible, 44px touch targets --}}
                    <div class="flex items-center gap-1.5 shrink-0" x-data="{ showConvert: false }">
                        <a href="/admin/synaxarium?edit_monthly={{ $item->id }}&day={{ $item->day }}"
                           class="w-10 h-10 rounded-xl flex items-center justify-center text-accent bg-accent/10 active:scale-90 transition"
                           title="{{ __('app.edit') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                        <div class="relative">
                            <button type="button" @click="showConvert = !showConvert"
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-amber-600 bg-amber-50 dark:bg-amber-900/20 active:scale-90 transition"
                                    title="{{ __('app.synaxarium_convert_to_annual') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                            </button>
                            <div x-show="showConvert" x-cloak @click.away="showConvert = false"
                                 x-transition
                                 class="absolute right-0 top-12 z-30 bg-card border border-border rounded-xl shadow-xl p-3 w-56">
                                <p class="text-xs font-semibold text-muted-text mb-2">{{ __('app.synaxarium_convert_select_month') }}</p>
                                <form method="POST" action="{{ route('admin.synaxarium.monthly.convert', $item) }}">
                                    @csrf
                                    <select name="month" class="w-full px-3 py-2 rounded-lg border border-border bg-surface text-primary text-sm mb-2">
                                        @foreach($monthNamesFull as $m => $mFull)
                                            <option value="{{ $m }}">{{ $mFull }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="w-full py-2 bg-amber-500 text-white text-xs font-bold rounded-lg hover:bg-amber-600 transition">
                                        {{ __('app.synaxarium_convert_to_annual') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        <button type="button" @click="confirmDelete('del-m-{{ $item->id }}')"
                                class="w-10 h-10 rounded-xl flex items-center justify-center text-red-500 bg-red-50 dark:bg-red-900/20 active:scale-90 transition"
                                title="{{ __('app.delete') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        <form id="del-m-{{ $item->id }}" method="POST" action="/admin/synaxarium/monthly/{{ $item->id }}" class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </div>
                </div>
                @empty
                <div class="flex flex-col items-center justify-center py-12 text-center px-6">
                    <div class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <p class="text-sm text-muted-text mb-4">{{ __('app.synaxarium_no_saints_for_day') }}</p>
                    <button type="button" @click="openSheet('add-monthly')"
                            class="px-5 py-3 bg-accent text-on-accent text-sm font-semibold rounded-2xl active:scale-95 transition shadow-md shadow-accent/20">
                        + {{ __('app.synaxarium_add_saint') }}
                    </button>
                </div>
                @endforelse
            </div>
            @endfor
        </div>
    </div>{{-- /monthly --}}

    {{-- ════════════════════════ ANNUAL TAB ════════════════════════ --}}
    <div x-show="tab === 'annual'" x-cloak>

        {{-- Add button --}}
        <button type="button" @click="openSheet('add-annual')"
                class="w-full lg:w-auto flex items-center justify-center gap-2 py-4 lg:py-2.5 lg:px-6 mb-4 lg:mb-3 bg-accent text-on-accent text-sm font-bold rounded-2xl lg:rounded-xl shadow-lg shadow-accent/20 active:scale-[0.98] transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            {{ __('app.synaxarium_add_annual') }}
        </button>

        {{-- Annual list — grouped by month, each month is an accordion --}}
        @php $byMonth = $annualCelebrations->groupBy('month'); @endphp

        @if($byMonth->isEmpty())
        <div class="bg-card rounded-2xl border border-border p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mx-auto mb-3">
                <svg class="w-8 h-8 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p class="text-sm text-muted-text">{{ __('app.synaxarium_no_annual') }}</p>
        </div>
        @else
        <div class="space-y-2">
            @foreach($monthNamesFull as $m => $mFull)
            @if(!isset($byMonth[$m])) @continue @endif
            @php
                $monthSaints  = $byMonth[$m];
                $monthByDay   = $monthSaints->groupBy('day');
                [$mEn, $mAm]  = explode(' / ', $mFull);
                $totalInMonth = $monthSaints->count();
            @endphp

            <div x-data="{ open: false }" class="bg-card rounded-2xl lg:rounded-xl border border-border shadow-sm overflow-hidden">

                {{-- Month accordion header --}}
                <button type="button" @click="open = !open"
                        class="w-full flex items-center justify-between px-4 py-3.5 lg:px-3 lg:py-2.5 active:bg-muted/30 transition select-none">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-amber-700 dark:text-amber-400">{{ $m }}</span>
                        </div>
                        <div class="text-left">
                            <p class="text-sm font-bold text-primary leading-tight">{{ $mAm }}</p>
                            <p class="text-xs text-muted-text leading-tight">{{ $mEn }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ $totalInMonth }} {{ $totalInMonth === 1 ? 'feast' : 'feasts' }}
                        </span>
                        <svg class="w-4 h-4 text-muted-text transition-transform duration-200 shrink-0"
                             :class="open ? 'rotate-180' : ''"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </button>

                {{-- Month accordion body --}}
                <div x-show="open"
                     x-transition:enter="transition duration-200 ease-out"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition duration-150 ease-in"
                     x-transition:leave-start="opacity-100 translate-y-0"
                     x-transition:leave-end="opacity-0 -translate-y-1"
                     class="border-t border-border divide-y divide-border/40">

                    @foreach($monthByDay as $day => $daySaints)
                    {{-- Day sub-header --}}
                    <div class="px-4 py-2 bg-muted/40 flex items-center gap-2">
                        <span class="text-xs font-bold text-accent bg-accent/10 px-2 py-0.5 rounded-lg">Day {{ $day }}</span>
                        <span class="text-xs text-muted-text">· {{ $daySaints->count() }} {{ $daySaints->count() === 1 ? 'saint' : 'saints' }}</span>
                    </div>

                    @foreach($daySaints as $item)
                    <div class="flex items-center gap-3 px-4 py-3.5 lg:px-3 lg:py-2.5 hover:bg-muted/20 transition">
                        @if($item->image_path)
                            <img src="{{ $item->imageUrl() }}" alt="" class="w-11 h-11 rounded-xl object-cover shrink-0">
                        @else
                            <div class="w-11 h-11 rounded-xl bg-accent/10 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-semibold text-primary text-sm leading-snug">{{ $item->celebration_en }}</span>
                                @if($item->is_main)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('app.synaxarium_main_badge') }}</span>
                                @endif
                            </div>
                            @if($item->celebration_am)
                                <p class="text-sm text-muted-text mt-0.5 leading-snug">{{ $item->celebration_am }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="/admin/synaxarium?edit_annual={{ $item->id }}"
                               class="w-10 h-10 rounded-xl flex items-center justify-center text-accent bg-accent/10 active:scale-90 transition"
                               title="{{ __('app.edit') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('admin.synaxarium.annual.convert', $item) }}" class="inline"
                                  onsubmit="return confirm('{{ __('app.synaxarium_convert_to_monthly') }}?')">
                                @csrf
                                <button type="submit"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center text-amber-600 bg-amber-50 dark:bg-amber-900/20 active:scale-90 transition"
                                        title="{{ __('app.synaxarium_convert_to_monthly') }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </button>
                            </form>
                            <button type="button" @click="confirmDelete('del-a-{{ $item->id }}')"
                                    class="w-10 h-10 rounded-xl flex items-center justify-center text-red-500 bg-red-50 dark:bg-red-900/20 active:scale-90 transition"
                                    title="{{ __('app.delete') }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <form id="del-a-{{ $item->id }}" method="POST" action="/admin/synaxarium/annual/{{ $item->id }}" class="hidden">
                                @csrf @method('DELETE')
                            </form>
                        </div>
                    </div>
                    @endforeach
                    @endforeach

                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>{{-- /annual --}}

    {{-- ── FAB (Monthly Add) ── --}}
    <div x-show="tab === 'monthly'" x-cloak
         class="fixed bottom-6 right-4 sm:right-6 z-30">
        <button type="button" @click="openSheet('add-monthly')"
                class="w-14 h-14 bg-accent text-on-accent rounded-2xl shadow-2xl shadow-accent/40 flex items-center justify-center active:scale-90 transition-all duration-150">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        </button>
    </div>

    {{-- ═══════════════════════ BOTTOM SHEETS ═══════════════════════ --}}

    {{-- Backdrop --}}
    <div x-show="sheet !== ''" x-cloak
         x-transition:enter="transition duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeSheet()"
         class="fixed inset-0 bg-black/50 z-40 backdrop-blur-sm"></div>

    {{-- ── ADD MONTHLY SHEET ── --}}
    <div x-show="sheet === 'add-monthly'" x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 lg:left-1/2 lg:-translate-x-1/2 lg:max-w-3xl lg:rounded-t-2xl z-50 bg-card rounded-t-3xl shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-card pt-3 pb-2 flex justify-center lg:hidden">
            <div class="w-10 h-1 rounded-full bg-border"></div>
        </div>

        <div class="px-5 pt-2 lg:px-6 lg:pt-5" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem))">
            <div class="flex items-center justify-between mb-5 lg:mb-4">
                <div>
                    <h3 class="text-lg font-bold text-primary">{{ __('app.synaxarium_add_saint') }}</h3>
                    <p class="text-xs text-muted-text" x-text="'Day ' + selectedDay"></p>
                </div>
                <button type="button" @click="closeSheet()"
                        class="w-10 h-10 rounded-xl flex items-center justify-center bg-muted text-muted-text active:scale-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="/admin/synaxarium/monthly" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_form" value="add_monthly">
                <input type="hidden" name="day" :value="selectedDay">

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400 normal-case font-normal">*</span></label>
                        <input type="text" name="celebration_en" value="{{ old('celebration_en') }}" required autocomplete="off"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition"
                               placeholder="e.g. Angel Mikael (Michael)">
                        @error('celebration_en') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (አማርኛ)</label>
                        <input type="text" name="celebration_am" value="{{ old('celebration_am') }}" autocomplete="off"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition"
                               placeholder="ለምሳሌ ቅዱስ ሚካኤል">
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (English)</label>
                        <textarea name="description_en" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition"
                                  placeholder="Optional description...">{{ old('description_en') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (አማርኛ)</label>
                        <textarea name="description_am" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition"
                                  placeholder="የበዓሉ መግለጫ...">{{ old('description_am') }}</textarea>
                    </div>
                </div>

                {{-- Image Upload --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_image') }}</label>
                    <label class="relative flex flex-col items-center justify-center w-full h-36 rounded-2xl border-2 border-dashed border-border bg-surface cursor-pointer overflow-hidden transition hover:border-accent/50 active:scale-[0.99]">
                        <div x-show="!imgAdd" class="flex flex-col items-center gap-2 pointer-events-none">
                            <svg class="w-9 h-9 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-semibold text-muted-text">Tap to upload image</span>
                            <span class="text-xs text-muted-text/70">JPG, PNG · max 2 MB</span>
                        </div>
                        <img x-show="imgAdd" :src="imgAdd" x-cloak class="absolute inset-0 w-full h-full object-cover">
                        <div x-show="imgAdd" x-cloak class="absolute inset-0 bg-black/30 flex items-center justify-center pointer-events-none">
                            <span class="text-white text-sm font-semibold">Tap to change</span>
                        </div>
                        <input type="file" name="image" accept="image/*" class="sr-only" @change="previewImg($event, 'imgAdd')">
                    </label>
                </div>

                {{-- Options --}}
                <div class="flex items-center gap-4 mb-6 px-1">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_main" value="1" class="sr-only peer">
                            <div class="w-11 h-6 rounded-full bg-muted peer-checked:bg-accent transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-primary">{{ __('app.synaxarium_is_main') }}</span>
                    </label>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-text">{{ __('app.synaxarium_sort_order') }}</label>
                        <input type="number" name="sort_order" min="0" max="255" value="0"
                               class="w-16 px-2 py-2.5 rounded-xl border border-border bg-surface text-primary text-base text-center focus:outline-none focus:ring-2 focus:ring-accent/50">
                    </div>
                </div>

                <button type="submit"
                        class="w-full lg:w-auto lg:px-8 py-4 lg:py-2.5 bg-accent text-on-accent text-base lg:text-sm font-bold rounded-2xl lg:rounded-xl shadow-lg shadow-accent/20 hover:opacity-90 active:scale-[0.98] transition">
                    + {{ __('app.synaxarium_add_saint') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ── EDIT MONTHLY SHEET ── --}}
    @if($editingMonthly)
    <div x-show="sheet === 'edit-monthly'"
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 lg:left-1/2 lg:-translate-x-1/2 lg:max-w-3xl lg:rounded-t-2xl z-50 bg-card rounded-t-3xl shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-card pt-3 pb-2 flex justify-center lg:hidden">
            <div class="w-10 h-1 rounded-full bg-border"></div>
        </div>

        <div class="px-5 pt-2 lg:px-6 lg:pt-5" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem))">
            <div class="flex items-center justify-between mb-5 lg:mb-4">
                <div>
                    <h3 class="text-lg font-bold text-primary">{{ __('app.synaxarium_edit_monthly') }}</h3>
                    <p class="text-xs text-muted-text">Day {{ $editingMonthly->day }}</p>
                </div>
                <a href="/admin/synaxarium?day={{ $editingMonthly->day }}"
                   class="w-10 h-10 rounded-xl flex items-center justify-center bg-muted text-muted-text active:scale-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </div>

            <form method="POST" action="/admin/synaxarium/monthly/{{ $editingMonthly->id }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400 normal-case font-normal">*</span></label>
                        <input type="text" name="celebration_en" value="{{ old('celebration_en', $editingMonthly->celebration_en) }}" required
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition">
                        @error('celebration_en') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (አማርኛ)</label>
                        <input type="text" name="celebration_am" value="{{ old('celebration_am', $editingMonthly->celebration_am) }}"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition">
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (English)</label>
                        <textarea name="description_en" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition">{{ old('description_en', $editingMonthly->description_en) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (አማርኛ)</label>
                        <textarea name="description_am" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition">{{ old('description_am', $editingMonthly->description_am) }}</textarea>
                    </div>
                </div>

                {{-- Image --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_image') }}</label>
                    @if($editingMonthly->image_path)
                    <div class="flex items-center gap-3 p-3 rounded-2xl border border-border bg-surface mb-3">
                        <img src="{{ $editingMonthly->imageUrl() }}" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-primary mb-1.5">Current image</p>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-red-500">
                                <input type="checkbox" name="remove_image" value="1" class="rounded text-red-500 w-4 h-4">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    </div>
                    @endif
                    <label class="relative flex items-center justify-center w-full h-28 rounded-2xl border-2 border-dashed border-border bg-surface cursor-pointer overflow-hidden hover:border-accent/50 transition">
                        <div x-show="!imgEdit" class="flex items-center gap-2 text-muted-text pointer-events-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">{{ $editingMonthly->image_path ? 'Upload new image' : 'Tap to upload' }}</span>
                        </div>
                        <img x-show="imgEdit" :src="imgEdit" x-cloak class="absolute inset-0 w-full h-full object-cover">
                        <input type="file" name="image" accept="image/*" class="sr-only" @change="previewImg($event, 'imgEdit')">
                    </label>
                </div>

                <div class="flex items-center gap-4 mb-6 px-1">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_main" value="1" {{ $editingMonthly->is_main ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 rounded-full bg-muted peer-checked:bg-accent transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-primary">{{ __('app.synaxarium_is_main') }}</span>
                    </label>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-text">{{ __('app.synaxarium_sort_order') }}</label>
                        <input type="number" name="sort_order" min="0" max="255" value="{{ $editingMonthly->sort_order }}"
                               class="w-16 px-2 py-2.5 rounded-xl border border-border bg-surface text-primary text-base text-center focus:outline-none focus:ring-2 focus:ring-accent/50">
                    </div>
                </div>

                <button type="submit"
                        class="w-full lg:w-auto lg:px-8 py-4 lg:py-2.5 bg-accent text-on-accent text-base lg:text-sm font-bold rounded-2xl lg:rounded-xl shadow-lg shadow-accent/20 hover:opacity-90 active:scale-[0.98] transition">
                    {{ __('app.save_changes') }}
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── ADD ANNUAL SHEET ── --}}
    <div x-show="sheet === 'add-annual'" x-cloak
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 lg:left-1/2 lg:-translate-x-1/2 lg:max-w-3xl lg:rounded-t-2xl z-50 bg-card rounded-t-3xl shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-card pt-3 pb-2 flex justify-center lg:hidden">
            <div class="w-10 h-1 rounded-full bg-border"></div>
        </div>

        <div class="px-5 pt-2 lg:px-6 lg:pt-5" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem))">
            <div class="flex items-center justify-between mb-5 lg:mb-4">
                <h3 class="text-lg font-bold text-primary">{{ __('app.synaxarium_add_annual') }}</h3>
                <button type="button" @click="closeSheet()"
                        class="w-10 h-10 rounded-xl flex items-center justify-center bg-muted text-muted-text active:scale-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="/admin/synaxarium/annual" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="_form" value="add_annual">

                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_month_number') }}</label>
                        <select name="month" required
                                class="w-full px-4 py-3.5 rounded-2xl border border-border bg-surface text-primary text-base focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                            @for($m = 1; $m <= 13; $m++)
                                <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>{{ $m }} — {{ $monthNamesFull[$m] }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">Day</label>
                        <input type="number" name="day" min="1" max="30" value="{{ old('day') }}" required
                               class="w-full px-4 py-3.5 rounded-2xl border border-border bg-surface text-primary text-base focus:outline-none focus:ring-2 focus:ring-accent/50 text-center">
                        @error('day') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400 normal-case font-normal">*</span></label>
                        <input type="text" name="celebration_en" value="{{ old('celebration_en') }}" required autocomplete="off"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition"
                               placeholder="e.g. Ethiopian Christmas (Genna)">
                        @error('celebration_en') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (አማርኛ)</label>
                        <input type="text" name="celebration_am" value="{{ old('celebration_am') }}" autocomplete="off"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition"
                               placeholder="ለምሳሌ ገና">
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (English)</label>
                        <textarea name="description_en" rows="2"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition"
                                  placeholder="Optional description...">{{ old('description_en') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (አማርኛ)</label>
                        <textarea name="description_am" rows="2"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition"
                                  placeholder="የበዓሉ መግለጫ...">{{ old('description_am') }}</textarea>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_image') }}</label>
                    <label class="relative flex flex-col items-center justify-center w-full h-28 rounded-2xl border-2 border-dashed border-border bg-surface cursor-pointer overflow-hidden hover:border-accent/50 transition">
                        <div x-show="!imgAdd" class="flex items-center gap-2 text-muted-text pointer-events-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">Tap to upload image</span>
                        </div>
                        <img x-show="imgAdd" :src="imgAdd" x-cloak class="absolute inset-0 w-full h-full object-cover">
                        <input type="file" name="image" accept="image/*" class="sr-only" @change="previewImg($event, 'imgAdd')">
                    </label>
                </div>

                <div class="flex items-center gap-4 mb-6 px-1">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_main" value="1" class="sr-only peer">
                            <div class="w-11 h-6 rounded-full bg-muted peer-checked:bg-accent transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-primary">{{ __('app.synaxarium_is_main') }}</span>
                    </label>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-text">{{ __('app.synaxarium_sort_order') }}</label>
                        <input type="number" name="sort_order" min="0" max="255" value="0"
                               class="w-16 px-2 py-2.5 rounded-xl border border-border bg-surface text-primary text-base text-center focus:outline-none focus:ring-2 focus:ring-accent/50">
                    </div>
                </div>

                <button type="submit"
                        class="w-full lg:w-auto lg:px-8 py-4 lg:py-2.5 bg-accent text-on-accent text-base lg:text-sm font-bold rounded-2xl lg:rounded-xl shadow-lg shadow-accent/20 hover:opacity-90 active:scale-[0.98] transition">
                    {{ __('app.create') }}
                </button>
            </form>
        </div>
    </div>

    {{-- ── EDIT ANNUAL SHEET ── --}}
    @if($editingAnnual)
    <div x-show="sheet === 'edit-annual'"
         x-transition:enter="transition duration-300 ease-out"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-200 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 lg:left-1/2 lg:-translate-x-1/2 lg:max-w-3xl lg:rounded-t-2xl z-50 bg-card rounded-t-3xl shadow-2xl max-h-[90vh] overflow-y-auto">

        <div class="sticky top-0 bg-card pt-3 pb-2 flex justify-center lg:hidden">
            <div class="w-10 h-1 rounded-full bg-border"></div>
        </div>

        <div class="px-5 pt-2 lg:px-6 lg:pt-5" style="padding-bottom: max(1.5rem, env(safe-area-inset-bottom, 1.5rem))">
            <div class="flex items-center justify-between mb-5 lg:mb-4">
                <div>
                    <h3 class="text-lg font-bold text-primary">{{ __('app.synaxarium_edit_annual') }}</h3>
                    <p class="text-xs text-muted-text">{{ $monthNames[$editingAnnual->month] ?? '' }} · Day {{ $editingAnnual->day }}</p>
                </div>
                <a href="/admin/synaxarium"
                   class="w-10 h-10 rounded-xl flex items-center justify-center bg-muted text-muted-text active:scale-90 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            </div>

            <form method="POST" action="/admin/synaxarium/annual/{{ $editingAnnual->id }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_month_number') }}</label>
                        <select name="month" required
                                class="w-full px-4 py-3.5 rounded-2xl border border-border bg-surface text-primary text-base focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                            @for($m = 1; $m <= 13; $m++)
                                <option value="{{ $m }}" {{ (int) old('month', $editingAnnual->month) === $m ? 'selected' : '' }}>{{ $m }} - {{ $monthNamesFull[$m] }}</option>
                            @endfor
                        </select>
                        @error('month') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">Day</label>
                        <input type="number" name="day" min="1" max="30" value="{{ old('day', $editingAnnual->day) }}" required
                               class="w-full px-4 py-3.5 rounded-2xl border border-border bg-surface text-primary text-base text-center focus:outline-none focus:ring-2 focus:ring-accent/50">
                        @error('day') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400 normal-case font-normal">*</span></label>
                        <input type="text" name="celebration_en" value="{{ old('celebration_en', $editingAnnual->celebration_en) }}" required
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition">
                        @error('celebration_en') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_celebration') }} (አማርኛ)</label>
                        <input type="text" name="celebration_am" value="{{ old('celebration_am', $editingAnnual->celebration_am) }}"
                               class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent transition">
                    </div>
                </div>

                <div class="lg:grid lg:grid-cols-2 lg:gap-3 space-y-4 lg:space-y-0 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (English)</label>
                        <textarea name="description_en" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition">{{ old('description_en', $editingAnnual->description_en) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2 lg:mb-1">{{ __('app.synaxarium_description') }} (አማርኛ)</label>
                        <textarea name="description_am" rows="3"
                                  class="w-full px-4 py-3.5 lg:px-3 lg:py-2.5 rounded-2xl lg:rounded-xl border border-border bg-surface text-primary text-base lg:text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent resize-none transition">{{ old('description_am', $editingAnnual->description_am) }}</textarea>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-xs font-bold text-muted-text uppercase tracking-wider mb-2">{{ __('app.synaxarium_image') }}</label>
                    @if($editingAnnual->image_path)
                    <div class="flex items-center gap-3 p-3 rounded-2xl border border-border bg-surface mb-3">
                        <img src="{{ $editingAnnual->imageUrl() }}" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-primary mb-1.5">Current image</p>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-sm text-red-500">
                                <input type="checkbox" name="remove_image" value="1" class="rounded text-red-500 w-4 h-4">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    </div>
                    @endif
                    <label class="relative flex items-center justify-center w-full h-28 rounded-2xl border-2 border-dashed border-border bg-surface cursor-pointer overflow-hidden hover:border-accent/50 transition">
                        <div x-show="!imgEdit" class="flex items-center gap-2 text-muted-text pointer-events-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span class="text-sm font-medium">{{ $editingAnnual->image_path ? 'Upload new image' : 'Tap to upload' }}</span>
                        </div>
                        <img x-show="imgEdit" :src="imgEdit" x-cloak class="absolute inset-0 w-full h-full object-cover">
                        <input type="file" name="image" accept="image/*" class="sr-only" @change="previewImg($event, 'imgEdit')">
                    </label>
                </div>

                <div class="flex items-center gap-4 mb-6 px-1">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <div class="relative">
                            <input type="checkbox" name="is_main" value="1" {{ old('is_main', $editingAnnual->is_main) ? 'checked' : '' }} class="sr-only peer">
                            <div class="w-11 h-6 rounded-full bg-muted peer-checked:bg-accent transition-colors duration-200"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200 peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-medium text-primary">{{ __('app.synaxarium_is_main') }}</span>
                    </label>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="text-xs font-medium text-muted-text">{{ __('app.synaxarium_sort_order') }}</label>
                        <input type="number" name="sort_order" min="0" max="255"
                               value="{{ old('sort_order', $editingAnnual->sort_order ?? 0) }}"
                               class="w-16 px-2 py-2.5 rounded-xl border border-border bg-surface text-primary text-base text-center focus:outline-none focus:ring-2 focus:ring-accent/50">
                    </div>
                </div>

                <button type="submit"
                        class="w-full lg:w-auto lg:px-8 py-4 lg:py-2.5 bg-accent text-on-accent text-base lg:text-sm font-bold rounded-2xl lg:rounded-xl shadow-lg shadow-accent/20 hover:opacity-90 active:scale-[0.98] transition">
                    {{ __('app.save_changes') }}
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── DELETE CONFIRM SHEET ── --}}
    <div x-show="sheet === 'delete'" x-cloak
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition duration-150 ease-in"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-0 left-0 right-0 z-50 bg-card rounded-t-3xl shadow-2xl">

        <div class="flex justify-center pt-3 pb-1">
            <div class="w-10 h-1 rounded-full bg-border"></div>
        </div>

        <div class="px-5 py-5" style="padding-bottom: max(1.25rem, env(safe-area-inset-bottom, 1.25rem))">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-12 h-12 rounded-2xl bg-red-50 dark:bg-red-900/20 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-primary">Delete this celebration?</h3>
                    <p class="text-sm text-muted-text mt-0.5">{{ __('app.synaxarium_delete_confirm') }}</p>
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

</div>
@endsection

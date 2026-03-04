@extends('layouts.admin')

@section('title', __('app.synaxarium_admin_title'))

@section('content')
<div class="max-w-3xl" x-data="{
    tab: '{{ request()->query('edit_annual') ? 'annual' : 'monthly' }}',
    selectedDay: {{ $editingMonthly ? $editingMonthly->day : (int)(request()->query('day', 1)) }}
}">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-primary">{{ __('app.synaxarium_admin_title') }}</h1>
    </div>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tab switcher --}}
    <div class="flex bg-muted rounded-xl p-1 gap-1 mb-6">
        <button type="button" @click="tab = 'monthly'"
                class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                :class="tab === 'monthly' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'">
            {{ __('app.synaxarium_monthly_tab') }} ({{ $monthlyCelebrations->count() }})
        </button>
        <button type="button" @click="tab = 'annual'"
                class="flex-1 py-2.5 rounded-lg text-sm font-semibold transition-all duration-200"
                :class="tab === 'annual' ? 'bg-card text-primary shadow-sm' : 'text-muted-text hover:text-secondary'">
            {{ __('app.synaxarium_annual_tab') }} ({{ $annualCelebrations->count() }})
        </button>
    </div>

    {{-- =============================== MONTHLY TAB =============================== --}}
    <div x-show="tab === 'monthly'">

        {{-- Day Picker Grid --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 mb-4">
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wide mb-3">{{ __('app.synaxarium_day_number') }}</p>
            <div class="grid grid-cols-6 gap-2">
                @for($d = 1; $d <= 30; $d++)
                <button type="button" @click="selectedDay = {{ $d }}"
                        class="relative h-11 rounded-xl text-sm font-semibold transition-all duration-150"
                        :class="selectedDay === {{ $d }}
                            ? 'bg-accent text-on-accent shadow-md scale-105'
                            : 'bg-surface text-primary hover:bg-muted border border-border'">
                    {{ $d }}
                    @if(isset($monthlyByDay[$d]) && $monthlyByDay[$d]->count() > 0)
                        <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 text-[10px] font-bold rounded-full flex items-center justify-center"
                              :class="selectedDay === {{ $d }} ? 'bg-white text-accent' : 'bg-accent text-on-accent'">
                            {{ $monthlyByDay[$d]->count() }}
                        </span>
                    @endif
                </button>
                @endfor
            </div>
        </div>

        {{-- Selected Day Panel --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden">
            {{-- Day header --}}
            <div class="px-5 py-3 bg-gradient-to-r from-accent/10 to-transparent border-b border-border">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span x-text="'{{ __('app.synaxarium_day_number_short', ['day' => '']) }}' + selectedDay"></span>
                </h2>
            </div>

            <div class="p-5 space-y-4">
                {{-- Saints list for each day --}}
                @for($d = 1; $d <= 30; $d++)
                <div x-show="selectedDay === {{ $d }}" {{ $d !== ($editingMonthly ? $editingMonthly->day : 1) ? 'x-cloak' : '' }}>
                    @php $saints = $monthlyByDay[$d] ?? collect(); @endphp

                    @if($saints->isNotEmpty())
                    <div class="space-y-2 mb-5">
                        @foreach($saints as $item)
                        <div class="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface group hover:border-accent/30 transition">
                            @if($item->image_path)
                                <img src="{{ $item->imageUrl() }}" alt="" class="w-10 h-10 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-sinksar/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-sinksar" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-medium text-primary text-sm">{{ $item->celebration_en }}</span>
                                    @if($item->is_main)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('app.synaxarium_main_badge') }}</span>
                                    @endif
                                </div>
                                @if($item->celebration_am)
                                    <p class="text-xs text-muted-text">{{ $item->celebration_am }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 shrink-0 opacity-50 group-hover:opacity-100 transition">
                                <a href="/admin/synaxarium?edit_monthly={{ $item->id }}" class="p-1.5 rounded-lg text-accent hover:bg-accent/10 transition" title="{{ __('app.edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="/admin/synaxarium/monthly/{{ $item->id }}" onsubmit="return confirm('{{ __('app.synaxarium_delete_confirm') }}')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="{{ __('app.delete') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4 mb-4">
                        <p class="text-sm text-muted-text">{{ __('app.synaxarium_no_saints_for_day') }}</p>
                    </div>
                    @endif
                </div>
                @endfor

                {{-- Divider + Form --}}
                <div class="border-t border-border pt-4">

                    {{-- Edit form (shown when editing and on the correct day) --}}
                    @if($editingMonthly)
                    <div x-show="selectedDay === {{ $editingMonthly->day }}">
                        <h3 class="text-sm font-semibold text-primary mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ __('app.synaxarium_edit_monthly') }}
                        </h3>
                        <form method="POST" action="/admin/synaxarium/monthly/{{ $editingMonthly->id }}" enctype="multipart/form-data">
                            @csrf @method('PUT')

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400">*</span></label>
                                    <input type="text" name="celebration_en" value="{{ old('celebration_en', $editingMonthly->celebration_en) }}" required
                                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                                    @error('celebration_en') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)</label>
                                    <input type="text" name="celebration_am" value="{{ old('celebration_am', $editingMonthly->celebration_am) }}"
                                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_image') }}</label>
                                @if($editingMonthly->image_path)
                                    <div class="flex items-center gap-2 mb-1.5">
                                        <img src="{{ $editingMonthly->imageUrl() }}" alt="" class="h-14 rounded-lg object-cover">
                                        <label class="text-xs text-red-500 cursor-pointer inline-flex items-center gap-1">
                                            <input type="checkbox" name="remove_image" value="1" class="rounded text-red-500">
                                            {{ __('app.remove') }}
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="image" accept="image/*"
                                       class="w-full text-xs text-muted-text file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent">
                            </div>

                            <div class="flex items-center gap-5 mb-4">
                                <label class="inline-flex items-center gap-2 text-sm text-secondary cursor-pointer">
                                    <input type="checkbox" name="is_main" value="1" {{ $editingMonthly->is_main ? 'checked' : '' }}
                                           class="rounded border-border text-accent focus:ring-accent/50">
                                    {{ __('app.synaxarium_is_main') }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-muted-text">{{ __('app.synaxarium_sort_order') }}:</label>
                                    <input type="number" name="sort_order" min="0" max="255" value="{{ $editingMonthly->sort_order }}"
                                           class="w-16 px-2 py-1.5 rounded-lg border border-border bg-surface text-primary text-sm text-center">
                                </div>
                            </div>

                            <div class="flex items-center gap-2 justify-end">
                                <a href="/admin/synaxarium?day={{ $editingMonthly->day }}" class="px-4 py-2 text-sm text-muted-text hover:text-primary transition">{{ __('app.cancel') }}</a>
                                <button type="submit" class="px-5 py-2 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                                    {{ __('app.save_changes') }}
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                    {{-- Create form --}}
                    <div x-show="{{ $editingMonthly ? 'selectedDay !== ' . $editingMonthly->day : 'true' }}"
                         {{ $editingMonthly ? 'x-cloak' : '' }}>
                        <h3 class="text-sm font-semibold text-primary mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            {{ __('app.synaxarium_add_saint') }}
                        </h3>
                        <form method="POST" action="/admin/synaxarium/monthly" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="day" :value="selectedDay">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400">*</span></label>
                                    <input type="text" name="celebration_en" value="{{ old('celebration_en') }}" required
                                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                           placeholder="e.g. Angel Mikael (Michael)">
                                    @error('celebration_en') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)</label>
                                    <input type="text" name="celebration_am" value="{{ old('celebration_am') }}"
                                           class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                           placeholder="e.g. ቅዱስ ሚካኤል">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_image') }}</label>
                                <input type="file" name="image" accept="image/*"
                                       class="w-full text-xs text-muted-text file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent">
                            </div>

                            <div class="flex items-center gap-5 mb-4">
                                <label class="inline-flex items-center gap-2 text-sm text-secondary cursor-pointer">
                                    <input type="checkbox" name="is_main" value="1"
                                           class="rounded border-border text-accent focus:ring-accent/50">
                                    {{ __('app.synaxarium_is_main') }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs text-muted-text">{{ __('app.synaxarium_sort_order') }}:</label>
                                    <input type="number" name="sort_order" min="0" max="255" value="0"
                                           class="w-16 px-2 py-1.5 rounded-lg border border-border bg-surface text-primary text-sm text-center">
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-5 py-2 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                                    {{ __('app.synaxarium_add_saint') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =============================== ANNUAL TAB =============================== --}}
    <div x-show="tab === 'annual'" x-cloak>

        @php
            $monthNames = [
                1 => 'Meskerem / መስከረም', 2 => 'Tikimt / ጥቅምት', 3 => 'Hidar / ኅዳር',
                4 => 'Tahsas / ታኅሣሥ', 5 => 'Tir / ጥር', 6 => 'Yekatit / የካቲት',
                7 => 'Megabit / መጋቢት', 8 => 'Miyazia / ሚያዝያ', 9 => 'Ginbot / ግንቦት',
                10 => 'Sene / ሰኔ', 11 => 'Hamle / ሐምሌ', 12 => 'Nehase / ነሐሴ',
                13 => 'Pagumen / ጳጉሜን',
            ];
        @endphp

        {{-- Annual Create/Edit Form --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 mb-6">
            <h2 class="text-sm font-semibold text-primary mb-4 flex items-center gap-2">
                @if($editingAnnual)
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('app.synaxarium_edit_annual') }}
                @else
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    {{ __('app.synaxarium_add_annual') }}
                @endif
            </h2>

            <form method="POST"
                  action="{{ $editingAnnual ? '/admin/synaxarium/annual/'.$editingAnnual->id : '/admin/synaxarium/annual' }}"
                  enctype="multipart/form-data">
                @csrf
                @if($editingAnnual) @method('PUT') @endif

                {{-- Month + Day (only on create) --}}
                @unless($editingAnnual)
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_month_number') }}</label>
                        <select name="month" required
                                class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                            @for($m = 1; $m <= 13; $m++)
                                <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>{{ $m }} - {{ $monthNames[$m] }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_day_number') }}</label>
                        <input type="number" name="day" min="1" max="30" value="{{ old('day') }}" required
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                        @error('day') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endunless

                {{-- Name fields side by side --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (English) <span class="text-red-400">*</span></label>
                        <input type="text" name="celebration_en" value="{{ old('celebration_en', $editingAnnual?->celebration_en) }}" required
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. Ethiopian Christmas (Genna)">
                        @error('celebration_en') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)</label>
                        <input type="text" name="celebration_am" value="{{ old('celebration_am', $editingAnnual?->celebration_am) }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. ገና">
                    </div>
                </div>

                {{-- Description fields side by side --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_description') }} (English)</label>
                        <textarea name="description_en" rows="2"
                                  class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                  placeholder="Optional description...">{{ old('description_en', $editingAnnual?->description_en) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_description') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)</label>
                        <textarea name="description_am" rows="2"
                                  class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                                  placeholder="የበዓሉ መግለጫ...">{{ old('description_am', $editingAnnual?->description_am) }}</textarea>
                    </div>
                </div>

                {{-- Image upload --}}
                <div class="mb-3">
                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_image') }}</label>
                    @if($editingAnnual?->image_path)
                        <div class="flex items-center gap-2 mb-1.5">
                            <img src="{{ $editingAnnual->imageUrl() }}" alt="" class="h-14 rounded-lg object-cover">
                            <label class="text-xs text-red-500 cursor-pointer inline-flex items-center gap-1">
                                <input type="checkbox" name="remove_image" value="1" class="rounded text-red-500">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-xs text-muted-text file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-accent/10 file:text-accent">
                </div>

                {{-- Is Main + Sort Order --}}
                <div class="flex items-center gap-5 mb-4">
                    <label class="inline-flex items-center gap-2 text-sm text-secondary cursor-pointer">
                        <input type="checkbox" name="is_main" value="1"
                               {{ old('is_main', $editingAnnual?->is_main) ? 'checked' : '' }}
                               class="rounded border-border text-accent focus:ring-accent/50">
                        {{ __('app.synaxarium_is_main') }}
                    </label>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-muted-text">{{ __('app.synaxarium_sort_order') }}:</label>
                        <input type="number" name="sort_order" min="0" max="255"
                               value="{{ old('sort_order', $editingAnnual?->sort_order ?? 0) }}"
                               class="w-16 px-2 py-1.5 rounded-lg border border-border bg-surface text-primary text-sm text-center">
                    </div>
                </div>

                <div class="flex items-center gap-2 justify-end">
                    @if($editingAnnual)
                        <a href="/admin/synaxarium" class="px-4 py-2 text-sm text-muted-text hover:text-primary transition">{{ __('app.cancel') }}</a>
                    @endif
                    <button type="submit" class="px-5 py-2 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                        {{ $editingAnnual ? __('app.save_changes') : __('app.create') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Annual List (grouped by month+day) --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="space-y-4">
                @forelse($annualByMonthDay as $key => $saints)
                @php
                    $firstSaint = $saints->first();
                    $monthLabel = $monthNames[$firstSaint->month] ?? $firstSaint->month;
                @endphp
                <div class="rounded-xl border border-border bg-surface overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gradient-to-r from-amber-50 to-transparent dark:from-amber-900/10 border-b border-border">
                        <span class="text-sm font-bold text-primary">{{ $monthLabel }} / {{ $firstSaint->day }}</span>
                        <span class="text-[10px] text-muted-text font-medium">{{ $saints->count() }} {{ $saints->count() === 1 ? 'saint' : 'saints' }}</span>
                    </div>
                    <div class="divide-y divide-border/50">
                        @foreach($saints as $item)
                        <div class="p-3 flex items-start gap-3 group hover:bg-muted/50 transition">
                            @if($item->image_path)
                                <img src="{{ $item->imageUrl() }}" alt="" class="w-10 h-10 rounded-lg object-cover shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-sinksar/10 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-sinksar" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-medium text-primary text-sm">{{ $item->celebration_en }}</span>
                                    @if($item->is_main)
                                        <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">{{ __('app.synaxarium_main_badge') }}</span>
                                    @endif
                                </div>
                                @if($item->celebration_am)
                                    <p class="text-xs text-muted-text">{{ $item->celebration_am }}</p>
                                @endif
                                @if($item->description_en)
                                    <p class="text-xs text-secondary mt-1 line-clamp-2">{{ $item->description_en }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-0.5 shrink-0 opacity-50 group-hover:opacity-100 transition">
                                <a href="/admin/synaxarium?edit_annual={{ $item->id }}" class="p-1.5 rounded-lg text-accent hover:bg-accent/10 transition" title="{{ __('app.edit') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="/admin/synaxarium/annual/{{ $item->id }}" onsubmit="return confirm('{{ __('app.synaxarium_delete_confirm') }}')" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition" title="{{ __('app.delete') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-muted-text text-sm">
                    {{ __('app.synaxarium_no_annual') }}
                </div>
                @endforelse
            </div>
        </div>
    </div>

</div>
@endsection

@extends('layouts.admin')

@section('title', __('app.synaxarium_admin_title'))

@section('content')
<div class="max-w-3xl" x-data="{ tab: '{{ request()->query('edit_annual') ? 'annual' : 'monthly' }}' }">

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

        {{-- Monthly Create/Edit Form --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 mb-6" x-data="{ lang: 'en' }">
            <h2 class="text-base font-semibold text-primary mb-4">
                {{ $editingMonthly ? __('app.synaxarium_edit_monthly') : __('app.synaxarium_add_monthly') }}
            </h2>

            <form method="POST"
                  action="{{ $editingMonthly ? '/admin/synaxarium/monthly/'.$editingMonthly->id : '/admin/synaxarium/monthly' }}"
                  enctype="multipart/form-data">
                @csrf
                @if($editingMonthly) @method('PUT') @endif

                {{-- Day number (only on create) --}}
                @unless($editingMonthly)
                <div class="mb-4">
                    <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.synaxarium_day_number') }}</label>
                    <input type="number" name="day" min="1" max="30"
                           value="{{ old('day') }}"
                           class="w-24 px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                           required>
                    @error('day')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                @endunless

                {{-- Language tabs --}}
                <div class="border border-border rounded-2xl overflow-hidden mb-4">
                    <div class="flex border-b border-border bg-muted">
                        <button type="button" @click="lang = 'en'"
                                :class="lang === 'en' ? 'border-b-2 border-accent text-accent bg-card font-semibold' : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">English</button>
                        <button type="button" @click="lang = 'am'"
                                :class="lang === 'am' ? 'border-b-2 border-accent text-accent bg-card font-semibold' : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">&#x12A0;&#x121B;&#x122D;&#x129B;</button>
                    </div>
                    <div x-show="lang === 'en'" class="p-4 bg-card">
                        <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                            {{ __('app.synaxarium_celebration') }} <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="celebration_en"
                               value="{{ old('celebration_en', $editingMonthly?->celebration_en) }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. Angel Mikael (Michael)" required>
                        @error('celebration_en')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="lang === 'am'" x-cloak class="p-4 bg-card">
                        <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                            {{ __('app.synaxarium_celebration') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)
                        </label>
                        <input type="text" name="celebration_am"
                               value="{{ old('celebration_am', $editingMonthly?->celebration_am) }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. ቅዱስ ሚካኤል">
                    </div>
                </div>

                {{-- Image upload --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.synaxarium_image') }}</label>
                    @if($editingMonthly?->image_path)
                        <div class="mb-2 flex items-end gap-3">
                            <img src="{{ $editingMonthly->imageUrl() }}" alt="" class="h-24 rounded-xl object-cover">
                            <label class="inline-flex items-center gap-2 text-sm text-red-500 hover:text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-border text-red-500 focus:ring-red-400">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-sm text-muted-text file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition">
                </div>

                <div class="flex items-center gap-3 justify-end">
                    @if($editingMonthly)
                        <a href="/admin/synaxarium" class="px-4 py-2.5 text-sm font-medium text-muted-text hover:text-primary transition">{{ __('app.cancel') }}</a>
                    @endif
                    <button type="submit" class="px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                        {{ $editingMonthly ? __('app.save_changes') : __('app.create') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Monthly List --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="space-y-3">
                @forelse($monthlyCelebrations as $item)
                <div class="p-4 rounded-xl border border-border bg-surface space-y-2">
                    <div class="flex items-start gap-3">
                        @if($item->image_path)
                            <img src="{{ $item->imageUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                <span class="text-lg font-bold text-muted-text">{{ $item->day }}</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-accent/10 text-accent">
                                    {{ __('app.synaxarium_day_number_short', ['day' => $item->day]) }}
                                </span>
                            </div>
                            <p class="font-medium text-primary text-sm">{{ $item->celebration_en }}</p>
                            @if($item->celebration_am)
                                <p class="text-xs text-muted-text mt-0.5">{{ $item->celebration_am }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 border-t border-border pt-2">
                        <a href="/admin/synaxarium?edit_monthly={{ $item->id }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-accent hover:bg-accent/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ __('app.edit') }}
                        </a>
                        <form method="POST" action="/admin/synaxarium/monthly/{{ $item->id }}"
                              onsubmit="return confirm('{{ __('app.synaxarium_delete_confirm') }}')"
                              class="inline ml-auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                {{ __('app.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-muted-text text-sm">
                    {{ __('app.synaxarium_no_monthly') }}
                </div>
                @endforelse
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
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6 mb-6" x-data="{ lang: 'en' }">
            <h2 class="text-base font-semibold text-primary mb-4">
                {{ $editingAnnual ? __('app.synaxarium_edit_annual') : __('app.synaxarium_add_annual') }}
            </h2>

            <form method="POST"
                  action="{{ $editingAnnual ? '/admin/synaxarium/annual/'.$editingAnnual->id : '/admin/synaxarium/annual' }}"
                  enctype="multipart/form-data">
                @csrf
                @if($editingAnnual) @method('PUT') @endif

                @unless($editingAnnual)
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.synaxarium_month_number') }}</label>
                        <select name="month" required
                                class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent">
                            @for($m = 1; $m <= 13; $m++)
                                <option value="{{ $m }}" {{ old('month') == $m ? 'selected' : '' }}>{{ $m }} - {{ $monthNames[$m] }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-secondary mb-1">{{ __('app.synaxarium_day_number') }}</label>
                        <input type="number" name="day" min="1" max="30"
                               value="{{ old('day') }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               required>
                        @error('day')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endunless

                {{-- Language tabs --}}
                <div class="border border-border rounded-2xl overflow-hidden mb-4">
                    <div class="flex border-b border-border bg-muted">
                        <button type="button" @click="lang = 'en'"
                                :class="lang === 'en' ? 'border-b-2 border-accent text-accent bg-card font-semibold' : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">English</button>
                        <button type="button" @click="lang = 'am'"
                                :class="lang === 'am' ? 'border-b-2 border-accent text-accent bg-card font-semibold' : 'text-muted-text hover:text-primary'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 text-sm transition">&#x12A0;&#x121B;&#x122D;&#x129B;</button>
                    </div>
                    <div x-show="lang === 'en'" class="p-4 bg-card">
                        <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                            {{ __('app.synaxarium_celebration') }} <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="celebration_en"
                               value="{{ old('celebration_en', $editingAnnual?->celebration_en) }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. Ethiopian Christmas (Genna)" required>
                        @error('celebration_en')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div x-show="lang === 'am'" x-cloak class="p-4 bg-card">
                        <label class="block text-xs font-semibold text-muted-text uppercase tracking-wide mb-1.5">
                            {{ __('app.synaxarium_celebration') }} (&#x12A0;&#x121B;&#x122D;&#x129B;)
                        </label>
                        <input type="text" name="celebration_am"
                               value="{{ old('celebration_am', $editingAnnual?->celebration_am) }}"
                               class="w-full px-3 py-2.5 rounded-xl border border-border bg-surface text-primary text-sm focus:outline-none focus:ring-2 focus:ring-accent/50 focus:border-accent"
                               placeholder="e.g. ገና">
                    </div>
                </div>

                {{-- Image upload --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-secondary mb-1.5">{{ __('app.synaxarium_image') }}</label>
                    @if($editingAnnual?->image_path)
                        <div class="mb-2 flex items-end gap-3">
                            <img src="{{ $editingAnnual->imageUrl() }}" alt="" class="h-24 rounded-xl object-cover">
                            <label class="inline-flex items-center gap-2 text-sm text-red-500 hover:text-red-600 cursor-pointer">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-border text-red-500 focus:ring-red-400">
                                {{ __('app.remove') }}
                            </label>
                        </div>
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="w-full text-sm text-muted-text file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-accent/10 file:text-accent hover:file:bg-accent/20 transition">
                </div>

                <div class="flex items-center gap-3 justify-end">
                    @if($editingAnnual)
                        <a href="/admin/synaxarium" class="px-4 py-2.5 text-sm font-medium text-muted-text hover:text-primary transition">{{ __('app.cancel') }}</a>
                    @endif
                    <button type="submit" class="px-5 py-2.5 bg-accent text-on-accent text-sm font-semibold rounded-xl hover:opacity-90 transition active:scale-95">
                        {{ $editingAnnual ? __('app.save_changes') : __('app.create') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Annual List --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-6">
            <div class="space-y-3">
                @forelse($annualCelebrations as $item)
                <div class="p-4 rounded-xl border border-border bg-surface space-y-2">
                    <div class="flex items-start gap-3">
                        @if($item->image_path)
                            <img src="{{ $item->imageUrl() }}" alt="" class="w-12 h-12 rounded-lg object-cover shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-lg bg-muted flex items-center justify-center shrink-0">
                                <span class="text-xs font-bold text-muted-text">{{ $item->month }}/{{ $item->day }}</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                    {{ $monthNames[$item->month] ?? $item->month }} / {{ $item->day }}
                                </span>
                            </div>
                            <p class="font-medium text-primary text-sm">{{ $item->celebration_en }}</p>
                            @if($item->celebration_am)
                                <p class="text-xs text-muted-text mt-0.5">{{ $item->celebration_am }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1 border-t border-border pt-2">
                        <a href="/admin/synaxarium?edit_annual={{ $item->id }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-accent hover:bg-accent/10 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            {{ __('app.edit') }}
                        </a>
                        <form method="POST" action="/admin/synaxarium/annual/{{ $item->id }}"
                              onsubmit="return confirm('{{ __('app.synaxarium_delete_confirm') }}')"
                              class="inline ml-auto">
                            @csrf @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                {{ __('app.delete') }}
                            </button>
                        </form>
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

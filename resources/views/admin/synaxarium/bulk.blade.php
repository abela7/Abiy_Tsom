@extends('layouts.admin')

@section('title', __('app.synaxarium_bulk_title'))

@section('content')

@php
$monthNamesFull = [
    1 => 'Meskerem / መስከረም', 2 => 'Tikimt / ጥቅምት', 3 => 'Hidar / ኅዳር',
    4 => 'Tahsas / ታኅሣሥ', 5 => 'Tir / ጥር', 6 => 'Yekatit / የካቲት',
    7 => 'Megabit / መጋቢት', 8 => 'Miyazia / ሚያዝያ', 9 => 'Ginbot / ግንቦት',
    10 => 'Sene / ሰኔ', 11 => 'Hamle / ሐምሌ', 12 => 'Nehase / ነሐሴ',
    13 => 'Pagumen / ጳጉሜን',
];
@endphp

<style>[x-cloak]{display:none!important}</style>

<div class="max-w-4xl lg:max-w-6xl pb-28 lg:pb-8"
     x-data="{
         kind: @json($defaultKind),
         day: {{ (int) $defaultDay }},
         month: {{ (int) $defaultMonth }},
         entries: @json($defaultEntries),
         emptyEntry: @json($emptyBulkEntry),
         addRow() {
             this.entries.push(JSON.parse(JSON.stringify(this.emptyEntry)));
         },
         removeRow(i) {
             if (this.entries.length > 1) this.entries.splice(i, 1);
         }
     }">

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between mb-5">
        <div>
            <h1 class="text-xl font-bold text-primary leading-tight">{{ __('app.synaxarium_bulk_title') }}</h1>
            <p class="text-sm text-muted-text mt-1 max-w-xl">{{ __('app.synaxarium_bulk_subtitle') }}</p>
        </div>
        <a href="{{ route('admin.synaxarium.index') }}"
           class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-semibold border border-border bg-card text-primary hover:bg-muted/60 transition shrink-0">
            {{ __('app.synaxarium_bulk_back') }}
        </a>
    </div>

    @if ($errors->any())
    <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 dark:bg-red-900/20 dark:border-red-800 px-4 py-3">
        <p class="text-sm font-semibold text-red-800 dark:text-red-200">{{ __('app.synaxarium_bulk_row_errors') }}</p>
        <ul class="mt-2 text-xs text-red-700 dark:text-red-300 list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $message)
            <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="post" action="{{ route('admin.synaxarium.bulk.store') }}" class="space-y-4">
        @csrf

        {{-- Single calendar slot for every row below --}}
        <div class="bg-card rounded-2xl border border-border shadow-sm p-4 space-y-4">
            <h2 class="text-sm font-semibold text-primary">{{ __('app.synaxarium_bulk_when_heading') }}</h2>
            <p class="text-xs text-muted-text -mt-2">{{ __('app.synaxarium_bulk_when_help') }}</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_bulk_kind') }}</label>
                    <select x-model="kind" name="kind"
                            class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary">
                        <option value="monthly">{{ __('app.synaxarium_bulk_kind_monthly') }}</option>
                        <option value="annual">{{ __('app.synaxarium_bulk_kind_annual') }}</option>
                    </select>
                </div>
                <div x-show="kind === 'annual'" x-cloak>
                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_month_number') }}</label>
                    <select x-model.number="month" name="month"
                            class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary">
                        @foreach ($monthNamesFull as $num => $label)
                        <option value="{{ $num }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_day_number') }}</label>
                    <select x-model.number="day" name="day"
                            class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary">
                        @for ($d = 1; $d <= 30; $d++)
                        <option value="{{ $d }}">{{ $d }}</option>
                        @endfor
                    </select>
                </div>
            </div>
        </div>

        <h2 class="text-sm font-semibold text-primary px-1">{{ __('app.synaxarium_bulk_saints_heading') }}</h2>

        <template x-for="(row, index) in entries" :key="index">
            <div class="bg-card rounded-2xl border border-border shadow-sm p-4 space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-muted-text uppercase tracking-wide" x-text="'#' + (index + 1)"></span>
                    <button type="button"
                            @click="removeRow(index)"
                            class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline disabled:opacity-40"
                            :disabled="entries.length <= 1">
                        {{ __('app.synaxarium_bulk_remove_row') }}
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" value="1" x-model="row.is_main"
                           class="rounded border-border text-accent focus:ring-accent"
                           :name="'entries[' + index + '][is_main]'">
                    <span class="text-sm text-primary">{{ __('app.synaxarium_is_main') }}</span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (EN)</label>
                        <input type="text" maxlength="500" x-model="row.celebration_en"
                               class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary"
                               :name="'entries[' + index + '][celebration_en]'"
                               autocomplete="off">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_sort_order') }}</label>
                        <input type="number" min="0" max="255" x-model.number="row.sort_order"
                               class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary"
                               :name="'entries[' + index + '][sort_order]'">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_celebration') }} (AM)</label>
                    <input type="text" maxlength="500" x-model="row.celebration_am"
                           class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary"
                           :name="'entries[' + index + '][celebration_am]'"
                           autocomplete="off">
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_description') }} (EN)</label>
                        <textarea rows="2" x-model="row.description_en"
                                  class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary resize-y min-h-[4rem]"
                                  :name="'entries[' + index + '][description_en]'"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-muted-text mb-1">{{ __('app.synaxarium_description') }} (AM)</label>
                        <textarea rows="2" x-model="row.description_am"
                                  class="w-full rounded-xl border border-border bg-surface px-3 py-2.5 text-sm text-primary resize-y min-h-[4rem]"
                                  :name="'entries[' + index + '][description_am]'"></textarea>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <button type="button" @click="addRow()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-3 sm:py-2.5 rounded-xl text-sm font-semibold border border-border bg-card text-primary hover:bg-muted/60 transition">
                {{ __('app.synaxarium_bulk_add_row') }}
            </button>
            <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 sm:py-2.5 rounded-xl text-sm font-semibold bg-accent text-on-accent shadow-sm hover:opacity-95 active:scale-[0.98] transition">
                {{ __('app.synaxarium_bulk_save') }}
            </button>
        </div>
    </form>
</div>
@endsection

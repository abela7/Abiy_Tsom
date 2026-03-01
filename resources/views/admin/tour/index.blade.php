@extends('layouts.admin')

@section('title', __('app.tour_management_title'))

@section('content')
<div class="max-w-3xl">

    <h1 class="text-2xl font-bold text-primary mb-1">{{ __('app.tour_management_title') }}</h1>
    <p class="text-sm text-muted-text mb-6">{{ __('app.tour_management_subtitle') }}</p>

    @if(session('success'))
        <div class="mb-5 px-4 py-3 rounded-xl bg-green-50 border border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400 text-sm">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.total_members') }}</p>
            <p class="text-2xl font-black text-primary mt-1">{{ number_format($totalMembers) }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.tour_completed_count') }}</p>
            <p class="text-2xl font-black text-success mt-1">{{ number_format($tourCompletedCount) }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
            <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.tour_not_completed_count') }}</p>
            <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($tourNotCompletedCount) }}</p>
        </div>
    </div>

    {{-- Clear / Delete tour data --}}
    <div class="bg-card rounded-2xl border border-border shadow-sm overflow-hidden"
         x-data="{ confirmClear: false }">
        <div class="px-6 py-4 border-b border-border">
            <h2 class="text-base font-semibold text-primary">{{ __('app.tour_clear_section_title') }}</h2>
            <p class="text-sm text-muted-text mt-1">{{ __('app.tour_clear_section_desc') }}</p>
        </div>
        <div class="p-6">
            @if($tourCompletedCount > 0)
                <div x-show="!confirmClear">
                    <button type="button"
                            @click="confirmClear = true"
                            class="px-4 py-2.5 rounded-xl bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 dark:text-amber-400 dark:hover:bg-amber-500/20 text-sm font-semibold transition border border-amber-500/20">
                        {{ __('app.tour_clear_all_btn') }}
                    </button>
                </div>
                <div x-show="confirmClear" x-cloak class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-semibold text-amber-600 dark:text-amber-400">{{ __('app.tour_clear_confirm') }}</span>
                    <form method="POST" action="{{ route('admin.tour.clear-all') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl bg-amber-500 text-white hover:bg-amber-600 text-sm font-semibold transition">
                            {{ __('app.yes') }} {{ __('app.tour_clear_all_btn') }}
                        </button>
                    </form>
                    <button type="button" @click="confirmClear = false"
                            class="px-4 py-2 rounded-xl bg-muted text-secondary hover:bg-border text-sm font-semibold transition">
                        {{ __('app.cancel') }}
                    </button>
                </div>
            @else
                <p class="text-sm text-muted-text">{{ __('app.tour_nothing_to_clear') }}</p>
            @endif
        </div>
    </div>

    <p class="mt-4 text-xs text-muted-text">
        {{ __('app.tour_management_hint') }}
    </p>
</div>
@endsection

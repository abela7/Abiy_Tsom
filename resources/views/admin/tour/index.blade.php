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

    {{-- Member list --}}
    <div class="mt-8">
        <h2 class="text-base font-semibold text-primary mb-3">{{ __('app.tour_member_list_title') }}</h2>

        {{-- Desktop table --}}
        <div class="hidden sm:block bg-card rounded-2xl border border-border shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border bg-muted/40">
                        <th class="text-left px-5 py-3 font-semibold text-muted-text">{{ __('app.member_name') }}</th>
                        <th class="text-left px-5 py-3 font-semibold text-muted-text">{{ __('app.tour_status') }}</th>
                        <th class="text-left px-5 py-3 font-semibold text-muted-text">{{ __('app.tour_completed_at_label') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @foreach($members as $member)
                    <tr class="hover:bg-muted/30 transition">
                        <td class="px-5 py-3 font-medium text-primary">{{ $member->baptism_name }}</td>
                        <td class="px-5 py-3">
                            @if($member->tour_completed_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    {{ __('app.tour_status_done') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-muted text-muted-text text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-border"></span>
                                    {{ __('app.tour_status_pending') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-muted-text text-xs">
                            {{ $member->tour_completed_at ? $member->tour_completed_at->format('d M Y, H:i') : '—' }}
                        </td>
                        <td class="px-5 py-3 text-right">
                            @if($member->tour_completed_at)
                            <form method="POST" action="{{ route('admin.tour.reset-member', $member) }}"
                                  onsubmit="return confirm('{{ __('app.tour_reset_member_confirm', ['name' => $member->baptism_name]) }}')">
                                @csrf
                                <button type="submit"
                                        class="text-xs text-amber-600 dark:text-amber-400 hover:underline font-semibold">
                                    {{ __('app.tour_reset_btn') }}
                                </button>
                            </form>
                            @else
                                <span class="text-xs text-muted-text">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-3">
            @foreach($members as $member)
            <div class="bg-card rounded-xl border border-border shadow-sm p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <p class="font-semibold text-primary text-sm">{{ $member->baptism_name }}</p>
                    @if($member->tour_completed_at)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-xs font-semibold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            {{ __('app.tour_status_done') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-muted text-muted-text text-xs font-semibold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-border"></span>
                            {{ __('app.tour_status_pending') }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-muted-text mb-3">
                    {{ $member->tour_completed_at ? $member->tour_completed_at->format('d M Y, H:i') : __('app.tour_not_completed_yet') }}
                </p>
                @if($member->tour_completed_at)
                <form method="POST" action="{{ route('admin.tour.reset-member', $member) }}"
                      onsubmit="return confirm('{{ __('app.tour_reset_member_confirm', ['name' => $member->baptism_name]) }}')">
                    @csrf
                    <button type="submit"
                            class="w-full text-xs font-semibold text-amber-600 dark:text-amber-400 border border-amber-400/40 rounded-lg py-1.5 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">
                        {{ __('app.tour_reset_btn') }}
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

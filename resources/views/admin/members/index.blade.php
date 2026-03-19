@extends('layouts.admin')

@section('title', __('app.members_tracking'))

@section('content')
<div class="space-y-6">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-bold text-primary">{{ __('app.members_tracking') }}</h1>
        <p class="text-sm text-muted-text mt-1">{{ __('app.members_tracking_subtitle') }}</p>
    </div>

    @if (! $telegramBotUsername)
        <div class="bg-yellow-950/20 border border-yellow-500/40 text-yellow-200 rounded-xl px-4 py-3 text-sm">
            Telegram bot username is not set. Configure it in Telegram settings to generate one-tap member links.
        </div>
    @endif

    {{-- ───────── Stats overview ───────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-3">
        {{-- Total --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-accent/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-accent tabular-nums">{{ number_format($totalMembers) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.total_members') }}</p>
        </div>

        {{-- Last 7 days --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-accent-secondary/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-accent-secondary tabular-nums">{{ number_format($last7Days) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.new_last_7_days') }}</p>
        </div>

        {{-- Last 30 days --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-accent-secondary/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-accent-secondary tabular-nums">{{ number_format($last30Days) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.new_last_30_days') }}</p>
        </div>

        {{-- Engaged --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-success tabular-nums">{{ number_format($engagedMembers) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.engaged_members') }}</p>
        </div>

        {{-- Passcode --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-primary tabular-nums">{{ number_format($passcodeEnabled) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.passcode_users') }}</p>
        </div>

        {{-- Tour completed --}}
        <div class="bg-card rounded-xl p-4 border border-border shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-accent-secondary/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-accent-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-accent-secondary tabular-nums">{{ number_format($tourCompletedCount) }}</p>
            <p class="text-[11px] text-muted-text font-medium mt-0.5">{{ __('app.tour_completed_count') }}</p>
        </div>

        {{-- Non-UK WhatsApp --}}
        @if($nonUkRequested > 0)
        <div class="bg-card rounded-xl p-4 border border-amber-500/30 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-amber-500/10 flex items-center justify-center">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="text-2xl font-black text-amber-500 tabular-nums">{{ number_format($nonUkRequested) }}</p>
            <p class="text-[11px] text-amber-600 font-medium mt-0.5">Non-UK WhatsApp</p>
        </div>
        @endif
    </div>

    {{-- ───────── Analytics panels ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Registrations by day --}}
        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    {{ __('app.registrations_by_day') }}
                </h2>
            </div>
            <div class="overflow-y-auto max-h-64">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-semibold text-muted-text">{{ __('app.date_label') }}</th>
                            <th class="text-right px-4 py-2 text-xs font-semibold text-muted-text">{{ __('app.count') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/50">
                        @forelse($registrationsByDay->reverse() as $row)
                            <tr class="hover:bg-muted/30">
                                <td class="px-4 py-2 text-secondary text-xs">{{ \Carbon\Carbon::parse($row->date)->format('D, d M') }}</td>
                                <td class="px-4 py-2 text-right font-bold text-accent text-xs tabular-nums">{{ $row->count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-6 text-center text-muted-text text-xs">{{ __('app.no_registrations_yet') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Locale --}}
        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    {{ __('app.locale_distribution') }}
                </h2>
            </div>
            <div class="p-4 space-y-1">
                @forelse($localeDistribution as $row)
                    @php $pct = $totalMembers > 0 ? round($row->count / $totalMembers * 100) : 0; @endphp
                    <div class="flex items-center gap-3 py-2">
                        <span class="text-sm font-semibold text-primary w-16">{{ $row->locale === 'en' ? __('app.english') : ($row->locale === 'am' ? __('app.amharic') : $row->locale) }}</span>
                        <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                            <div class="h-full bg-accent rounded-full transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-xs font-bold text-accent tabular-nums w-10 text-right">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-muted-text text-sm py-4 text-center">{{ __('app.no_data_short') }}</p>
                @endforelse
            </div>
        </div>

        {{-- Theme + Completions --}}
        <div class="space-y-4">
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-border">
                    <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                        <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        {{ __('app.theme_distribution') }}
                    </h2>
                </div>
                <div class="p-4 space-y-1">
                    @forelse($themeDistribution as $row)
                        @php $pct = $totalMembers > 0 ? round($row->count / $totalMembers * 100) : 0; @endphp
                        <div class="flex items-center gap-3 py-2">
                            <span class="text-sm font-semibold text-primary w-16 capitalize">{{ $row->theme }}</span>
                            <div class="flex-1 h-2 bg-muted rounded-full overflow-hidden">
                                <div class="h-full bg-accent-secondary rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-accent-secondary tabular-nums w-10 text-right">{{ $row->count }}</span>
                        </div>
                    @empty
                        <p class="text-muted-text text-sm py-4 text-center">{{ __('app.no_data_short') }}</p>
                    @endforelse
                </div>
            </div>

            {{-- Completions --}}
            <div class="bg-card rounded-xl border border-border shadow-sm p-4">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2 mb-3">
                    <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('app.total_completions') }}
                </h2>
                <p class="text-2xl font-black text-primary tabular-nums">{{ number_format($totalChecklistCompletions + $totalCustomCompletions) }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-muted-text">
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-accent inline-block"></span>
                        {{ number_format($totalChecklistCompletions) }} standard
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-accent-secondary inline-block"></span>
                        {{ number_format($totalCustomCompletions) }} custom
                    </span>
                </div>
            </div>

            {{-- Registration range --}}
            @if($firstRegistration || $lastRegistration)
            <div class="bg-card rounded-xl border border-border shadow-sm p-4">
                <div class="flex items-center justify-between text-xs">
                    <div>
                        <p class="text-muted-text font-medium">{{ __('app.first_registration') }}</p>
                        <p class="text-sm font-bold text-primary mt-0.5">{{ $firstRegistration ? \Carbon\Carbon::parse($firstRegistration)->format('d M Y') : '—' }}</p>
                    </div>
                    <svg class="w-5 h-5 text-muted-text/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                    <div class="text-right">
                        <p class="text-muted-text font-medium">{{ __('app.last_registration') }}</p>
                        <p class="text-sm font-bold text-primary mt-0.5">{{ $lastRegistration ? \Carbon\Carbon::parse($lastRegistration)->format('d M Y') : '—' }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ───────── Member list ───────── --}}
    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden"
         x-data="{ confirmWipeAll: false }">

        {{-- Header --}}
        <div class="px-5 py-4 border-b border-border">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-base font-bold text-primary flex items-center gap-2">
                        <svg class="w-5 h-5 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        {{ __('app.member_list') }}
                        <span class="text-sm font-normal text-muted-text">({{ $members->total() }})</span>
                    </h2>
                    <p class="text-xs text-muted-text mt-0.5">{{ __('app.member_list_subtitle') }}</p>
                </div>

                {{-- Wipe All --}}
                <div x-show="!confirmWipeAll">
                    <button type="button" @click="confirmWipeAll = true"
                            class="px-3 py-1.5 rounded-lg bg-red-600/10 text-red-500 hover:bg-red-600/20 text-xs font-semibold transition border border-red-600/20">
                        {{ __('app.wipe_all_members') }}
                    </button>
                </div>
                <div x-show="confirmWipeAll" x-cloak class="flex flex-col items-end gap-2">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-red-500">{{ __('app.confirm_wipe_all') }}</span>
                        <form method="POST" action="{{ route('admin.members.wipe-all') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">{{ __('app.yes_wipe_all') }}</button>
                        </form>
                        <button type="button" @click="confirmWipeAll = false" class="px-3 py-1.5 rounded-lg bg-muted text-secondary text-xs font-semibold hover:bg-border transition">{{ __('app.cancel') }}</button>
                    </div>
                    <span class="text-[10px] text-amber-500 font-medium bg-amber-500/10 px-2 py-1 rounded-md border border-amber-500/20">
                        All WhatsApp reminders will be stopped for all members
                    </span>
                </div>
            </div>
        </div>

        {{-- Activity filter --}}
        <div class="px-5 py-3 border-b border-border bg-muted/30" x-data="{ showCustom: {{ $activeFilter === 'custom' ? 'true' : 'false' }} }">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-bold text-muted-text uppercase tracking-wider mr-1">Filter:</span>
                @php
                    $filters = [
                        '' => 'All',
                        'today' => 'Active Today',
                        '1d' => '1d+ Inactive',
                        '2d' => '2d+ Inactive',
                        '3d' => '3d+ Inactive',
                        '7d' => '7d+ Inactive',
                        '30d' => '30d+ Inactive',
                    ];
                @endphp
                @foreach($filters as $value => $label)
                    <a href="{{ route('admin.members.index', array_merge(request()->except('page', 'active', 'from', 'to'), $value ? ['active' => $value] : [])) }}"
                       class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition border {{ $activeFilter === $value ? 'bg-accent text-on-accent border-accent shadow-sm' : 'bg-card text-secondary border-border hover:border-accent/40 hover:text-primary' }}">
                        {{ $label }}
                    </a>
                @endforeach

                <span class="w-px h-5 bg-border mx-1"></span>

                <button type="button" @click="showCustom = !showCustom"
                        class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition border {{ $activeFilter === 'custom' ? 'bg-accent text-on-accent border-accent shadow-sm' : 'bg-card text-secondary border-border hover:border-accent/40 hover:text-primary' }}">
                    Custom
                </button>
            </div>

            {{-- Custom range --}}
            <form x-show="showCustom" x-transition.duration.150ms method="GET" action="{{ route('admin.members.index') }}"
                  class="flex items-center gap-2 flex-wrap mt-3 pt-3 border-t border-border/50">
                <input type="hidden" name="active" value="custom">
                <label class="text-xs text-muted-text font-medium">From</label>
                <input type="date" name="from" value="{{ request('from', now()->subDays(7)->format('Y-m-d')) }}"
                       class="px-2.5 py-1.5 rounded-lg text-xs border border-border bg-surface text-primary focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none">
                <label class="text-xs text-muted-text font-medium">To</label>
                <input type="date" name="to" value="{{ request('to', now()->format('Y-m-d')) }}"
                       class="px-2.5 py-1.5 rounded-lg text-xs border border-border bg-surface text-primary focus:ring-2 focus:ring-accent/30 focus:border-accent outline-none">
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold bg-accent text-on-accent border border-accent hover:brightness-110 transition shadow-sm">
                    Apply
                </button>
            </form>
        </div>

        {{-- Flash message --}}
        @if(session('success'))
            <div class="px-5 py-3 bg-success/10 border-b border-success/20 text-sm font-medium text-success flex items-center gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/60 border-b border-border">
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">#</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.baptism_name') }}</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.locale_label') }}</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.theme') }}</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.tour_column') }}</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">WhatsApp</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.registered_at') }}</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">Last Active</th>
                        <th class="text-left px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">IP</th>
                        <th class="text-right px-4 py-2.5 text-[11px] font-bold text-muted-text uppercase tracking-wider">{{ __('app.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($members as $member)
                    @php $latestSession = $member->sessions->first(); @endphp
                    <tr class="hover:bg-muted/30 transition-colors" x-data="{
                        confirmDelete: false,
                        confirmWipe: false,
                        hasWhatsApp: {{ $member->whatsapp_reminder_enabled && $member->whatsapp_confirmation_status === 'confirmed' ? 'true' : 'false' }},
                        whatsappPhone: '{{ $member->whatsapp_phone ?? '' }}'
                    }">
                        {{-- ID --}}
                        <td class="px-4 py-3 text-muted-text/60 tabular-nums text-xs">{{ $member->id }}</td>

                        {{-- Name --}}
                        <td class="px-4 py-3">
                            <span class="font-semibold text-primary">{{ $member->baptism_name }}</span>
                        </td>

                        {{-- Locale --}}
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wide
                                {{ $member->locale === 'am' ? 'bg-green-500/10 text-green-600' : 'bg-blue-500/10 text-blue-500' }}">
                                {{ $member->locale ?? '—' }}
                            </span>
                        </td>

                        {{-- Theme --}}
                        <td class="px-4 py-3">
                            <span class="text-xs text-secondary capitalize">{{ $member->theme ?? '—' }}</span>
                        </td>

                        {{-- Tour --}}
                        <td class="px-4 py-3">
                            @if($member->tour_completed_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-success/10 text-success text-[10px] font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    {{ __('app.tour_completed_short') }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-md bg-muted text-muted-text text-[10px] font-semibold">{{ __('app.tour_not_completed_short') }}</span>
                            @endif
                        </td>

                        {{-- WhatsApp --}}
                        <td class="px-4 py-3">
                            @if($member->whatsapp_confirmation_status === 'confirmed')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-success/10 text-success text-[10px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span>
                                    {{ __('app.active') }}
                                </span>
                                <div class="text-[10px] text-muted-text mt-1 font-mono">{{ $member->whatsapp_phone }} · {{ $member->whatsapp_reminder_time ? \Carbon\Carbon::parse($member->whatsapp_reminder_time)->format('H:i') : '—' }}</div>
                            @elseif($member->whatsapp_confirmation_status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-600 text-[10px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    {{ __('app.pending') }}
                                </span>
                                <div class="text-[10px] text-muted-text mt-1 font-mono">{{ $member->whatsapp_phone ?? '—' }}</div>
                            @elseif($member->whatsapp_confirmation_status === 'rejected')
                                <span class="px-2 py-0.5 rounded-md bg-red-500/10 text-red-500 text-[10px] font-bold">{{ __('app.rejected') }}</span>
                            @elseif($member->whatsapp_non_uk_requested)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-600 text-[10px] font-bold">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Non-UK
                                </span>
                                <div class="text-[10px] text-muted-text mt-1 font-mono">{{ $member->whatsapp_phone ?? '—' }}</div>
                            @else
                                <span class="text-muted-text/40 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Registered --}}
                        <td class="px-4 py-3 text-xs text-muted-text whitespace-nowrap tabular-nums">
                            {{ $member->created_at->format('d M Y') }}
                        </td>

                        {{-- Last Active --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($latestSession && $latestSession->last_used_at)
                                @php
                                    $diffHours = $latestSession->last_used_at->diffInHours(now());
                                    $activeClass = $diffHours < 1 ? 'text-success' : ($diffHours < 24 ? 'text-primary' : ($diffHours < 72 ? 'text-amber-500' : 'text-red-400'));
                                @endphp
                                <span class="text-xs font-medium {{ $activeClass }}">{{ $latestSession->last_used_at->diffForHumans() }}</span>
                            @else
                                <span class="text-xs text-muted-text/40">Never</span>
                            @endif
                        </td>

                        {{-- IP --}}
                        <td class="px-4 py-3 text-[11px] text-muted-text font-mono whitespace-nowrap">
                            {{ $latestSession->ip_address ?? '—' }}
                        </td>

                        {{-- Actions --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                <form method="POST" action="{{ route('admin.members.restart-tour', $member) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-md bg-accent-secondary/10 text-accent-secondary hover:bg-accent-secondary/20 text-[11px] font-semibold transition border border-accent-secondary/20" title="Restart Tour">
                                        {{ __('app.tour_restart_btn') }}
                                    </button>
                                </form>
                                @if ($telegramBotUsername)
                                    <form method="POST" action="{{ route('admin.members.telegram-link', $member) }}">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 rounded-md bg-accent/10 text-accent hover:bg-accent/20 text-[11px] font-semibold border border-accent/20" title="Generate Telegram Link">
                                            TG Link
                                        </button>
                                    </form>
                                @endif

                                {{-- Re-invite (for pending or non-UK members with a phone) --}}
                                @if ($member->whatsapp_phone && ($member->whatsapp_confirmation_status === 'pending' || $member->whatsapp_non_uk_requested))
                                    <form method="POST" action="{{ route('admin.members.reinvite', $member) }}"
                                          x-data="{ confirmReinvite: false }">
                                        @csrf
                                        <button x-show="!confirmReinvite" type="button" @click="confirmReinvite = true"
                                                class="px-2.5 py-1 rounded-md bg-green-500/10 text-green-600 hover:bg-green-500/20 text-[11px] font-semibold transition border border-green-500/20"
                                                title="Send re-invite via WhatsApp">
                                            Re-invite
                                        </button>
                                        <div x-show="confirmReinvite" x-cloak class="flex items-center gap-1">
                                            <span class="text-[11px] text-green-600 font-semibold">Send?</span>
                                            <button type="submit" class="px-2 py-0.5 rounded bg-green-600 text-white text-[11px] font-bold hover:bg-green-700 transition">Yes</button>
                                            <button type="button" @click="confirmReinvite = false" class="px-2 py-0.5 rounded bg-muted text-secondary text-[11px] font-semibold hover:bg-border transition">No</button>
                                        </div>
                                    </form>
                                @endif

                                {{-- Wipe data --}}
                                <template x-if="!confirmWipe && !confirmDelete">
                                    <button type="button" @click="confirmWipe = true"
                                            class="px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 text-[11px] font-semibold transition border border-amber-500/20">
                                        {{ __('app.wipe_data') }}
                                    </button>
                                </template>
                                <template x-if="confirmWipe">
                                    <div class="flex items-center gap-1">
                                        <span class="text-[11px] text-amber-600 font-semibold">{{ __('app.sure') }}?</span>
                                        <form method="POST" action="{{ route('admin.members.wipe-data', $member) }}">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="px-2 py-0.5 rounded bg-amber-500 text-white text-[11px] font-bold hover:bg-amber-600 transition">{{ __('app.yes') }}</button>
                                        </form>
                                        <button type="button" @click="confirmWipe = false" class="px-2 py-0.5 rounded bg-muted text-secondary text-[11px] font-semibold hover:bg-border transition">{{ __('app.no') }}</button>
                                    </div>
                                </template>

                                {{-- Delete member --}}
                                <template x-if="!confirmDelete && !confirmWipe">
                                    <button type="button" @click="confirmDelete = true"
                                            class="px-2.5 py-1 rounded-md bg-red-600/10 text-red-500 hover:bg-red-600/20 text-[11px] font-semibold transition border border-red-600/20">
                                        {{ __('app.delete') }}
                                    </button>
                                </template>
                                <template x-if="confirmDelete">
                                    <div class="flex flex-col items-end gap-1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[11px] text-red-500 font-semibold">{{ __('app.sure') }}?</span>
                                            <form method="POST" action="{{ route('admin.members.destroy', $member) }}">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-2 py-0.5 rounded bg-red-600 text-white text-[11px] font-bold hover:bg-red-700 transition">{{ __('app.yes') }}</button>
                                            </form>
                                            <button type="button" @click="confirmDelete = false" class="px-2 py-0.5 rounded bg-muted text-secondary text-[11px] font-semibold hover:bg-border transition">{{ __('app.no') }}</button>
                                        </div>
                                        <template x-if="hasWhatsApp">
                                            <span class="text-[10px] text-amber-500 font-medium bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                                                WhatsApp (<span x-text="whatsappPhone"></span>) will stop
                                            </span>
                                        </template>
                                    </div>
                                </template>

                                @php $memberTelegramLink = $telegramMemberLinks[$member->id] ?? null; @endphp
                                @if (is_string($memberTelegramLink))
                                    <div class="w-full mt-1.5 flex items-center gap-1">
                                        <input id="member-telegram-link-{{ $member->id }}" type="text"
                                               class="flex-1 min-w-0 px-2 py-1 text-[11px] border border-border rounded-md bg-surface text-secondary font-mono"
                                               value="{{ $memberTelegramLink }}" readonly>
                                        <button type="button" onclick="copyToClipboard('member-telegram-link-{{ $member->id }}')"
                                                class="px-2 py-1 rounded-md bg-muted border border-border text-[11px] hover:bg-border transition">Copy</button>
                                        <a href="{{ $memberTelegramLink }}" target="_blank"
                                           class="px-2 py-1 rounded-md bg-accent text-on-accent text-[11px] font-semibold">Open</a>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-4 py-16 text-center">
                            <svg class="w-10 h-10 text-muted-text/20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <p class="text-muted-text text-sm">{{ __('app.no_members_yet') }}</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($members->hasPages())
        <div class="px-5 py-3 border-t border-border bg-muted/20">
            {{ $members->links() }}
        </div>
        @endif
    </div>

</div>
@push('scripts')
<script>
    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        if (!input) return;
        navigator.clipboard.writeText(input.value).catch(() => {});
    }
</script>
@endpush
@endsection

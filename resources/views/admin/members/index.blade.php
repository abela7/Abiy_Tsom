@extends('layouts.admin')

@section('title', __('app.members_tracking'))

@section('content')
<h1 class="text-2xl font-bold text-primary mb-1">{{ __('app.members_tracking') }}</h1>
<p class="text-sm text-muted-text mb-6">{{ __('app.members_tracking_subtitle') }}</p>

@if (! $telegramBotUsername)
    <div class="bg-yellow-950/20 border border-yellow-500/40 text-yellow-200 rounded-lg px-4 py-3 mb-6">
        Telegram bot username is not set. Configure it in Telegram settings to generate one-tap member links.
    </div>
@endif

{{-- Summary cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.total_members') }}</p>
        <p class="text-2xl font-black text-accent mt-1">{{ number_format($totalMembers) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.new_last_7_days') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($last7Days) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.new_last_30_days') }}</p>
        <p class="text-2xl font-black text-accent-secondary mt-1">{{ number_format($last30Days) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.engaged_members') }}</p>
        <p class="text-2xl font-black text-success mt-1">{{ number_format($engagedMembers) }}</p>
    </div>
    <div class="bg-card rounded-xl p-4 shadow-sm border border-border">
        <p class="text-xs font-semibold text-muted-text uppercase tracking-wider">{{ __('app.passcode_users') }}</p>
        <p class="text-2xl font-black text-primary mt-1">{{ number_format($passcodeEnabled) }}</p>
    </div>
</div>

{{-- Date range --}}
@if($firstRegistration || $lastRegistration)
<div class="bg-card rounded-xl p-4 shadow-sm border border-border mb-6">
    <p class="text-xs font-semibold text-muted-text uppercase tracking-wider mb-3">{{ __('app.first_registration') }} / {{ __('app.last_registration') }}</p>
    <div class="flex flex-wrap gap-4 text-sm">
        @if($firstRegistration)
            <span class="font-medium">{{ \Carbon\Carbon::parse($firstRegistration)->format('d M Y') }}</span>
        @endif
        @if($lastRegistration)
            <span class="font-medium">{{ \Carbon\Carbon::parse($lastRegistration)->format('d M Y') }}</span>
        @endif
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    {{-- Registrations by day --}}
    <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
            <h2 class="text-sm font-bold text-primary">{{ __('app.registrations_by_day') }}</h2>
        </div>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-muted sticky top-0">
                    <tr>
                        <th class="text-left px-4 py-2 font-semibold text-secondary">{{ __('app.date_label') }}</th>
                        <th class="text-right px-4 py-2 font-semibold text-secondary">{{ __('app.count') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($registrationsByDay->reverse() as $row)
                        <tr class="hover:bg-muted/50">
                            <td class="px-4 py-2 font-medium">
                                {{ \Carbon\Carbon::parse($row->date)->format('D, d M Y') }}
                            </td>
                            <td class="px-4 py-2 text-right font-bold text-accent">{{ $row->count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-8 text-center text-muted-text">{{ __('app.no_registrations_yet') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Locale & Theme --}}
    <div class="space-y-6">
        <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary">{{ __('app.locale_distribution') }}</h2>
            </div>
            <div class="p-4">
                @forelse($localeDistribution as $row)
                    <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                        <span class="font-medium">{{ $row->locale === 'en' ? __('app.english') : ($row->locale === 'am' ? __('app.amharic') : $row->locale) }}</span>
                        <span class="font-bold text-accent">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-muted-text text-sm py-4">{{ __('app.no_data_short') }}</p>
                @endforelse
            </div>
        </div>

        <div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden">
            <div class="px-4 py-3 border-b border-border">
                <h2 class="text-sm font-bold text-primary">{{ __('app.theme_distribution') }}</h2>
            </div>
            <div class="p-4">
                @forelse($themeDistribution as $row)
                    <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                        <span class="font-medium capitalize">{{ $row->theme }}</span>
                        <span class="font-bold text-accent">{{ $row->count }}</span>
                    </div>
                @empty
                    <p class="text-muted-text text-sm py-4">{{ __('app.no_data_short') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Engagement stats --}}
<div class="bg-card rounded-xl p-4 shadow-sm border border-border mb-8">
    <p class="text-xs font-semibold text-muted-text uppercase tracking-wider mb-2">{{ __('app.total_completions') }}</p>
    <p class="text-xl font-bold text-primary">
        {{ number_format($totalChecklistCompletions + $totalCustomCompletions) }}
        <span class="text-sm font-normal text-muted-text ml-1">({{ number_format($totalChecklistCompletions) }} standard + {{ number_format($totalCustomCompletions) }} custom)</span>
    </p>
</div>

{{-- Member list --}}
<div class="bg-card rounded-xl shadow-sm border border-border overflow-hidden"
     x-data="{ confirmWipeAll: false }">

    <div class="px-4 py-3 border-b border-border flex items-center justify-between gap-3 flex-wrap">
        <div>
            <h2 class="text-sm font-bold text-primary">{{ __('app.member_list') }}</h2>
            <p class="text-xs text-muted-text mt-0.5">{{ __('app.member_list_subtitle') }}</p>
        </div>

        {{-- Wipe All Members --}}
        <div x-show="!confirmWipeAll">
            <button type="button"
                    @click="confirmWipeAll = true"
                    class="px-4 py-2 rounded-lg bg-red-600/10 text-red-600 hover:bg-red-600/20 text-xs font-bold transition border border-red-600/20">
                {{ __('app.wipe_all_members') }}
            </button>
        </div>
        <div x-show="confirmWipeAll" x-cloak class="flex items-center gap-2">
            <span class="text-xs font-semibold text-red-600">{{ __('app.confirm_wipe_all') }}</span>
            <form method="POST" action="{{ route('admin.members.wipe-all') }}">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-3 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">
                    {{ __('app.yes_wipe_all') }}
                </button>
            </form>
            <button type="button" @click="confirmWipeAll = false"
                    class="px-3 py-1.5 rounded-lg bg-muted text-secondary text-xs font-semibold hover:bg-border transition">
                {{ __('app.cancel') }}
            </button>
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="px-4 py-3 bg-success/10 border-b border-success/20 text-sm font-medium text-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-muted">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">#</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.baptism_name') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.locale_label') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.theme') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.passcode') }}</th>
                    <th class="text-left px-4 py-3 font-semibold text-secondary">{{ __('app.registered_at') }}</th>
                    <th class="text-right px-4 py-3 font-semibold text-secondary">{{ __('app.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                @forelse($members as $member)
                <tr class="hover:bg-muted/40 transition" x-data="{ confirmDelete: false, confirmWipe: false }">
                    <td class="px-4 py-3 text-muted-text tabular-nums">{{ $member->id }}</td>
                    <td class="px-4 py-3 font-semibold text-primary">{{ $member->baptism_name }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-md bg-accent/10 text-accent text-xs font-semibold uppercase">
                            {{ $member->locale ?? '—' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 capitalize text-secondary">{{ $member->theme ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($member->passcode_enabled)
                            <span class="px-2 py-0.5 rounded-md bg-success/10 text-success text-xs font-semibold">{{ __('app.on') }}</span>
                        @else
                            <span class="px-2 py-0.5 rounded-md bg-muted text-muted-text text-xs font-semibold">{{ __('app.off') }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-muted-text whitespace-nowrap">
                        {{ $member->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2 flex-wrap">
                            @if ($telegramBotUsername)
                                <form method="POST" action="{{ route('admin.members.telegram-link', $member) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-3 py-1.5 rounded-lg bg-accent/10 text-accent hover:bg-accent/20 text-xs font-semibold border border-accent/20">
                                        Generate Telegram one-tap link
                                    </button>
                                </form>
                            @endif

                            {{-- Wipe data --}}
                            <template x-if="!confirmWipe && !confirmDelete">
                                <button type="button"
                                        @click="confirmWipe = true"
                                        class="px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 text-xs font-semibold transition border border-amber-500/20">
                                    {{ __('app.wipe_data') }}
                                </button>
                            </template>
                            <template x-if="confirmWipe">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-amber-600 font-semibold">{{ __('app.sure') }}?</span>
                                    <form method="POST" action="{{ route('admin.members.wipe-data', $member) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-md bg-amber-500 text-white text-xs font-bold hover:bg-amber-600 transition">
                                            {{ __('app.yes') }}
                                        </button>
                                    </form>
                                    <button type="button" @click="confirmWipe = false"
                                            class="px-2.5 py-1 rounded-md bg-muted text-secondary text-xs font-semibold hover:bg-border transition">
                                        {{ __('app.no') }}
                                    </button>
                                </div>
                            </template>

                            {{-- Delete member --}}
                            <template x-if="!confirmDelete && !confirmWipe">
                                <button type="button"
                                        @click="confirmDelete = true"
                                        class="px-3 py-1.5 rounded-lg bg-red-600/10 text-red-600 hover:bg-red-600/20 text-xs font-semibold transition border border-red-600/20">
                                    {{ __('app.delete') }}
                                </button>
                            </template>
                            <template x-if="confirmDelete">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-xs text-red-600 font-semibold">{{ __('app.sure') }}?</span>
                                    <form method="POST" action="{{ route('admin.members.destroy', $member) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-2.5 py-1 rounded-md bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition">
                                            {{ __('app.yes') }}
                                        </button>
                                    </form>
                                    <button type="button" @click="confirmDelete = false"
                                            class="px-2.5 py-1 rounded-md bg-muted text-secondary text-xs font-semibold hover:bg-border transition">
                                        {{ __('app.no') }}
                                    </button>
                                </div>
                            </template>
                            @php
                                $memberTelegramLink = $telegramMemberLinks[$member->id] ?? null;
                            @endphp
                            @if (is_string($memberTelegramLink))
                                <div class="w-full mt-2">
                                    <input id="member-telegram-link-{{ $member->id }}"
                                           type="text"
                                           class="w-64 max-w-full px-2 py-1.5 text-xs border border-border rounded-md bg-card text-secondary"
                                           value="{{ $memberTelegramLink }}"
                                           readonly>
                                    <button type="button"
                                            onclick="copyToClipboard('member-telegram-link-{{ $member->id }}')"
                                            class="px-2 py-1.5 rounded-md bg-surface border border-border text-xs">
                                        Copy
                                    </button>
                                    <a href="{{ $memberTelegramLink }}"
                                       target="_blank"
                                       class="px-2 py-1.5 rounded-md bg-accent text-on-accent text-xs">
                                        Open
                                    </a>
                                </div>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-12 text-center text-muted-text">
                        {{ __('app.no_members_yet') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($members->hasPages())
    <div class="px-4 py-3 border-t border-border">
        {{ $members->links() }}
    </div>
    @endif
</div>
@push('scripts')
<script>
    function copyToClipboard(inputId) {
        const input = document.getElementById(inputId);
        if (!input) {
            return;
        }
        navigator.clipboard.writeText(input.value).catch(() => {});
    }
</script>
@endpush
@endsection

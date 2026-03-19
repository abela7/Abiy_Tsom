@extends('layouts.admin')

@section('title', old('baptism_name', $member->baptism_name) . ' - Member Detail')

@section('content')
<div class="space-y-6">

    @if(session('success'))
        <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-medium text-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Back + Header --}}
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div>
            <a href="{{ route('admin.members.index') }}" class="inline-flex items-center gap-1 text-xs text-muted-text hover:text-primary transition mb-3">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('app.back_to_members') }}
            </a>
            <h1 class="text-2xl font-bold text-primary">{{ old('baptism_name', $member->baptism_name) }}</h1>
            <p class="text-sm text-muted-text mt-0.5">Member #{{ $member->id }} &middot; Registered {{ $member->created_at->format('d M Y, H:i') }} ({{ $member->created_at->diffForHumans() }})</p>
        </div>
        <div class="flex items-center gap-2">
            @php $latestSession = $sessions->first(); @endphp
            @if($latestSession && $latestSession->last_used_at && !$latestSession->revoked_at)
                @php $diffHours = $latestSession->last_used_at->diffInHours(now()); @endphp
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold {{ $diffHours < 1 ? 'bg-success/15 text-success' : ($diffHours < 24 ? 'bg-blue-500/15 text-blue-500' : ($diffHours < 72 ? 'bg-amber-500/15 text-amber-600' : 'bg-red-500/15 text-red-500')) }}">
                    Last active {{ $latestSession->last_used_at->diffForHumans() }}
                </span>
            @else
                <span class="px-3 py-1.5 rounded-lg text-xs font-bold bg-muted text-muted-text">Never active</span>
            @endif
        </div>
    </div>

    {{-- ───────── Profile Overview ───────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Member Info --}}
        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-border bg-muted/30">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile
                </h2>
            </div>
            <div class="p-5 space-y-3">
                <div class="py-2 border-b border-border/50">
                    <p class="text-xs text-muted-text font-medium mb-2">{{ __('app.baptism_name') }}</p>
                    <form method="post" action="{{ route('admin.members.update', $member) }}" class="flex flex-col sm:flex-row gap-2 sm:items-start">
                        @csrf
                        @method('PATCH')
                        <div class="flex-1 min-w-0">
                            <label for="admin_member_baptism_name" class="sr-only">{{ __('app.baptism_name') }}</label>
                            <input type="text"
                                   id="admin_member_baptism_name"
                                   name="baptism_name"
                                   value="{{ old('baptism_name', $member->baptism_name) }}"
                                   maxlength="255"
                                   required
                                   class="w-full px-3 py-2 rounded-lg border text-sm font-semibold text-primary bg-surface border-border focus:outline-none focus:ring-2 focus:ring-accent/30 focus:border-accent @error('baptism_name') border-red-500 @enderror"
                                   autocomplete="name">
                            @error('baptism_name')
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                            <p class="text-[11px] text-muted-text mt-1.5 leading-snug">{{ __('app.admin_member_baptism_name_hint') }}</p>
                        </div>
                        <button type="submit"
                                class="shrink-0 px-4 py-2 rounded-lg bg-accent text-on-accent text-sm font-bold hover:bg-accent-hover transition touch-manipulation">
                            {{ __('app.save') }}
                        </button>
                    </form>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Locale</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $member->locale === 'am' ? 'bg-green-500/10 text-green-600' : 'bg-blue-500/10 text-blue-500' }}">{{ $member->locale ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Theme</span>
                    <span class="text-sm text-secondary capitalize">{{ $member->theme ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Passcode</span>
                    @if($member->passcode_enabled)
                        <span class="px-2 py-0.5 rounded bg-success/10 text-success text-[10px] font-bold">Enabled</span>
                    @else
                        <span class="px-2 py-0.5 rounded bg-muted text-muted-text text-[10px] font-bold">Off</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Tour</span>
                    @if($member->tour_completed_at)
                        <span class="px-2 py-0.5 rounded bg-success/10 text-success text-[10px] font-bold">Done {{ $member->tour_completed_at->format('d M') }}</span>
                    @else
                        <span class="px-2 py-0.5 rounded bg-muted text-muted-text text-[10px] font-bold">Not done</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Referred by</span>
                    <span class="text-sm text-secondary">{{ $member->referrer?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5">
                    <span class="text-xs text-muted-text font-medium">Access Token</span>
                    <span class="text-[10px] text-muted-text/60 font-mono">{{ substr($member->token, 0, 8) }}...{{ substr($member->token, -4) }}</span>
                </div>
            </div>
        </div>

        {{-- WhatsApp Info --}}
        <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-border bg-muted/30">
                <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp
                </h2>
            </div>
            <div class="p-5 space-y-3">
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Status</span>
                    @if($member->whatsapp_confirmation_status === 'confirmed')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-success/10 text-success text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-success animate-pulse"></span> Active
                        </span>
                    @elseif($member->whatsapp_confirmation_status === 'pending')
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span> Pending
                        </span>
                    @elseif($member->whatsapp_confirmation_status === 'rejected')
                        <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-500 text-[10px] font-bold">Rejected</span>
                    @elseif($member->whatsapp_non_uk_requested)
                        <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 text-[10px] font-bold">Non-UK</span>
                    @else
                        <span class="px-2 py-0.5 rounded bg-muted text-muted-text text-[10px] font-bold">None</span>
                    @endif
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Phone</span>
                    <span class="text-sm font-mono text-primary">{{ $member->whatsapp_phone ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Reminder Enabled</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $member->whatsapp_reminder_enabled ? 'bg-success/10 text-success' : 'bg-muted text-muted-text' }}">
                        {{ $member->whatsapp_reminder_enabled ? 'Yes' : 'No' }}
                    </span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Reminder Time</span>
                    <span class="text-sm text-secondary">{{ $member->whatsapp_reminder_time ? \Carbon\Carbon::parse($member->whatsapp_reminder_time)->format('H:i') : '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Language</span>
                    <span class="text-sm text-secondary uppercase">{{ $member->whatsapp_language ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Last Sent</span>
                    <span class="text-sm text-secondary">{{ $member->whatsapp_last_sent_date ? $member->whatsapp_last_sent_date->format('d M Y') : '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Confirmation Requested</span>
                    <span class="text-xs text-secondary">{{ $member->whatsapp_confirmation_requested_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5 border-b border-border/50">
                    <span class="text-xs text-muted-text font-medium">Confirmation Responded</span>
                    <span class="text-xs text-secondary">{{ $member->whatsapp_confirmation_responded_at?->format('d M Y, H:i') ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center py-1.5">
                    <span class="text-xs text-muted-text font-medium">Non-UK Requested</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $member->whatsapp_non_uk_requested ? 'bg-amber-500/10 text-amber-600' : 'bg-muted text-muted-text' }}">
                        {{ $member->whatsapp_non_uk_requested ? 'Yes' : 'No' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Engagement Stats --}}
        <div class="space-y-4">
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-border bg-muted/30">
                    <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                        <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Engagement
                    </h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div class="text-center p-3 rounded-lg bg-muted/40">
                        <p class="text-xl font-black text-accent tabular-nums">{{ $totalDailyViews }}</p>
                        <p class="text-[10px] text-muted-text font-medium mt-1">Days Viewed</p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-muted/40">
                        <p class="text-xl font-black text-success tabular-nums">{{ $totalChecklists }}</p>
                        <p class="text-[10px] text-muted-text font-medium mt-1">Checklists Done</p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-muted/40">
                        <p class="text-xl font-black text-accent-secondary tabular-nums">{{ $totalCustomChecklists }}</p>
                        <p class="text-[10px] text-muted-text font-medium mt-1">Custom Done</p>
                    </div>
                    <div class="text-center p-3 rounded-lg bg-muted/40">
                        <p class="text-xl font-black text-primary tabular-nums">{{ $reminderOpens->sum('open_count') }}</p>
                        <p class="text-[10px] text-muted-text font-medium mt-1">Links Opened</p>
                    </div>
                </div>
            </div>

            {{-- Custom activities --}}
            @if($customActivities->isNotEmpty())
            <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-border bg-muted/30">
                    <h2 class="text-sm font-bold text-primary">Custom Activities</h2>
                </div>
                <div class="p-4 space-y-1">
                    @foreach($customActivities as $activity)
                        <div class="flex items-center gap-2 py-1.5 text-sm">
                            <span class="text-base">{{ $activity->icon ?? '?' }}</span>
                            <span class="text-secondary">{{ $activity->name }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ───────── Sessions ───────── --}}
    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-border bg-muted/30">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Sessions
                <span class="text-xs font-normal text-muted-text">({{ $sessions->count() }})</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/40 border-b border-border">
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Status</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">IP Address</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Device / Browser</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Created</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Last Used</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Expires</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($sessions as $session)
                    <tr class="hover:bg-muted/20 {{ $session->revoked_at ? 'opacity-50' : '' }}">
                        <td class="px-4 py-2.5">
                            @if($session->revoked_at)
                                <span class="px-2 py-0.5 rounded bg-red-500/10 text-red-500 text-[10px] font-bold">Revoked</span>
                            @elseif($session->expires_at && $session->expires_at->isPast())
                                <span class="px-2 py-0.5 rounded bg-muted text-muted-text text-[10px] font-bold">Expired</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-success/10 text-success text-[10px] font-bold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Active
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs font-mono text-secondary">{{ $session->ip_address ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-[11px] text-muted-text max-w-[300px] truncate" title="{{ $session->user_agent }}">
                            @if($session->user_agent)
                                @php
                                    $ua = $session->user_agent;
                                    $browser = 'Unknown';
                                    if (str_contains($ua, 'WhatsApp')) $browser = 'WhatsApp In-App';
                                    elseif (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) $browser = 'Chrome';
                                    elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
                                    elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
                                    elseif (str_contains($ua, 'Edg')) $browser = 'Edge';

                                    $os = 'Unknown';
                                    if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
                                    elseif (str_contains($ua, 'Android')) $os = 'Android';
                                    elseif (str_contains($ua, 'Windows')) $os = 'Windows';
                                    elseif (str_contains($ua, 'Mac OS')) $os = 'macOS';
                                    elseif (str_contains($ua, 'Linux')) $os = 'Linux';
                                @endphp
                                <span class="font-semibold text-secondary">{{ $browser }}</span>
                                <span class="text-muted-text/60">on {{ $os }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-muted-text whitespace-nowrap">{{ $session->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-4 py-2.5 text-xs whitespace-nowrap">
                            @if($session->last_used_at)
                                <span class="text-secondary font-medium">{{ $session->last_used_at->diffForHumans() }}</span>
                                <span class="text-muted-text/50 text-[10px] block">{{ $session->last_used_at->format('d M, H:i') }}</span>
                            @else
                                <span class="text-muted-text/40">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-muted-text whitespace-nowrap">{{ $session->expires_at?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted-text text-sm">No sessions</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ───────── Reminder Link Opens ───────── --}}
    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-border bg-muted/30">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Reminder Link Opens
                <span class="text-xs font-normal text-muted-text">({{ $reminderOpens->count() }})</span>
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/40 border-b border-border">
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Day</th>
                        <th class="text-right px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Total Opens</th>
                        <th class="text-right px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Authenticated</th>
                        <th class="text-right px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Public</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">First Opened</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Last Opened</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($reminderOpens as $open)
                    <tr class="hover:bg-muted/20">
                        <td class="px-4 py-2.5">
                            <span class="text-xs font-semibold text-primary">Day {{ $open->dailyContent?->day_number ?? '?' }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <span class="text-xs font-bold text-accent tabular-nums">{{ $open->open_count }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <span class="text-xs font-medium text-success tabular-nums">{{ $open->authenticated_open_count }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <span class="text-xs font-medium text-muted-text tabular-nums">{{ $open->public_open_count }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-muted-text whitespace-nowrap">{{ $open->first_opened_at?->format('d M, H:i') ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-xs text-secondary whitespace-nowrap font-medium">{{ $open->last_opened_at?->format('d M, H:i') ?? '—' }}</td>
                        <td class="px-4 py-2.5 text-[11px] text-muted-text font-mono">{{ $open->last_ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-muted-text text-sm">No reminder links opened</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ───────── Fundraising Responses ───────── --}}
    @if(is_countable($fundraisingResponses) && count($fundraisingResponses) > 0)
    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-border bg-muted/30">
            <h2 class="text-sm font-bold text-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-muted-text" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                Fundraising Responses
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-muted/40 border-b border-border">
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Campaign</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Status</th>
                        <th class="text-right px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Views</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Contact</th>
                        <th class="text-left px-4 py-2 text-[10px] font-bold text-muted-text uppercase tracking-wider">Interested At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @foreach($fundraisingResponses as $fr)
                    <tr class="hover:bg-muted/20">
                        <td class="px-4 py-2.5 text-xs font-semibold text-primary">{{ $fr->campaign?->title ?? 'Campaign #' . $fr->campaign_id }}</td>
                        <td class="px-4 py-2.5">
                            @if($fr->status === 'interested')
                                <span class="px-2 py-0.5 rounded bg-success/10 text-success text-[10px] font-bold">Interested</span>
                            @elseif($fr->status === 'snoozed')
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-600 text-[10px] font-bold">Snoozed</span>
                            @else
                                <span class="px-2 py-0.5 rounded bg-muted text-muted-text text-[10px] font-bold">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right text-xs font-bold text-accent tabular-nums">{{ $fr->view_count }}</td>
                        <td class="px-4 py-2.5 text-xs text-secondary">
                            @if($fr->contact_name || $fr->contact_phone)
                                {{ $fr->contact_name ?? '' }} {{ $fr->contact_phone ? '(' . $fr->contact_phone . ')' : '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-xs text-muted-text">{{ $fr->interested_at?->format('d M Y, H:i') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection

@extends('layouts.admin')

@section('title', 'Volunteer Invitation Stats')

@section('content')
@php
    $campaignUrl = route('volunteer.invite.show', $campaign->slug);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:items-start sm:justify-between sm:flex-row">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight truncate">{{ $campaign->name }}</h1>
                @if($campaign->is_active)
                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-accent/15 text-accent">{{ __('app.active') }}</span>
                @endif
            </div>
            <p class="text-sm text-muted-text mt-1.5">{{ __('app.slug') }}: {{ $campaign->slug }}</p>
            <p class="text-xs text-muted-text break-all">{{ __('app.campaign_url') }}: {{ $campaignUrl }}</p>
        </div>
        <div class="flex flex-col sm:flex-row flex-wrap gap-2">
            <a href="{{ route('admin.volunteer-invitations.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-semibold text-secondary hover:bg-muted transition">
                &larr; {{ __('app.back') }}
            </a>
            <a href="{{ $campaignUrl }}"
               target="_blank"
               rel="noopener"
               class="inline-flex items-center justify-center rounded-lg bg-accent text-on-accent px-4 py-2.5 text-sm font-semibold hover:bg-accent-hover transition">
                {{ __('app.open_live_page') }}
                <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5m6-5H7"/>
                </svg>
            </a>
        </div>
    </div>

    @php
        $total = $summary['total_invitations'];
        $funnelSteps = [
            ['label' => __('app.opened'), 'count' => $total, 'color' => 'text-primary'],
            ['label' => __('app.video_started'), 'count' => $summary['video_started_count'], 'color' => 'text-primary'],
            ['label' => __('app.video_completed'), 'count' => $summary['video_completed_count'], 'color' => 'text-accent'],
            ['label' => __('app.decision_made'), 'count' => $summary['decision_count'], 'color' => 'text-primary'],
            ['label' => __('app.willing'), 'count' => $summary['interested_count'], 'color' => 'text-green-600 dark:text-green-400'],
            ['label' => __('app.contact_submitted'), 'count' => $summary['contact_submitted_count'], 'color' => 'text-green-600 dark:text-green-400'],
        ];
        $bounceRate = $total > 0 ? round(($summary['bounced_count'] / $total) * 100) : 0;
        $shareRate = $total > 0 ? round(($summary['shared_count'] / $total) * 100) : 0;
    @endphp

    {{-- Funnel visualization --}}
    <div class="bg-card rounded-xl border border-border p-4 sm:p-6">
        <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold mb-4">{{ __('app.conversion_funnel') }}</h2>
        <div class="space-y-2">
            @foreach($funnelSteps as $i => $fStep)
                @php
                    $pct = $total > 0 ? round(($fStep['count'] / $total) * 100) : 0;
                    $dropoff = '';
                    if ($i > 0 && $funnelSteps[$i - 1]['count'] > 0) {
                        $prev = $funnelSteps[$i - 1]['count'];
                        $lost = $prev - $fStep['count'];
                        $dropPct = round(($lost / $prev) * 100);
                        if ($lost > 0) {
                            $dropoff = "-{$lost} ({$dropPct}% drop)";
                        }
                    }
                @endphp
                <div>
                    <div class="flex items-baseline justify-between gap-2 mb-1">
                        <span class="text-xs font-semibold text-secondary">{{ $fStep['label'] }}</span>
                        <div class="flex items-baseline gap-2">
                            @if($dropoff)
                                <span class="text-[10px] text-red-500 dark:text-red-400">{{ $dropoff }}</span>
                            @endif
                            <span class="text-sm font-black {{ $fStep['color'] }}">{{ $fStep['count'] }}</span>
                            <span class="text-[10px] text-muted-text">({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                        <div class="h-full rounded-full bg-accent/70 transition-all duration-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Invitations opened</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['total_invitations'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Video started</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['video_started_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Video completed</p>
            <p class="text-2xl font-black text-accent mt-2">{{ $summary['video_completed_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Video skipped</p>
            <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">{{ $summary['video_skipped_count'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">{{ __('app.decisions') }}</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['decision_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">{{ __('app.willing') }}</p>
            <p class="text-xl font-black text-green-600 dark:text-green-400 mt-2">{{ $summary['interested_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">No time</p>
            <p class="text-xl font-black text-amber-600 dark:text-amber-400 mt-2">{{ $summary['no_time_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Not interested</p>
            <p class="text-xl font-black text-red-600 dark:text-red-400 mt-2">{{ $summary['not_interested_count'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Contact details</p>
            <p class="text-xl font-black text-primary mt-2">{{ $summary['contact_submitted_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">{{ __('app.shared') }}</p>
            <p class="text-xl font-black text-primary mt-2">{{ $summary['shared_count'] }}</p>
            <p class="text-[10px] text-muted-text mt-1">{{ $shareRate }}% of visitors</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">{{ __('app.bounced') }}</p>
            <p class="text-xl font-black text-red-600 dark:text-red-400 mt-2">{{ $summary['bounced_count'] }}</p>
            <p class="text-[10px] text-muted-text mt-1">{{ $bounceRate }}% bounce rate</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">{{ __('app.engaged') }}</p>
            <p class="text-xl font-black text-green-600 dark:text-green-400 mt-2">{{ $total - $summary['bounced_count'] }}</p>
            <p class="text-[10px] text-muted-text mt-1">{{ $total > 0 ? 100 - $bounceRate : 0 }}% engagement</p>
        </div>
    </div>

    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-border flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold">Latest submissions</h2>
                <p class="text-xs text-muted-text">Grouped by IP to avoid duplicate clicks from the same address.</p>
                <p class="text-xs text-muted-text">Deleting a row removes all records for that visitor group.</p>
            </div>
            <p class="text-xs text-muted-text">{{ $submissions->count() }} grouped rows shown</p>
        </div>

        @if($submissions->isEmpty())
            <div class="p-10 text-center text-muted-text text-sm">
                No visitor submissions yet for this campaign.
            </div>
        @else
            <form id="latest-submissions-form"
                  method="POST"
                  action="{{ route('admin.volunteer-invitations.submissions.export', $campaign) }}">
                @csrf
                <div class="px-4 py-3 border-b border-border flex flex-wrap gap-2 sm:items-center justify-between">
                    <p class="text-xs text-muted-text" id="selected-submissions-count">0 selected</p>
                    <div class="flex flex-wrap gap-2 justify-end">
                        <button id="export-selected-button"
                                type="submit"
                                formaction="{{ route('admin.volunteer-invitations.submissions.export', $campaign) }}"
                                disabled
                                class="inline-flex items-center justify-center rounded-lg bg-accent text-on-accent px-3 py-2 text-xs font-semibold hover:bg-accent-hover transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Export selected as CSV
                        </button>
                        <button id="delete-selected-button"
                                type="submit"
                                formaction="{{ route('admin.volunteer-invitations.submissions.delete', $campaign) }}"
                                onclick="return confirm('Delete selected submission records?')"
                                disabled
                                class="inline-flex items-center justify-center rounded-lg bg-red-600 text-white px-3 py-2 text-xs font-semibold hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            Delete selected
                        </button>
                    </div>
                </div>

                {{-- Desktop submissions list --}}
                <div class="hidden md:block divide-y divide-border">
                    {{-- Header --}}
                    <div class="grid grid-cols-[auto_1fr_auto_1fr_1fr_auto_auto_auto_auto_auto] items-center bg-muted text-xs font-semibold text-secondary">
                        <div class="px-4 py-3"><input id="select-all-submissions" type="checkbox" class="rounded border-border text-accent"></div>
                        <div class="px-4 py-3">{{ __('app.visitor') }}</div>
                        <div class="px-4 py-3">{{ __('app.decision') }}</div>
                        <div class="px-4 py-3">{{ __('app.name') }}</div>
                        <div class="px-4 py-3">{{ __('app.phone') }}</div>
                        <div class="px-4 py-3">{{ __('app.method') }}</div>
                        <div class="px-4 py-3">{{ __('app.views') }}</div>
                        <div class="px-4 py-3">{{ __('app.touched') }}</div>
                        <div class="px-4 py-3">{{ __('app.created') }}</div>
                        <div class="px-4 py-3 w-10"></div>
                    </div>

                    @foreach($submissions as $submission)
                        @php
                            $visitorIp = $submission->ip_address ?: 'No IP';
                            $decisionLabel = match($submission->decision) {
                                \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'Willing',
                                \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'No time',
                                \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'Not interested',
                                default => 'No decision',
                            };
                            $methodLabel = match($submission->preferred_contact_method) {
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_PHONE => 'Phone',
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_TELEGRAM => 'Telegram',
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_WHATSAPP => 'WhatsApp',
                                default => '—',
                            };
                            $timeline = collect([
                                ['ts' => $submission->opened_at, 'label' => 'Opened page', 'color' => 'bg-blue-500'],
                                ['ts' => $submission->video_started_at, 'label' => 'Started video', 'color' => 'bg-primary'],
                                ['ts' => $submission->video_completed_at, 'label' => 'Completed video', 'color' => 'bg-accent'],
                                ['ts' => $submission->video_skipped_at, 'label' => 'Skipped video', 'color' => 'bg-amber-500'],
                                ['ts' => $submission->decision_at, 'label' => 'Decision: ' . $decisionLabel, 'color' => match($submission->decision) {
                                    \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'bg-green-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'bg-amber-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'bg-red-500',
                                    default => 'bg-gray-400',
                                }],
                                ['ts' => $submission->contact_submitted_at, 'label' => 'Submitted contact', 'color' => 'bg-green-500'],
                                ['ts' => $submission->shared_at, 'label' => 'Shared invitation', 'color' => 'bg-accent'],
                                ['ts' => $submission->last_activity_at, 'label' => 'Last activity', 'color' => 'bg-gray-400'],
                            ])->filter(fn ($e) => $e['ts'] !== null)->sortBy('ts')->values();
                        @endphp
                        <div x-data="{ open: false }">
                            {{-- Row --}}
                            <div class="grid grid-cols-[auto_1fr_auto_1fr_1fr_auto_auto_auto_auto_auto] items-center text-sm hover:bg-muted/40 transition cursor-pointer" @click="open = !open">
                                <div class="px-4 py-3" @click.stop>
                                    <input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}" class="submission-checkbox rounded border-border text-accent">
                                </div>
                                <div class="px-4 py-3 text-secondary min-w-0">
                                    <p class="font-medium truncate">{{ $visitorIp }}</p>
                                    @if(($submission->group_size ?? 0) > 1)
                                        <p class="text-xs text-muted-text">{{ $submission->group_size }} records</p>
                                    @endif
                                </div>
                                <div class="px-4 py-3">
                                    @if($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success whitespace-nowrap">{{ __('app.willing') }}</span>
                                    @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 whitespace-nowrap">{{ __('app.no_time') }}</span>
                                    @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300 whitespace-nowrap">{{ __('app.not_interested') }}</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-text whitespace-nowrap">{{ __('app.pending') }}</span>
                                    @endif
                                </div>
                                <div class="px-4 py-3 text-secondary truncate">{{ $submission->contact_name ?: '—' }}</div>
                                <div class="px-4 py-3 text-secondary truncate">{{ $submission->phone ?: '—' }}</div>
                                <div class="px-4 py-3 text-secondary text-xs">{{ $methodLabel }}</div>
                                <div class="px-4 py-3 text-secondary text-center">{{ $submission->open_count ?: 0 }}</div>
                                <div class="px-4 py-3 text-xs text-muted-text whitespace-nowrap">{{ $submission->decision_at ? $submission->decision_at->format('M d H:i') : '—' }}</div>
                                <div class="px-4 py-3 text-xs text-muted-text whitespace-nowrap">{{ $submission->created_at->format('M d H:i') }}</div>
                                <div class="px-4 py-3 flex justify-center">
                                    <svg class="w-4 h-4 text-muted-text transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </div>
                            </div>

                            {{-- Expandable detail panel --}}
                            <div x-show="open" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0"
                                 x-transition:leave-end="opacity-0 -translate-y-1"
                                 class="bg-muted/20 border-t border-dashed border-border">
                                <div class="px-6 py-5 max-w-3xl">
                                    <p class="text-xs font-bold uppercase tracking-wider text-muted-text mb-4">Activity timeline</p>
                                    @if($timeline->isEmpty())
                                        <p class="text-sm text-muted-text">No tracked activity — visitor bounced immediately.</p>
                                    @else
                                        <div class="space-y-0">
                                            @foreach($timeline as $event)
                                                <div class="flex items-start gap-3 relative">
                                                    {{-- Vertical line --}}
                                                    @if(!$loop->last)
                                                        <div class="absolute left-[9px] top-5 bottom-0 w-px bg-border"></div>
                                                    @endif
                                                    {{-- Dot --}}
                                                    <div class="relative z-10 mt-1 w-[18px] h-[18px] rounded-full {{ $event['color'] }}/20 flex items-center justify-center shrink-0">
                                                        <div class="w-2 h-2 rounded-full {{ $event['color'] }}"></div>
                                                    </div>
                                                    {{-- Content --}}
                                                    <div class="pb-4 min-w-0">
                                                        <p class="text-sm font-semibold text-secondary">{{ $event['label'] }}</p>
                                                        <p class="text-xs text-muted-text">{{ $event['ts']->format('M d, Y \a\t H:i:s') }}</p>
                                                        @if(!$loop->first && $timeline[$loop->index - 1]['ts'])
                                                            @php
                                                                $diff = $event['ts']->diffInSeconds($timeline[$loop->index - 1]['ts']);
                                                                if ($diff < 60) {
                                                                    $elapsed = $diff . ' seconds after previous';
                                                                } elseif ($diff < 3600) {
                                                                    $elapsed = round($diff / 60) . ' minutes after previous';
                                                                } else {
                                                                    $elapsed = round($diff / 3600, 1) . ' hours after previous';
                                                                }
                                                            @endphp
                                                            <p class="text-[11px] text-muted-text mt-0.5">{{ $elapsed }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Summary chips --}}
                                    <div class="mt-4 pt-4 border-t border-border flex flex-wrap gap-2">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-card border border-border text-xs text-secondary">
                                            <span class="text-muted-text">Views:</span>
                                            <span class="font-semibold">{{ $submission->open_count ?: 0 }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-card border border-border text-xs text-secondary">
                                            <span class="text-muted-text">Video:</span>
                                            <span class="font-semibold">
                                                @if($submission->video_completed_at) Watched
                                                @elseif($submission->video_skipped_at) Skipped
                                                @elseif($submission->video_started_at) Started
                                                @else Not played
                                                @endif
                                            </span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-card border border-border text-xs text-secondary">
                                            <span class="text-muted-text">Shared:</span>
                                            <span class="font-semibold">{{ $submission->shared_at ? 'Yes' : 'No' }}</span>
                                        </span>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-card border border-border text-xs text-secondary">
                                            <span class="text-muted-text">Status:</span>
                                            <span class="font-semibold">
                                                @if(!$submission->last_activity_at) Bounced
                                                @elseif($submission->contact_submitted_at) Converted
                                                @elseif($submission->decision) Decided
                                                @else Engaged
                                                @endif
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Mobile submissions list --}}
                <div class="md:hidden divide-y divide-border">
                    @foreach($submissions as $submission)
                        @php
                            $visitorIp = $submission->ip_address ?: 'No IP';
                            $decisionLabel = match($submission->decision) {
                                \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'Willing',
                                \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'No time',
                                \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'Not interested',
                                default => 'No decision',
                            };
                            $methodLabel = match($submission->preferred_contact_method) {
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_PHONE => 'Phone',
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_TELEGRAM => 'Telegram',
                                \App\Models\VolunteerInvitationSubmission::CONTACT_METHOD_WHATSAPP => 'WhatsApp',
                                default => '—',
                            };
                            $timeline = collect([
                                ['ts' => $submission->opened_at, 'label' => 'Opened page', 'color' => 'bg-blue-500'],
                                ['ts' => $submission->video_started_at, 'label' => 'Started video', 'color' => 'bg-primary'],
                                ['ts' => $submission->video_completed_at, 'label' => 'Completed video', 'color' => 'bg-accent'],
                                ['ts' => $submission->video_skipped_at, 'label' => 'Skipped video', 'color' => 'bg-amber-500'],
                                ['ts' => $submission->decision_at, 'label' => 'Decision: ' . $decisionLabel, 'color' => match($submission->decision) {
                                    \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'bg-green-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'bg-amber-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'bg-red-500',
                                    default => 'bg-gray-400',
                                }],
                                ['ts' => $submission->contact_submitted_at, 'label' => 'Submitted contact', 'color' => 'bg-green-500'],
                                ['ts' => $submission->shared_at, 'label' => 'Shared invitation', 'color' => 'bg-accent'],
                                ['ts' => $submission->last_activity_at, 'label' => 'Last activity', 'color' => 'bg-gray-400'],
                            ])->filter(fn ($e) => $e['ts'] !== null)->sortBy('ts')->values();
                        @endphp
                        <article class="p-4 space-y-2" x-data="{ open: false }">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}" class="submission-checkbox mt-1 rounded border-border text-accent">
                                <div class="min-w-0 space-y-2 w-full">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="text-sm font-semibold text-primary truncate">{{ $visitorIp }}</p>
                                            @if(($submission->group_size ?? 0) > 1)
                                                <p class="text-xs text-muted-text">{{ $submission->group_size }} records</p>
                                            @endif
                                        </div>
                                        <p class="text-xs text-muted-text">{{ $submission->created_at->format('M d H:i') }}</p>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            @if($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success">{{ __('app.willing') }}</span>
                                            @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">{{ __('app.no_time') }}</span>
                                            @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">{{ __('app.not_interested') }}</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-text">{{ __('app.pending') }}</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-muted-text">{{ $submission->open_count ?: 0 }} views</p>
                                    </div>
                                    @if($submission->contact_name || $submission->phone)
                                        <p class="text-sm text-secondary">
                                            {{ $submission->contact_name ?: '—' }} &middot; {{ $submission->phone ?: '—' }}
                                        </p>
                                    @endif

                                    <button type="button" @click.stop="open = !open"
                                            class="flex items-center gap-1.5 text-xs font-semibold text-accent hover:text-accent-hover transition">
                                        <span x-text="open ? 'Hide activity' : 'View activity'"></span>
                                        <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" x-cloak
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="mt-2 rounded-xl bg-muted/20 border border-border p-3">
                                        @if($timeline->isEmpty())
                                            <p class="text-xs text-muted-text">No tracked activity — bounced immediately.</p>
                                        @else
                                            <div class="space-y-0">
                                                @foreach($timeline as $event)
                                                    <div class="flex items-start gap-2.5 relative">
                                                        @if(!$loop->last)
                                                            <div class="absolute left-[7px] top-4 bottom-0 w-px bg-border"></div>
                                                        @endif
                                                        <div class="relative z-10 mt-0.5 w-[14px] h-[14px] rounded-full {{ $event['color'] }}/20 flex items-center justify-center shrink-0">
                                                            <div class="w-1.5 h-1.5 rounded-full {{ $event['color'] }}"></div>
                                                        </div>
                                                        <div class="pb-3 min-w-0">
                                                            <p class="text-xs font-semibold text-secondary">{{ $event['label'] }}</p>
                                                            <p class="text-[10px] text-muted-text">{{ $event['ts']->format('H:i:s') }}</p>
                                                            @if(!$loop->first && $timeline[$loop->index - 1]['ts'])
                                                                @php
                                                                    $diff = $event['ts']->diffInSeconds($timeline[$loop->index - 1]['ts']);
                                                                    if ($diff < 60) { $elapsed = $diff . 's later'; }
                                                                    elseif ($diff < 3600) { $elapsed = round($diff / 60) . 'min later'; }
                                                                    else { $elapsed = round($diff / 3600, 1) . 'h later'; }
                                                                @endphp
                                                                <p class="text-[10px] text-muted-text">{{ $elapsed }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="mt-2 pt-2 border-t border-border flex flex-wrap gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md bg-card border border-border text-[10px] text-secondary">
                                                Video: @if($submission->video_completed_at) Watched @elseif($submission->video_skipped_at) Skipped @elseif($submission->video_started_at) Started @else — @endif
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md bg-card border border-border text-[10px] text-secondary">
                                                Shared: {{ $submission->shared_at ? 'Yes' : 'No' }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md bg-card border border-border text-[10px] text-secondary">
                                                @if(!$submission->last_activity_at) Bounced
                                                @elseif($submission->contact_submitted_at) Converted
                                                @elseif($submission->decision) Decided
                                                @else Engaged
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('latest-submissions-form');
    if (!form) {
        return;
    }

    const selectAll = document.getElementById('select-all-submissions');
    const checkboxes = Array.from(form.querySelectorAll('.submission-checkbox'));
    const selectedLabel = document.getElementById('selected-submissions-count');

    const updateSelection = () => {
        const checkedCount = form.querySelectorAll('.submission-checkbox:checked').length;
        const totalCount = checkboxes.length;

        selectedLabel.textContent = `${checkedCount} selected`;
        const deleteButton = document.getElementById('delete-selected-button');
        const exportButton = document.getElementById('export-selected-button');

        deleteButton.disabled = checkedCount === 0;
        exportButton.disabled = checkedCount === 0;

        if (selectAll) {
            selectAll.checked = totalCount > 0 && checkedCount === totalCount;
            selectAll.indeterminate = checkedCount > 0 && checkedCount < totalCount;
        }
    };

    checkboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', updateSelection);
    });

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            const shouldSelectAll = selectAll.checked;
            checkboxes.forEach((checkbox) => {
                checkbox.checked = shouldSelectAll;
            });
            updateSelection();
        });
    }

    updateSelection();
});
</script>
@endpush
@endsection

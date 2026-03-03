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
                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-accent/15 text-accent">Active</span>
                @endif
            </div>
            <p class="text-sm text-muted-text mt-1.5">Slug: {{ $campaign->slug }}</p>
            <p class="text-xs text-muted-text break-all">Campaign URL: {{ $campaignUrl }}</p>
        </div>
        <div class="flex flex-col sm:flex-row flex-wrap gap-2">
            <a href="{{ route('admin.volunteer-invitations.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-border px-4 py-2.5 text-sm font-semibold text-secondary hover:bg-muted transition">
                &larr; Back
            </a>
            <a href="{{ $campaignUrl }}"
               target="_blank"
               rel="noopener"
               class="inline-flex items-center justify-center rounded-lg bg-accent text-on-accent px-4 py-2.5 text-sm font-semibold hover:bg-accent-hover transition">
                Open live page
                <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5m6-5H7"/>
                </svg>
            </a>
        </div>
    </div>

    @php
        $total = $summary['total_invitations'];
        $funnelSteps = [
            ['label' => 'Opened', 'count' => $total, 'color' => 'text-primary'],
            ['label' => 'Video started', 'count' => $summary['video_started_count'], 'color' => 'text-primary'],
            ['label' => 'Video completed', 'count' => $summary['video_completed_count'], 'color' => 'text-accent'],
            ['label' => 'Decision made', 'count' => $summary['decision_count'], 'color' => 'text-primary'],
            ['label' => 'Willing', 'count' => $summary['interested_count'], 'color' => 'text-green-600 dark:text-green-400'],
            ['label' => 'Contact submitted', 'count' => $summary['contact_submitted_count'], 'color' => 'text-green-600 dark:text-green-400'],
        ];
        $bounceRate = $total > 0 ? round(($summary['bounced_count'] / $total) * 100) : 0;
        $shareRate = $total > 0 ? round(($summary['shared_count'] / $total) * 100) : 0;
    @endphp

    {{-- Funnel visualization --}}
    <div class="bg-card rounded-xl border border-border p-4 sm:p-6">
        <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold mb-4">Conversion funnel</h2>
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
            <p class="text-xs uppercase tracking-wider text-muted-text">Decisions</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['decision_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Willing</p>
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
            <p class="text-xs uppercase tracking-wider text-muted-text">Shared</p>
            <p class="text-xl font-black text-primary mt-2">{{ $summary['shared_count'] }}</p>
            <p class="text-[10px] text-muted-text mt-1">{{ $shareRate }}% of visitors</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Bounced</p>
            <p class="text-xl font-black text-red-600 dark:text-red-400 mt-2">{{ $summary['bounced_count'] }}</p>
            <p class="text-[10px] text-muted-text mt-1">{{ $bounceRate }}% bounce rate</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Engaged</p>
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

                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-muted">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input id="select-all-submissions" type="checkbox" class="rounded border-border text-accent">
                                </th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Visitor</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Decision</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Name</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Phone</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Contact method</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Views</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Touched</th>
                                <th class="text-left px-4 py-3 font-semibold text-secondary">Created</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
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
                                        default => 'Not provided',
                                    };
                                    $timeline = collect([
                                        ['ts' => $submission->opened_at, 'label' => 'Opened page', 'icon' => 'eye', 'color' => 'text-blue-500'],
                                        ['ts' => $submission->video_started_at, 'label' => 'Started video', 'icon' => 'play', 'color' => 'text-primary'],
                                        ['ts' => $submission->video_completed_at, 'label' => 'Completed video', 'icon' => 'check', 'color' => 'text-accent'],
                                        ['ts' => $submission->video_skipped_at, 'label' => 'Skipped video', 'icon' => 'skip', 'color' => 'text-amber-500'],
                                        ['ts' => $submission->decision_at, 'label' => 'Made decision: ' . $decisionLabel, 'icon' => 'decision', 'color' => match($submission->decision) {
                                            \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'text-green-500',
                                            \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'text-amber-500',
                                            \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'text-red-500',
                                            default => 'text-muted-text',
                                        }],
                                        ['ts' => $submission->contact_submitted_at, 'label' => 'Submitted contact details', 'icon' => 'contact', 'color' => 'text-green-500'],
                                        ['ts' => $submission->shared_at, 'label' => 'Shared invitation', 'icon' => 'share', 'color' => 'text-accent'],
                                        ['ts' => $submission->last_activity_at, 'label' => 'Last activity', 'icon' => 'pulse', 'color' => 'text-muted-text'],
                                    ])->filter(fn ($e) => $e['ts'] !== null)->sortBy('ts')->values();
                                @endphp
                                <tr class="hover:bg-muted/40 transition" x-data="{ open: false }">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" name="submission_ids[]" value="{{ $submission->id }}" class="submission-checkbox rounded border-border text-accent">
                                    </td>
                                    <td class="px-4 py-3 text-secondary">
                                        <p class="font-medium">{{ $visitorIp }}</p>
                                        @if(($submission->group_size ?? 0) > 1)
                                            <p class="text-xs text-muted-text">Combined {{ $submission->group_size }} records</p>
                                        @endif
                                        <p class="text-xs text-muted-text">Token {{ Str::limit($submission->visitor_token, 14, '...') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-success-bg text-success">Willing</span>
                                        @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">No time</span>
                                        @elseif($submission->decision === \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">Not interested</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-muted text-muted-text">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-secondary">{{ $submission->contact_name ?: 'N/A' }}</td>
                                    <td class="px-4 py-3 text-secondary">{{ $submission->phone ?: 'N/A' }}</td>
                                    <td class="px-4 py-3 text-secondary">{{ $methodLabel }}</td>
                                    <td class="px-4 py-3 text-secondary">{{ $submission->open_count ?: 0 }}</td>
                                    <td class="px-4 py-3 text-xs text-muted-text">
                                        {{ $submission->decision_at ? $submission->decision_at->format('M d H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-xs text-muted-text">{{ $submission->created_at->format('M d H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <button type="button" @click.stop="open = !open" class="p-1.5 rounded-lg hover:bg-muted transition" title="View activity report">
                                            <svg class="w-4 h-4 text-muted-text transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                                <tr x-show="open" x-collapse x-cloak>
                                    <td colspan="10" class="p-0">
                                        <div class="bg-muted/30 border-t border-border px-6 py-4">
                                            <h3 class="text-xs font-bold uppercase tracking-wider text-muted-text mb-3">Visitor activity timeline</h3>
                                            @if($timeline->isEmpty())
                                                <p class="text-xs text-muted-text">No tracked activity (bounced immediately).</p>
                                            @else
                                                <div class="relative pl-5">
                                                    <div class="absolute left-[7px] top-1 bottom-1 w-px bg-border"></div>
                                                    @foreach($timeline as $event)
                                                        <div class="relative flex items-start gap-3 pb-3 last:pb-0">
                                                            <div class="absolute left-[-13px] top-0.5 w-2.5 h-2.5 rounded-full border-2 border-card {{ str_replace('text-', 'bg-', $event['color']) }}"></div>
                                                            <div class="flex-1 min-w-0">
                                                                <div class="flex items-baseline gap-2 flex-wrap">
                                                                    <span class="text-xs font-semibold {{ $event['color'] }}">{{ $event['label'] }}</span>
                                                                    <span class="text-[10px] text-muted-text">{{ $event['ts']->format('M d, Y \a\t H:i:s') }}</span>
                                                                </div>
                                                                @if(!$loop->first && $timeline[$loop->index - 1]['ts'])
                                                                    @php
                                                                        $diff = $event['ts']->diffInSeconds($timeline[$loop->index - 1]['ts']);
                                                                        if ($diff < 60) {
                                                                            $elapsed = $diff . 's later';
                                                                        } elseif ($diff < 3600) {
                                                                            $elapsed = round($diff / 60) . 'min later';
                                                                        } else {
                                                                            $elapsed = round($diff / 3600, 1) . 'h later';
                                                                        }
                                                                    @endphp
                                                                    <p class="text-[10px] text-muted-text">{{ $elapsed }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            <div class="mt-3 pt-3 border-t border-border grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px]">
                                                <div>
                                                    <p class="text-muted-text">Page opens</p>
                                                    <p class="font-semibold text-secondary">{{ $submission->open_count ?: 0 }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-muted-text">Video</p>
                                                    <p class="font-semibold text-secondary">
                                                        @if($submission->video_completed_at)
                                                            Watched
                                                        @elseif($submission->video_skipped_at)
                                                            Skipped
                                                        @elseif($submission->video_started_at)
                                                            Started only
                                                        @else
                                                            Not played
                                                        @endif
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-muted-text">Shared</p>
                                                    <p class="font-semibold text-secondary">{{ $submission->shared_at ? 'Yes' : 'No' }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-muted-text">Status</p>
                                                    <p class="font-semibold text-secondary">
                                                        @if(!$submission->last_activity_at)
                                                            Bounced
                                                        @elseif($submission->contact_submitted_at)
                                                            Converted
                                                        @elseif($submission->decision)
                                                            Decided
                                                        @else
                                                            Engaged
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
                                default => 'Not provided',
                            };
                            $timeline = collect([
                                ['ts' => $submission->opened_at, 'label' => 'Opened page', 'color' => 'text-blue-500'],
                                ['ts' => $submission->video_started_at, 'label' => 'Started video', 'color' => 'text-primary'],
                                ['ts' => $submission->video_completed_at, 'label' => 'Completed video', 'color' => 'text-accent'],
                                ['ts' => $submission->video_skipped_at, 'label' => 'Skipped video', 'color' => 'text-amber-500'],
                                ['ts' => $submission->decision_at, 'label' => 'Made decision: ' . $decisionLabel, 'color' => match($submission->decision) {
                                    \App\Models\VolunteerInvitationSubmission::DECISION_INTERESTED => 'text-green-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NO_TIME => 'text-amber-500',
                                    \App\Models\VolunteerInvitationSubmission::DECISION_NOT_INTERESTED => 'text-red-500',
                                    default => 'text-muted-text',
                                }],
                                ['ts' => $submission->contact_submitted_at, 'label' => 'Submitted contact details', 'color' => 'text-green-500'],
                                ['ts' => $submission->shared_at, 'label' => 'Shared invitation', 'color' => 'text-accent'],
                                ['ts' => $submission->last_activity_at, 'label' => 'Last activity', 'color' => 'text-muted-text'],
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
                                                <p class="text-xs text-muted-text">Combined {{ $submission->group_size }} records</p>
                                            @endif
                                            <p class="text-xs text-muted-text">Token {{ Str::limit($submission->visitor_token, 20, '...') }}</p>
                                        </div>
                                        <p class="text-xs text-muted-text">{{ $submission->created_at->format('M d H:i') }}</p>
                                    </div>
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-secondary">{{ $decisionLabel }}</p>
                                        <p class="text-xs text-muted-text">Opened {{ $submission->open_count ?: 0 }}</p>
                                    </div>
                                    <p class="text-sm text-secondary">
                                        {{ $submission->contact_name ?: 'Name not provided' }} - {{ $submission->phone ?: 'Phone not provided' }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="inline-flex px-2 py-1 rounded-full bg-muted text-muted-text">Method: {{ $methodLabel }}</span>
                                        <span class="inline-flex px-2 py-1 rounded-full bg-muted text-muted-text">
                                            Decision time: {{ $submission->decision_at ? $submission->decision_at->format('M d H:i') : 'N/A' }}
                                        </span>
                                    </div>
                                    @if($submission->contact_submitted_at)
                                        <p class="text-xs text-muted-text">Contact provided at {{ $submission->contact_submitted_at->format('M d, H:i') }}</p>
                                    @endif

                                    <button type="button" @click.stop="open = !open" class="flex items-center gap-1.5 text-xs font-semibold text-accent hover:text-accent-hover transition mt-1">
                                        <span x-text="open ? 'Hide activity' : 'View activity'"></span>
                                        <svg class="w-3.5 h-3.5 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="open" x-collapse x-cloak class="mt-2 rounded-xl bg-muted/30 border border-border p-3">
                                        <h4 class="text-[10px] font-bold uppercase tracking-wider text-muted-text mb-2">Activity timeline</h4>
                                        @if($timeline->isEmpty())
                                            <p class="text-xs text-muted-text">No tracked activity (bounced immediately).</p>
                                        @else
                                            <div class="relative pl-4">
                                                <div class="absolute left-[5px] top-1 bottom-1 w-px bg-border"></div>
                                                @foreach($timeline as $event)
                                                    <div class="relative flex items-start gap-2 pb-2.5 last:pb-0">
                                                        <div class="absolute left-[-11px] top-0.5 w-2 h-2 rounded-full border-2 border-card {{ str_replace('text-', 'bg-', $event['color']) }}"></div>
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                                                <span class="text-[11px] font-semibold {{ $event['color'] }}">{{ $event['label'] }}</span>
                                                                <span class="text-[10px] text-muted-text">{{ $event['ts']->format('H:i:s') }}</span>
                                                            </div>
                                                            @if(!$loop->first && $timeline[$loop->index - 1]['ts'])
                                                                @php
                                                                    $diff = $event['ts']->diffInSeconds($timeline[$loop->index - 1]['ts']);
                                                                    if ($diff < 60) {
                                                                        $elapsed = $diff . 's later';
                                                                    } elseif ($diff < 3600) {
                                                                        $elapsed = round($diff / 60) . 'min later';
                                                                    } else {
                                                                        $elapsed = round($diff / 3600, 1) . 'h later';
                                                                    }
                                                                @endphp
                                                                <p class="text-[10px] text-muted-text">{{ $elapsed }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                        <div class="mt-2 pt-2 border-t border-border grid grid-cols-2 gap-2 text-[10px]">
                                            <div>
                                                <p class="text-muted-text">Video</p>
                                                <p class="font-semibold text-secondary">
                                                    @if($submission->video_completed_at) Watched
                                                    @elseif($submission->video_skipped_at) Skipped
                                                    @elseif($submission->video_started_at) Started only
                                                    @else Not played
                                                    @endif
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-muted-text">Shared</p>
                                                <p class="font-semibold text-secondary">{{ $submission->shared_at ? 'Yes' : 'No' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-muted-text">Status</p>
                                                <p class="font-semibold text-secondary">
                                                    @if(!$submission->last_activity_at) Bounced
                                                    @elseif($submission->contact_submitted_at) Converted
                                                    @elseif($submission->decision) Decided
                                                    @else Engaged
                                                    @endif
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-muted-text">Page opens</p>
                                                <p class="font-semibold text-secondary">{{ $submission->open_count ?: 0 }}</p>
                                            </div>
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

@extends('layouts.admin')

@section('title', 'Volunteer Invitation Stats')

@section('content')
@php
    $campaignUrl = route('volunteer.invite.show', $campaign->slug);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:items-center sm:justify-between sm:flex-row">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl sm:text-3xl font-black text-primary tracking-tight truncate">{{ $campaign->name }}</h1>
                @if($campaign->is_active)
                    <span class="text-[10px] font-bold uppercase px-2 py-1 rounded-full bg-accent/15 text-accent">Active</span>
                @endif
            </div>
            <p class="text-sm text-muted-text mt-1.5">Slug: {{ $campaign->slug }}</p>
            <p class="text-xs text-muted-text">Campaign URL: {{ $campaignUrl }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
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

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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
            <p class="text-xs uppercase tracking-wider text-muted-text">Decisions</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['decision_count'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Willing</p>
            <p class="text-xl font-black text-green-600 dark:text-green-400 mt-2">{{ $summary['interested_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">No Time</p>
            <p class="text-xl font-black text-amber-600 dark:text-amber-400 mt-2">{{ $summary['no_time_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Not interested</p>
            <p class="text-xl font-black text-red-600 dark:text-red-400 mt-2">{{ $summary['not_interested_count'] }}</p>
        </div>
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Contact details</p>
            <p class="text-xl font-black text-primary mt-2">{{ $summary['contact_submitted_count'] }}</p>
        </div>
    </div>

    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-border flex items-center justify-between flex-wrap gap-3">
            <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold">Latest submissions</h2>
            <p class="text-xs text-muted-text">{{ $submissions->count() }} latest rows shown</p>
        </div>

        @if($submissions->isEmpty())
            <div class="p-10 text-center text-muted-text text-sm">
                No visitor submissions yet for this campaign.
            </div>
        @else
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Visitor</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Decision</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Name</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Phone</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Contact method</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Touched</th>
                            <th class="text-left px-4 py-3 font-semibold text-secondary">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @foreach($submissions as $submission)
                            @php
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
                            @endphp
                            <tr class="hover:bg-muted/40 transition">
                                <td class="px-4 py-3 text-secondary tabular-nums">{{ Str::limit($submission->visitor_token, 12, '...') }}</td>
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
                                <td class="px-4 py-3 text-xs text-muted-text">
                                    {{ $submission->decision_at ? $submission->decision_at->format('M d H:i') : 'N/A' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-text">{{ $submission->created_at->format('M d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="md:hidden divide-y divide-border">
                @foreach($submissions as $submission)
                    @php
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
                    @endphp
                    <div class="p-4">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-primary truncate">{{ $decisionLabel }}</p>
                            <p class="text-xs text-muted-text">{{ $submission->created_at->format('M d H:i') }}</p>
                        </div>
                        <p class="text-xs text-muted-text mt-1">Visitor {{ Str::limit($submission->visitor_token, 18, '...') }}</p>
                        <div class="mt-2 text-sm text-secondary">
                            {{ $submission->contact_name ?: 'Name not provided' }} - {{ $submission->phone ?: 'Phone not provided' }}
                        </div>
                        <p class="text-xs text-muted-text mt-1">Method: {{ $methodLabel }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

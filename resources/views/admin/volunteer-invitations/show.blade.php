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
            <p class="text-xs uppercase tracking-wider text-muted-text">Decisions</p>
            <p class="text-2xl font-black text-primary mt-2">{{ $summary['decision_count'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
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
        <div class="bg-card rounded-xl p-4 border border-border">
            <p class="text-xs uppercase tracking-wider text-muted-text">Contact details</p>
            <p class="text-xl font-black text-primary mt-2">{{ $summary['contact_submitted_count'] }}</p>
        </div>
    </div>

    <div class="bg-card rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-border flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm uppercase tracking-wider text-muted-text font-bold">Latest submissions</h2>
                <p class="text-xs text-muted-text">Grouped by IP to avoid duplicate clicks from the same address.</p>
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
                    <button id="export-selected-button"
                            type="submit"
                            disabled
                            class="inline-flex items-center justify-center rounded-lg bg-accent text-on-accent px-3 py-2 text-xs font-semibold hover:bg-accent-hover transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Export selected as CSV
                    </button>
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
                                @endphp
                                <tr class="hover:bg-muted/40 transition">
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
                        @endphp
                        <article class="p-4 space-y-2">
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
    const submitButton = document.getElementById('export-selected-button');

    const updateSelection = () => {
        const checkedCount = form.querySelectorAll('.submission-checkbox:checked').length;
        const totalCount = checkboxes.length;

        selectedLabel.textContent = `${checkedCount} selected`;
        submitButton.disabled = checkedCount === 0;

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

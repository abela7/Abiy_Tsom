<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerInvitationCampaign;
use App\Models\VolunteerInvitationSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VolunteerInviteController extends Controller
{
    /**
     * List campaigns + high-level totals for admin dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $campaigns = VolunteerInvitationCampaign::query()
            ->withCount([
                'submissions',
                'submissions as video_started_count' => fn ($query) => $query->whereNotNull('video_started_at'),
                'submissions as video_completed_count' => fn ($query) => $query->whereNotNull('video_completed_at'),
                'submissions as decision_count' => fn ($query) => $query->whereNotNull('decision'),
                'submissions as interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_INTERESTED),
                'submissions as no_time_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NO_TIME),
                'submissions as not_interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NOT_INTERESTED),
                'submissions as contact_submitted_count' => fn ($query) => $query->whereNotNull('contact_submitted_at'),
            ])
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        if ($request->boolean('with_raw')) {
            return response()->json($campaigns);
        }

        return response()->json([
            'campaigns' => $campaigns,
            'summary' => [
                'total_campaigns'        => $campaigns->count(),
                'total_invitations'      => $campaigns->sum('submissions_count'),
                'video_started'          => $campaigns->sum('video_started_count'),
                'video_completed'        => $campaigns->sum('video_completed_count'),
                'decisions_made'         => $campaigns->sum('decision_count'),
                'willing'                => $campaigns->sum('interested_count'),
                'no_time'                => $campaigns->sum('no_time_count'),
                'not_interested'         => $campaigns->sum('not_interested_count'),
                'contacts_collected'     => $campaigns->sum('contact_submitted_count'),
            ],
        ]);
    }

    /**
     * Create a campaign.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'slug'       => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('volunteer_invitation_campaigns', 'slug')],
            'youtube_url'=> ['nullable', 'url', 'max:500'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_active']) && $validated['is_active']) {
            VolunteerInvitationCampaign::query()->update(['is_active' => false]);
        }

        $campaign = VolunteerInvitationCampaign::create([
            'name'       => $validated['name'],
            'slug'       => $validated['slug'],
            'youtube_url'=> $validated['youtube_url'] ?? null,
            'is_active'  => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json($campaign, 201);
    }

    /**
     * Update campaign metadata, including YouTube URL.
     */
    public function update(Request $request, VolunteerInvitationCampaign $campaign): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['nullable', 'string', 'max:150'],
            'slug'        => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('volunteer_invitation_campaigns', 'slug')->ignore($campaign->id)],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data = array_filter($validated, fn ($value) => $value !== null);
        if (! empty($data)) {
            if (array_key_exists('is_active', $data) && $data['is_active']) {
                VolunteerInvitationCampaign::query()->update(['is_active' => false]);
            }

            $campaign->update($data);
        }

        return response()->json($campaign->fresh());
    }

    /**
     * Delete a campaign and all visitor submissions (careful action).
     */
    public function destroy(VolunteerInvitationCampaign $campaign): JsonResponse
    {
        $id = $campaign->id;
        $campaign->delete();

        return response()->json(['message' => 'campaign_deleted', 'id' => $id]);
    }

    /**
     * Set selected campaign active (deactivate all others).
     */
    public function activate(VolunteerInvitationCampaign $campaign): JsonResponse
    {
        VolunteerInvitationCampaign::query()->update(['is_active' => false]);
        $campaign->update(['is_active' => true]);

        return response()->json([
            'message' => 'campaign_activated',
            'slug' => $campaign->slug,
        ]);
    }

    /**
     * Show detailed campaign stats and latest submissions.
     */
    public function stats(VolunteerInvitationCampaign $campaign): JsonResponse
    {
        $campaign->loadCount([
            'submissions',
            'submissions as video_started_count' => fn ($query) => $query->whereNotNull('video_started_at'),
            'submissions as video_completed_count' => fn ($query) => $query->whereNotNull('video_completed_at'),
            'submissions as decision_count' => fn ($query) => $query->whereNotNull('decision'),
            'submissions as interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_INTERESTED),
            'submissions as no_time_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NO_TIME),
            'submissions as not_interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NOT_INTERESTED),
            'submissions as contact_submitted_count' => fn ($query) => $query->whereNotNull('contact_submitted_at'),
        ]);

        $recentSubmissions = $campaign->submissions()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'id',
                'visitor_token',
                'decision',
                'phone',
                'preferred_contact_method',
                'created_at',
            ]);

        return response()->json([
            'campaign' => $campaign,
            'summary' => [
                'total_invitations'     => $campaign->submissions_count,
                'video_started_count'   => $campaign->video_started_count,
                'video_completed_count' => $campaign->video_completed_count,
                'decision_count'        => $campaign->decision_count,
                'interested_count'      => $campaign->interested_count,
                'no_time_count'         => $campaign->no_time_count,
                'not_interested_count'  => $campaign->not_interested_count,
                'contact_submitted_count' => $campaign->contact_submitted_count,
            ],
            'submissions' => $recentSubmissions,
        ]);
    }
}

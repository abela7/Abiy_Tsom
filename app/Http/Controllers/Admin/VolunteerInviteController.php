<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VolunteerInvitationCampaign;
use App\Models\VolunteerInvitationSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VolunteerInviteController extends Controller
{
    /**
     * List campaigns + metrics dashboard (HTML) and raw API JSON (when requested as API).
     */
    public function index(Request $request)
    {
        $campaigns = $this->buildCampaignQuery()
            ->orderByDesc('is_active')
            ->orderByDesc('created_at')
            ->get();

        $summary = $this->buildSummary($campaigns);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'campaigns' => $campaigns,
                'summary'   => $summary,
            ]);
        }

        return view('admin.volunteer-invitations.index', [
            'campaigns' => $campaigns,
            'summary'   => $summary,
        ]);
    }

    /**
     * Create a campaign.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:150'],
            'slug'       => ['required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('volunteer_invitation_campaigns', 'slug')],
            'seo_title'  => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'youtube_url'=> ['nullable', 'url', 'max:500'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_active'])) {
            VolunteerInvitationCampaign::query()->update(['is_active' => false]);
        }

        $campaign = VolunteerInvitationCampaign::create([
            'name'           => $validated['name'],
            'slug'           => $validated['slug'],
            'seo_title'      => trim((string) ($validated['seo_title'] ?? '')) ?: null,
            'seo_description'=> trim((string) ($validated['seo_description'] ?? '')) ?: null,
            'youtube_url'    => $validated['youtube_url'],
            'is_active'      => (bool) ($validated['is_active'] ?? false),
        ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($campaign, 201);
        }

        return redirect()->route('admin.volunteer-invitations.index')
            ->with('success', "Campaign \"{$campaign->name}\" has been created.");
    }

    /**
     * Update campaign metadata, including YouTube URL.
     */
    public function update(Request $request, VolunteerInvitationCampaign $campaign): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['nullable', 'string', 'max:150'],
            'slug'            => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/', Rule::unique('volunteer_invitation_campaigns', 'slug')->ignore($campaign->id)],
            'seo_title'       => ['nullable', 'string', 'max:160'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'youtube_url' => ['nullable', 'url', 'max:500'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data = array_filter($validated, fn ($value) => $value !== null);
        if (array_key_exists('is_active', $data) && $data['is_active']) {
            VolunteerInvitationCampaign::query()->update(['is_active' => false]);
        }
        if (array_key_exists('seo_title', $data)) {
            $data['seo_title'] = trim((string) $data['seo_title']) ?: null;
        }
        if (array_key_exists('seo_description', $data)) {
            $data['seo_description'] = trim((string) $data['seo_description']) ?: null;
        }

        if (! empty($data)) {
            $campaign->update($data);
        }

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json($campaign->fresh());
        }

        return redirect()->route('admin.volunteer-invitations.index')
            ->with('success', "Campaign \"{$campaign->name}\" has been updated.");
        ;
    }

    /**
     * Delete a campaign and all associated submissions.
     */
    public function destroy(Request $request, VolunteerInvitationCampaign $campaign): JsonResponse|RedirectResponse
    {
        $name = $campaign->name;
        $campaign->delete();

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['message' => 'campaign_deleted', 'slug' => $campaign->slug]);
        }

        return redirect()->route('admin.volunteer-invitations.index')
            ->with('success', "Campaign \"{$name}\" has been removed.");
    }

    /**
     * Set selected campaign active (deactivate all others).
     */
    public function activate(Request $request, VolunteerInvitationCampaign $campaign): JsonResponse|RedirectResponse
    {
        VolunteerInvitationCampaign::query()->update(['is_active' => false]);
        $campaign->update(['is_active' => true]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'message' => 'campaign_activated',
                'slug'    => $campaign->slug,
            ]);
        }

        return redirect()->route('admin.volunteer-invitations.index')
            ->with('success', "Campaign \"{$campaign->name}\" is now active.");
    }

    /**
     * Show detailed campaign stats and latest submissions.
     */
    public function stats(Request $request, VolunteerInvitationCampaign $campaign)
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

        $summary = [
            'total_invitations'      => $campaign->submissions_count,
            'video_started_count'    => $campaign->video_started_count,
            'video_completed_count'  => $campaign->video_completed_count,
            'decision_count'         => $campaign->decision_count,
            'interested_count'       => $campaign->interested_count,
            'no_time_count'          => $campaign->no_time_count,
            'not_interested_count'   => $campaign->not_interested_count,
            'contact_submitted_count' => $campaign->contact_submitted_count,
        ];

        $recentSubmissions = $campaign->submissions()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get([
                'id',
                'visitor_token',
                'decision',
                'contact_name',
                'phone',
                'preferred_contact_method',
                'decision_at',
                'contact_submitted_at',
                'opened_at',
                'video_completed_at',
                'created_at',
            ]);

        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'campaign' => $campaign,
                'summary' => $summary,
                'submissions' => $recentSubmissions,
            ]);
        }

        return view('admin.volunteer-invitations.show', [
            'campaign' => $campaign,
            'summary' => $summary,
            'submissions' => $recentSubmissions,
        ]);
    }

    private function buildCampaignQuery()
    {
        return VolunteerInvitationCampaign::query()->withCount([
            'submissions',
            'submissions as video_started_count' => fn ($query) => $query->whereNotNull('video_started_at'),
            'submissions as video_completed_count' => fn ($query) => $query->whereNotNull('video_completed_at'),
            'submissions as decision_count' => fn ($query) => $query->whereNotNull('decision'),
            'submissions as interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_INTERESTED),
            'submissions as no_time_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NO_TIME),
            'submissions as not_interested_count' => fn ($query) => $query->where('decision', VolunteerInvitationSubmission::DECISION_NOT_INTERESTED),
            'submissions as contact_submitted_count' => fn ($query) => $query->whereNotNull('contact_submitted_at'),
        ]);
    }

    private function buildSummary($campaigns): array
    {
        return [
            'total_campaigns'       => $campaigns->count(),
            'total_invitations'     => $campaigns->sum('submissions_count'),
            'video_started'         => $campaigns->sum('video_started_count'),
            'video_completed'       => $campaigns->sum('video_completed_count'),
            'decisions_made'        => $campaigns->sum('decision_count'),
            'willing'               => $campaigns->sum('interested_count'),
            'no_time'               => $campaigns->sum('no_time_count'),
            'not_interested'        => $campaigns->sum('not_interested_count'),
            'contacts_collected'    => $campaigns->sum('contact_submitted_count'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\VolunteerInvitationCampaign;
use App\Models\VolunteerInvitationSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VolunteerInviteController extends Controller
{
    private const COOKIE_NAME = 'vt_invite_token';
    private const COOKIE_MINUTES = 60 * 24 * 30;

    /**
     * Public landing endpoint for one invite campaign.
     */
    public function show(string $slug, Request $request)
    {
        $campaign = VolunteerInvitationCampaign::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();

        if (! $campaign) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Campaign not found or inactive'], 404);
            }

            abort(404);
        }

        $submission = $this->resolveSubmission($request, $campaign);

        if ($request->wantsJson()) {
            return $this->withInviteCookie($submission, response()->json([
                'campaign' => [
                    'id'              => $campaign->id,
                    'name'            => $campaign->name,
                    'slug'            => $campaign->slug,
                    'seo_title'       => $campaign->seo_title,
                    'seo_description' => $campaign->seo_description,
                    'youtube_url'     => $campaign->youtube_url,
                ],
                'submission' => [
                    'id'       => $submission->id,
                    'visitor'  => $submission->visitor_token,
                    'decision' => $submission->decision,
                ],
            ]));
        }

        return $this->withInviteCookie(
            $submission,
            response()->view('public.invite.show', [
                'campaign'    => $campaign,
                'submission'  => $submission,
                'slug'        => $campaign->slug,
                'campaignUrl' => route('volunteer.invite.show', $campaign->slug),
            ])
        );
    }

    /**
     * Track video progress from frontend.
     */
    public function track(string $slug, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event' => ['required', Rule::in(['video_started', 'video_completed'])],
        ]);

        $campaign = $this->resolveCampaign($slug);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found or inactive'], 404);
        }

        $submission = $this->resolveSubmission($request, $campaign);

        if ($validated['event'] === 'video_started' && ! $submission->video_started_at) {
            $submission->update(['video_started_at' => now()]);
        }

        if ($validated['event'] === 'video_completed' && ! $submission->video_completed_at) {
            $submission->update(['video_completed_at' => now()]);
        }

        return $this->withInviteCookie($submission, response()->json([
            'message' => 'tracked',
            'event'   => $validated['event'],
        ]));
    }

    /**
     * Capture user decision from confirmation page.
     */
    public function decision(string $slug, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                VolunteerInvitationSubmission::DECISION_INTERESTED,
                VolunteerInvitationSubmission::DECISION_NO_TIME,
                VolunteerInvitationSubmission::DECISION_NOT_INTERESTED,
            ])],
        ]);

        $campaign = $this->resolveCampaign($slug);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found or inactive'], 404);
        }

        $submission = $this->resolveSubmission($request, $campaign);
        $submission->update([
            'decision'    => $validated['decision'],
            'decision_at' => $submission->decision_at ?? now(),
        ]);

        return $this->withInviteCookie($submission, response()->json([
            'message'  => 'decision_saved',
            'decision' => $submission->decision,
        ]));
    }

    /**
     * Store contact details for users who confirmed willingness.
     */
    public function contact(string $slug, Request $request): JsonResponse
    {
        $campaign = $this->resolveCampaign($slug);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found or inactive'], 404);
        }

        $submission = $this->resolveSubmission($request, $campaign);
        if ($submission->decision !== VolunteerInvitationSubmission::DECISION_INTERESTED) {
            return response()->json(['message' => 'Contact data can only be submitted for interested users'], 422);
        }

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'max:150'],
            'phone'       => ['required', 'string', 'max:40'],
            'contact_method' => [
                'required',
                Rule::in([
                    VolunteerInvitationSubmission::CONTACT_METHOD_WHATSAPP,
                    VolunteerInvitationSubmission::CONTACT_METHOD_PHONE,
                    VolunteerInvitationSubmission::CONTACT_METHOD_TELEGRAM,
                ]),
            ],
        ]);

        $submission->update([
            'contact_name'             => $validated['contact_name'],
            'phone'                    => $validated['phone'],
            'preferred_contact_method' => $validated['contact_method'],
            'contact_submitted_at'     => $submission->contact_submitted_at ?? now(),
        ]);

        return $this->withInviteCookie($submission, response()->json([
            'message'   => 'contact_saved',
            'submitted' => true,
        ]));
    }

    private function resolveCampaign(string $slug): ?VolunteerInvitationCampaign
    {
        return VolunteerInvitationCampaign::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }

    private function resolveSubmission(Request $request, VolunteerInvitationCampaign $campaign): VolunteerInvitationSubmission
    {
        $token = $this->resolveVisitorToken($request);
        $ipAddress = $request->ip();
        $userAgent = $this->safeSlice($request->userAgent());
        $referer = $this->safeSlice($request->header('referer'), 1024);

        $submission = VolunteerInvitationSubmission::query()
            ->where('volunteer_invitation_campaign_id', $campaign->id)
            ->where('visitor_token', $token)
            ->first();

        if (! $submission) {
            if ($ipAddress !== null) {
                $submission = VolunteerInvitationSubmission::query()
                    ->where('volunteer_invitation_campaign_id', $campaign->id)
                    ->where('ip_address', $ipAddress)
                    ->orderByDesc('created_at')
                    ->first();
            }
        }

        if (! $submission) {
            return VolunteerInvitationSubmission::create([
                'volunteer_invitation_campaign_id' => $campaign->id,
                'visitor_token'                   => $token,
                'ip_address'                      => $ipAddress,
                'user_agent'                      => $userAgent,
                'referer'                         => $referer,
                'opened_at'                       => now(),
                'open_count'                      => 1,
            ]);
        }

        $submission->update([
            'visitor_token' => $token,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer'    => $this->safeSlice($request->header('referer'), 1024),
            'open_count' => ((int) $submission->open_count) + 1,
        ]);

        if (! $submission->opened_at) {
            $submission->opened_at = now();
            $submission->save();
        }

        return $submission;
    }

    private function resolveVisitorToken(Request $request): string
    {
        $token = $request->cookie(self::COOKIE_NAME);
        if (is_string($token) && preg_match('/^[A-Fa-f0-9]{64}$/', $token) === 1) {
            return $token;
        }

        return bin2hex(random_bytes(32));
    }

    private function withInviteCookie(VolunteerInvitationSubmission $submission, Response|JsonResponse $response): Response|JsonResponse
    {
        try {
            $token = $submission->visitor_token;
            $isSecure = request()->isSecure();
            $response->withCookie(cookie(self::COOKIE_NAME, $token, self::COOKIE_MINUTES, '/', null, $isSecure, true, false, 'lax'));
        } catch (\Throwable $e) {
            Log::warning('Invite cookie could not be set', ['error' => $e->getMessage()]);
        }

        return $response;
    }

    private function safeSlice(mixed $value, int $length = 512): ?string
    {
        if ($value === null) {
            return null;
        }

        if (strlen((string) $value) <= $length) {
            return (string) $value;
        }

        return substr((string) $value, 0, $length);
    }
}

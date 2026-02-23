<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Models\MemberFundraisingResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles the fundraising popup interactions for members.
 */
class FundraisingController extends Controller
{
    /**
     * Returns the active campaign data if the popup should be shown to this member.
     */
    public function popup(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');
        $campaign = FundraisingCampaign::active();

        if (! $campaign) {
            return response()->json(['show' => false]);
        }

        $response = MemberFundraisingResponse::where('member_id', $member->id)
            ->where('campaign_id', $campaign->id)
            ->first();

        $show = $response === null || $response->shouldShowPopup();

        if (! $show) {
            return response()->json(['show' => false]);
        }

        return response()->json([
            'show'        => true,
            'campaign_id' => $campaign->id,
            'title'       => $campaign->title,
            'description' => $campaign->description,
            'embed_url'   => $campaign->youtubeEmbedUrl(),
            'donate_url'  => $campaign->donate_url,
        ]);
    }

    /**
     * Member clicked "Not Today" — snooze the popup until tomorrow.
     */
    public function snooze(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');

        $validated = $request->validate([
            'campaign_id' => ['required', 'integer', 'exists:fundraising_campaigns,id'],
        ]);

        MemberFundraisingResponse::updateOrCreate(
            [
                'member_id'   => $member->id,
                'campaign_id' => (int) $validated['campaign_id'],
            ],
            [
                'status'            => 'snoozed',
                'last_snoozed_date' => Carbon::today(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Member expressed interest — save contact details and mark as interested.
     */
    public function interested(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');

        $validated = $request->validate([
            'campaign_id'   => ['required', 'integer', 'exists:fundraising_campaigns,id'],
            'contact_name'  => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:50'],
        ]);

        $campaign = FundraisingCampaign::find((int) $validated['campaign_id']);

        MemberFundraisingResponse::updateOrCreate(
            [
                'member_id'   => $member->id,
                'campaign_id' => (int) $validated['campaign_id'],
            ],
            [
                'status'        => 'interested',
                'contact_name'  => trim($validated['contact_name']),
                'contact_phone' => trim($validated['contact_phone']),
                'interested_at' => now(),
            ]
        );

        return response()->json([
            'success'    => true,
            'donate_url' => $campaign?->donate_url ?? 'https://donate.abuneteklehaymanot.org/',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundraisingCampaign;
use App\Models\MemberFundraisingResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin management for the member fundraising popup campaign.
 */
class FundraisingController extends Controller
{
    public function index(): View
    {
        $campaign = FundraisingCampaign::latest()->first();

        $stats = null;
        if ($campaign) {
            $stats = [
                'interested' => MemberFundraisingResponse::where('campaign_id', $campaign->id)
                    ->where('status', 'interested')
                    ->count(),
                'snoozed' => MemberFundraisingResponse::where('campaign_id', $campaign->id)
                    ->where('status', 'snoozed')
                    ->count(),
                'leads' => MemberFundraisingResponse::where('campaign_id', $campaign->id)
                    ->where('status', 'interested')
                    ->whereNotNull('contact_name')
                    ->get(['contact_name', 'contact_phone', 'interested_at']),
            ];
        }

        return view('admin.fundraising.index', compact('campaign', 'stats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'title_am'       => ['nullable', 'string', 'max:255'],
            'description'    => ['nullable', 'string', 'max:2000'],
            'description_am' => ['nullable', 'string', 'max:2000'],
            'youtube_url'    => ['nullable', 'url', 'max:500'],
            'donate_url'     => ['nullable', 'url', 'max:500'],
            'is_active'      => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        // If activating, deactivate all others first (single active campaign)
        if ($data['is_active']) {
            FundraisingCampaign::where('is_active', true)->update(['is_active' => false]);
        }

        $campaign = FundraisingCampaign::latest()->first();

        if ($campaign) {
            $campaign->update($data);
        } else {
            FundraisingCampaign::create($data);
        }

        return redirect('/admin/fundraising')->with('success', 'Fundraising campaign saved successfully.');
    }

    /**
     * Delete all member responses so every member sees the popup again.
     */
    public function resetResponses(): RedirectResponse
    {
        $campaign = FundraisingCampaign::latest()->first();

        if ($campaign) {
            $deleted = MemberFundraisingResponse::where('campaign_id', $campaign->id)->delete();

            return redirect('/admin/fundraising')
                ->with('success', "All responses have been reset ({$deleted} cleared). Every member will see the popup again.");
        }

        return redirect('/admin/fundraising');
    }
}

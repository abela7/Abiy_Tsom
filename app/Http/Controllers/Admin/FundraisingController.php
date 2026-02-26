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
            $allResponses = MemberFundraisingResponse::where('campaign_id', $campaign->id)
                ->with('member:id,baptism_name')
                ->orderByDesc('view_count')
                ->orderByDesc('updated_at')
                ->get();

            $stats = [
                'interested' => $allResponses->where('status', 'interested')->count(),
                'snoozed'    => $allResponses->where('status', 'snoozed')->count(),
                'viewed'     => $allResponses->whereNull('status')->count(),
                'responses'  => $allResponses,
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
     * Delete a single member response so that member sees the popup again.
     */
    public function deleteResponse(int $id): RedirectResponse
    {
        MemberFundraisingResponse::where('id', $id)->delete();

        return redirect('/admin/fundraising')
            ->with('success', __('app.fundraising_response_cleared'));
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

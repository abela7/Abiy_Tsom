<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin tour data management — view stats and clear/reset tour completion.
 */
class TourController extends Controller
{
    public function index(): View
    {
        $totalMembers = Member::count();
        $tourCompletedCount = Member::whereNotNull('tour_completed_at')->count();
        $tourNotCompletedCount = $totalMembers - $tourCompletedCount;

        $members = Member::orderByDesc('tour_completed_at')
            ->orderBy('baptism_name')
            ->get(['id', 'baptism_name', 'tour_completed_at', 'created_at']);

        return view('admin.tour.index', compact(
            'totalMembers',
            'tourCompletedCount',
            'tourNotCompletedCount',
            'members'
        ));
    }

    /**
     * Reset tour for a single member — they will see it again on next home visit.
     */
    public function resetMember(Member $member): RedirectResponse
    {
        $member->update(['tour_completed_at' => null]);

        return redirect()->route('admin.tour.index')
            ->with('success', __('app.tour_restarted_for_member', ['name' => $member->baptism_name]));
    }

    /**
     * Clear all tour data — reset tour_completed_at for every member.
     * All members will see the tour again on their next home visit.
     */
    public function clearAll(): RedirectResponse
    {
        $count = Member::whereNotNull('tour_completed_at')->update(['tour_completed_at' => null]);

        return redirect()->route('admin.tour.index')
            ->with('success', __('app.tour_data_cleared', ['count' => $count]));
    }
}

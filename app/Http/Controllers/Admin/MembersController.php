<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Member analytics, listing, and management for admins.
 */
class MembersController extends Controller
{
    /**
     * Show member stats and paginated member list.
     */
    public function index(): View
    {
        $totalMembers = Member::count();

        $registrationsByDay = Member::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $firstRegistration = $registrationsByDay->first()?->date;
        $lastRegistration = $registrationsByDay->last()?->date;

        $last7Days = Member::where('created_at', '>=', now()->subDays(7))->count();
        $last30Days = Member::where('created_at', '>=', now()->subDays(30))->count();

        $localeDistribution = Member::query()
            ->selectRaw('locale, COUNT(*) as count')
            ->groupBy('locale')
            ->orderByDesc('count')
            ->get();

        $themeDistribution = Member::query()
            ->selectRaw('theme, COUNT(*) as count')
            ->groupBy('theme')
            ->orderByDesc('count')
            ->get();

        $passcodeEnabled = Member::where('passcode_enabled', true)->count();

        $totalChecklistCompletions = MemberChecklist::where('completed', true)->count();
        $totalCustomCompletions = MemberCustomChecklist::where('completed', true)->count();
        $engagedMembers = Member::whereHas('checklists', fn ($q) => $q->where('completed', true))
            ->orWhereHas('customChecklists', fn ($q) => $q->where('completed', true))
            ->count();

        $members = Member::orderByDesc('created_at')->paginate(25);

        return view('admin.members.index', compact(
            'totalMembers',
            'registrationsByDay',
            'firstRegistration',
            'lastRegistration',
            'last7Days',
            'last30Days',
            'localeDistribution',
            'themeDistribution',
            'passcodeEnabled',
            'totalChecklistCompletions',
            'totalCustomCompletions',
            'engagedMembers',
            'members'
        ));
    }

    /**
     * Delete a single member and all their associated data.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $member->checklists()->delete();
        $member->customChecklists()->delete();
        $member->customActivities()->delete();
        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.member_deleted'));
    }

    /**
     * Wipe all activity/checklist data for a member but keep their account.
     */
    public function wipeData(Member $member): RedirectResponse
    {
        $member->checklists()->delete();
        $member->customChecklists()->delete();
        $member->customActivities()->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.member_data_wiped'));
    }

    /**
     * Delete every member and all their data (nuclear option).
     */
    public function wipeAll(): RedirectResponse
    {
        MemberChecklist::truncate();
        MemberCustomChecklist::truncate();
        Member::query()->each(function (Member $m): void {
            $m->customActivities()->delete();
        });
        Member::truncate();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.all_members_wiped'));
    }
}

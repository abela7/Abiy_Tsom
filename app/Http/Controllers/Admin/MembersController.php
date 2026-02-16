<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Illuminate\View\View;

/**
 * Anonymous member analytics — no PII, aggregate stats only.
 */
class MembersController extends Controller
{
    /**
     * Show anonymous user tracking stats.
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
            'engagedMembers'
        ));
    }
}

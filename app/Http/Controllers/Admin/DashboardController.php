<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use Illuminate\View\View;

/**
 * Admin dashboard overview.
 */
class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with key stats.
     */
    public function index(): View
    {
        $season = LentSeason::active();
        $totalMembers = Member::count();
        $publishedDays = $season ? DailyContent::where('lent_season_id', $season->id)->where('is_published', true)->count() : 0;
        $totalDays = $season?->total_days ?? 55;

        return view('admin.dashboard', compact('season', 'totalMembers', 'publishedDays', 'totalDays'));
    }
}

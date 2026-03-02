<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use App\Models\MemberSession;
use Illuminate\Support\Facades\DB;
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

        // New registrations
        $newToday = Member::whereDate('created_at', today())->count();
        $new7d = Member::where('created_at', '>=', now()->subDays(7))->count();
        $new30d = Member::where('created_at', '>=', now()->subDays(30))->count();

        // Active users (distinct members with non-revoked sessions active recently)
        $active24h = MemberSession::where('last_used_at', '>=', now()->subHours(24))
            ->whereNull('revoked_at')
            ->distinct('member_id')
            ->count('member_id');
        $active7d = MemberSession::where('last_used_at', '>=', now()->subDays(7))
            ->whereNull('revoked_at')
            ->distinct('member_id')
            ->count('member_id');
        $active30d = MemberSession::where('last_used_at', '>=', now()->subDays(30))
            ->whereNull('revoked_at')
            ->distinct('member_id')
            ->count('member_id');

        // Engagement
        $engagedMembers = Member::whereHas('checklists', fn ($q) => $q->where('completed', true))
            ->orWhereHas('customChecklists', fn ($q) => $q->where('completed', true))
            ->count();
        $totalCompletions = MemberChecklist::where('completed', true)->count()
            + MemberCustomChecklist::where('completed', true)->count();

        // Connections
        $telegramConnected = Member::whereNotNull('telegram_chat_id')->count();
        $whatsappConnected = Member::where('whatsapp_reminder_enabled', true)->count();

        // Referrals
        $totalReferredMembers = Member::whereNotNull('referred_by')->count();

        // Registration trend (last 14 days)
        $registrationTrend = Member::query()
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        // Fill missing days with 0
        $trendData = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $trendData[$date] = $registrationTrend[$date] ?? 0;
        }

        // Distributions
        $localeDistribution = Member::query()
            ->selectRaw("locale, COUNT(*) as count")
            ->groupBy('locale')
            ->pluck('count', 'locale');
        $themeDistribution = Member::query()
            ->selectRaw("theme, COUNT(*) as count")
            ->groupBy('theme')
            ->pluck('count', 'theme');

        return view('admin.dashboard', compact(
            'season', 'totalMembers', 'publishedDays', 'totalDays',
            'newToday', 'new7d', 'new30d',
            'active24h', 'active7d', 'active30d',
            'engagedMembers', 'totalCompletions',
            'telegramConnected', 'whatsappConnected', 'totalReferredMembers',
            'trendData',
            'localeDistribution', 'themeDistribution'
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use App\Services\AbiyTsomStructure;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Shows member progress graphs and improvement suggestions.
 */
class ProgressController extends Controller
{
    /**
     * Progress dashboard with charts.
     */
    public function index(Request $request): View
    {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();
        $dayToken = $member ? '?token=' . $member->token : '';

        return view('member.progress', compact('member', 'season', 'dayToken'));
    }

    /**
     * API endpoint — returns chart data for the progress graphs.
     *
     * Accepts ?period=daily|weekly|monthly|all (default: all).
     */
    public function data(Request $request): JsonResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('member');

        $season = LentSeason::active();
        if (! $season) {
            return response()->json([
                'success' => false,
                'message' => 'No active season.',
            ], 404);
        }

        $period = $request->query('period', 'daily');
        if (! in_array($period, ['daily', 'weekly', 'monthly', 'all'], true)) {
            $period = 'daily';
        }

        $dayParam = $request->query('day');
        $weekParam = $request->query('week');

        $allDays = DailyContent::where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->orderBy('day_number')
            ->get();

        $today = Carbon::today();

        // Find today's content to determine current week
        $todayContent = $allDays->first(
            fn (DailyContent $d) => $d->date && $d->date->isSameDay($today)
        );

        $periodDays = $this->filterByPeriod($allDays, $period, $todayContent, $dayParam, $weekParam);

        $activities = Activity::where('lent_season_id', $season->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $customActivities = $member->customActivities()
            ->orderBy('sort_order')
            ->get();

        $totalActivities = $activities->count() + $customActivities->count();

        // Fetch all completed checks for the season (once)
        $allDayIds = $allDays->pluck('id');
        $allChecks = MemberChecklist::where('member_id', $member->id)
            ->whereIn('daily_content_id', $allDayIds)
            ->where('completed', true)
            ->get();

        $allCustomChecks = MemberCustomChecklist::where('member_id', $member->id)
            ->whereIn('daily_content_id', $allDayIds)
            ->where('completed', true)
            ->get();

        // Period-scoped checks
        $periodDayIds = $periodDays->pluck('id');
        $checks = $allChecks->whereIn('daily_content_id', $periodDayIds);
        $customChecks = $allCustomChecks->whereIn('daily_content_id', $periodDayIds);

        // Daily completion rates for the period
        $dailyRates = [];
        foreach ($periodDays as $day) {
            $doneCount = $checks->where('daily_content_id', $day->id)->count()
                + $customChecks->where('daily_content_id', $day->id)->count();
            $rate = $totalActivities > 0
                ? (int) round(($doneCount / $totalActivities) * 100)
                : 0;
            $dailyRates[] = [
                'day' => $day->day_number,
                'date' => $day->date->locale('en')->translatedFormat('M j'),
                'rate' => $rate,
            ];
        }

        // Per-activity rates for the period
        $periodDayCount = $periodDays->count();
        $activityRates = [];

        foreach ($activities as $activity) {
            $doneCount = $checks->where('activity_id', $activity->id)->count();
            $rate = $periodDayCount > 0
                ? (int) round(($doneCount / $periodDayCount) * 100)
                : 0;
            $activityRates[] = [
                'key' => 'a-' . $activity->id,
                'name' => $activity->name,
                'rate' => $rate,
            ];
        }

        foreach ($customActivities as $ca) {
            $doneCount = $customChecks
                ->where('member_custom_activity_id', $ca->id)
                ->count();
            $rate = $periodDayCount > 0
                ? (int) round(($doneCount / $periodDayCount) * 100)
                : 0;
            $activityRates[] = [
                'key' => 'c-' . $ca->id,
                'name' => $ca->name,
                'rate' => $rate,
            ];
        }

        // Overall %
        $totalChecks = $checks->count() + $customChecks->count();
        $overall = ($periodDayCount > 0 && $totalActivities > 0)
            ? (int) round(($totalChecks / ($periodDayCount * $totalActivities)) * 100)
            : 0;

        // Suggestions: bottom 3 activities
        $suggestions = collect($activityRates)
            ->sortBy('rate')
            ->take(3)
            ->values()
            ->toArray();

        // Streak: consecutive days from today backwards with >= 1 completion
        $streak = $this->computeStreak(
            $allDays,
            $allChecks,
            $allCustomChecks,
            $today
        );

        // Best / worst day in the period
        $bestDay = null;
        $worstDay = null;
        if (count($dailyRates) > 0) {
            $sorted = collect($dailyRates)->sortByDesc('rate')->values();
            $bestDay = $sorted->first();
            $worstDay = $sorted->last();
        }

        // Heatmap: all 55 days with rate (always computed so
        // frontend can show/hide based on period tab)
        $heatmap = [];
        foreach ($allDays as $day) {
            $doneCount = $allChecks->where('daily_content_id', $day->id)->count()
                + $allCustomChecks->where('daily_content_id', $day->id)->count();
            $rate = $totalActivities > 0
                ? (int) round(($doneCount / $totalActivities) * 100)
                : 0;
            $heatmap[] = [
                'day' => $day->day_number,
                'rate' => $rate,
            ];
        }

        // Optional: link to day content when viewing a single day
        $viewDayContentId = null;
        if ($period === 'daily' && $periodDays->count() === 1) {
            $viewDayContentId = $periodDays->first()?->id;
        }

        // Day picker options (1-55 with dates)
        $startDate = Carbon::parse($season->start_date);
        $dayOptions = [];
        for ($d = 1; $d <= 55; $d++) {
            $date = $startDate->copy()->addDays($d - 1);
            $content = $allDays->firstWhere('day_number', $d);
            $dayOptions[] = [
                'day' => $d,
                'date' => $date->locale('en')->translatedFormat('M j'),
                'label' => "Day {$d} · " . $date->locale('en')->translatedFormat('M j'),
                'has_content' => (bool) $content,
            ];
        }

        // Week picker options (1-8)
        $locale = app()->getLocale();
        $weekNameAttr = $locale === 'am' ? 'name_am' : 'name_en';
        $weekOptions = [];
        foreach (AbiyTsomStructure::getWeeks() as $wn => $info) {
            $name = $info[$weekNameAttr] ?? $info['name_geez'] ?? $info['name_en'];
            $weekOptions[] = [
                'week' => $wn,
                'name' => $name,
                'day_start' => $info['day_start'],
                'day_end' => $info['day_end'],
                'label' => "Week {$wn} · {$name} (Days {$info['day_start']}-{$info['day_end']})",
            ];
        }

        return response()->json([
            'success' => true,
            'period' => $period,
            'overall' => $overall,
            'daily_rates' => $dailyRates,
            'activity_rates' => $activityRates,
            'suggestions' => $suggestions,
            'streak' => $streak,
            'best_day' => $bestDay,
            'worst_day' => $worstDay,
            'heatmap' => $heatmap,
            'day_options' => $dayOptions,
            'week_options' => $weekOptions,
            'view_day_content_id' => $viewDayContentId,
        ]);
    }

    /**
     * Filter days by the requested period.
     *
     * @param  Collection<int, DailyContent>  $allDays
     * @param  int|null  $dayParam  Specific day number (1-55) when period=daily
     * @param  int|null  $weekParam  Specific week number (1-8) when period=weekly
     * @return Collection<int, DailyContent>
     */
    private function filterByPeriod(
        Collection $allDays,
        string $period,
        ?DailyContent $todayContent,
        ?string $dayParam = null,
        ?string $weekParam = null
    ): Collection {
        $today = Carbon::today();

        return match ($period) {
            'daily' => $this->filterDaily($allDays, $today, $todayContent, $dayParam),
            'weekly' => $this->filterWeekly($allDays, $todayContent, $weekParam),
            'monthly' => $this->getMonthlyDays($allDays, $todayContent),
            default => $allDays,
        };
    }

    /**
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function filterDaily(Collection $allDays, Carbon $today, ?DailyContent $todayContent, ?string $dayParam): Collection
    {
        if ($dayParam !== null && $dayParam !== '') {
            $dayNum = (int) $dayParam;
            if ($dayNum >= 1 && $dayNum <= 55) {
                return $allDays->where('day_number', $dayNum)->values();
            }
        }

        return $allDays->filter(
            fn (DailyContent $d) => $d->date && $d->date->isSameDay($today)
        )->values();
    }

    /**
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function filterWeekly(Collection $allDays, ?DailyContent $todayContent, ?string $weekParam): Collection
    {
        if ($weekParam !== null && $weekParam !== '') {
            $weekNum = (int) $weekParam;
            if ($weekNum >= 1 && $weekNum <= 8) {
                [$start, $end] = AbiyTsomStructure::getDayRangeForWeek($weekNum);

                return $allDays->whereBetween('day_number', [$start, $end])->values();
            }
        }

        if ($todayContent) {
            return $allDays->where('weekly_theme_id', $todayContent->weekly_theme_id)->values();
        }

        return collect();
    }

    /**
     * Get days for the current 4-week block (weeks 1-4 or 5-8).
     *
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function getMonthlyDays(
        Collection $allDays,
        ?DailyContent $todayContent
    ): Collection {
        if (! $todayContent || ! $todayContent->weekly_theme_id) {
            return $allDays;
        }

        $currentWeekTheme = $todayContent->weeklyTheme;
        if (! $currentWeekTheme) {
            return $allDays;
        }

        $weekNum = $currentWeekTheme->week_number;
        // Block 1 = weeks 1-4, Block 2 = weeks 5-8
        $blockStart = $weekNum <= 4 ? 1 : 5;
        $blockEnd = $weekNum <= 4 ? 4 : 8;

        // Get theme IDs in this block
        $themeIds = $allDays
            ->load('weeklyTheme')
            ->pluck('weeklyTheme')
            ->filter()
            ->unique('id')
            ->filter(fn ($t) => $t->week_number >= $blockStart && $t->week_number <= $blockEnd)
            ->pluck('id');

        return $allDays
            ->whereIn('weekly_theme_id', $themeIds)
            ->values();
    }

    /**
     * Count consecutive days with >= 1 completed activity,
     * walking backwards from today.
     */
    private function computeStreak(
        Collection $allDays,
        Collection $checks,
        Collection $customChecks,
        Carbon $today
    ): int {
        // Sort days by date descending, only up to today
        $pastDays = $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->lte($today))
            ->sortByDesc('day_number')
            ->values();

        $streak = 0;

        foreach ($pastDays as $day) {
            $done = $checks->where('daily_content_id', $day->id)->count()
                + $customChecks->where('daily_content_id', $day->id)->count();

            if ($done > 0) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }
}

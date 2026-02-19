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
     * API endpoint returns chart data for the progress graphs.
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

        $referenceDay = $this->getReferenceDay($allDays, $today);

        $periodDays = $this->filterByPeriod($allDays, $period, $referenceDay, $dayParam, $weekParam);

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

        $memberLocale = in_array($member->locale, ['en', 'am'], true)
            ? $member->locale
            : app()->getLocale();

        foreach ($activities as $activity) {
            $doneCount = $checks->where('activity_id', $activity->id)->count();
            $rate = $periodDayCount > 0
                ? (int) round(($doneCount / $periodDayCount) * 100)
                : 0;
            $activityRates[] = [
                'key' => 'a-' . $activity->id,
                'name' => localized($activity, 'name', $memberLocale) ?? $activity->name,
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

        // Suggestions: bottom 3 activities that are not already complete.
        $suggestions = collect($activityRates)
            ->filter(fn (array $a) => (int) $a['rate'] < 100)
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

        // Heatmap: all published days with rate (always computed so
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

        // Day picker options from available published days
        $dayOptions = [];
        foreach ($allDays as $dayItem) {
            $dayDate = $dayItem->date?->locale('en')->translatedFormat('M j');
            $dayOptions[] = [
                'day' => $dayItem->day_number,
                'date' => $dayDate ?? '',
                'label' => $dayDate !== null
                    ? ('Day ' . $dayItem->day_number . ' (' . $dayDate . ')')
                    : ('Day ' . $dayItem->day_number),
                'has_content' => true,
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
                'label' => "Week {$wn} - {$name} (Days {$info['day_start']}-{$info['day_end']})",
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
     * @param  int|null  $dayParam  Specific day number when period=daily
     * @param  int|null  $weekParam  Specific week number (1-8) when period=weekly
     * @return Collection<int, DailyContent>
     */
    private function filterByPeriod(
        Collection $allDays,
        string $period,
        ?DailyContent $referenceDay,
        ?string $dayParam = null,
        ?string $weekParam = null
    ): Collection {
        return match ($period) {
            'daily' => $this->filterDaily($allDays, $referenceDay, $dayParam),
            'weekly' => $this->filterWeekly($allDays, $referenceDay, $weekParam),
            'monthly' => $this->getMonthlyDays($allDays, $referenceDay),
            default => $allDays,
        };
    }

    /**
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function filterDaily(Collection $allDays, ?DailyContent $referenceDay, ?string $dayParam): Collection
    {
        $maxDay = $allDays->max('day_number');
        $minDay = $allDays->min('day_number');

        if ($dayParam !== null && $dayParam !== '') {
            $dayNum = (int) $dayParam;
            if ($minDay !== null && $maxDay !== null && $dayNum >= (int) $minDay && $dayNum <= (int) $maxDay) {
                return $allDays->where('day_number', $dayNum)->values();
            }
        }

        if (! $referenceDay) {
            return $allDays->take(0);
        }

        return $allDays->filter(
            fn (DailyContent $d) => $d->day_number === $referenceDay->day_number
        )->values();
    }

    /**
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function filterWeekly(Collection $allDays, ?DailyContent $referenceDay, ?string $weekParam): Collection
    {
        if ($weekParam !== null && $weekParam !== '') {
            $weekNum = (int) $weekParam;
            if ($weekNum >= 1 && $weekNum <= 8) {
                [$start, $end] = AbiyTsomStructure::getDayRangeForWeek($weekNum);

                return $allDays->whereBetween('day_number', [$start, $end])->values();
            }
        }

        if ($referenceDay) {
            return $allDays->where('weekly_theme_id', $referenceDay->weekly_theme_id)->values();
        }

        if ($allDays->isEmpty()) {
            return collect();
        }

        $firstDayNumber = $allDays->min('day_number');
        $lastDayNumber = $allDays->max('day_number');
        if ($firstDayNumber === null || $lastDayNumber === null) {
            return collect();
        }

        return $allDays->filter(
            fn (DailyContent $d) => $d->day_number >= $firstDayNumber && $d->day_number <= min($firstDayNumber + 6, $lastDayNumber)
        )->values();
    }

    /**
     * Get days for the month of the reference day.
     *
     * @param  Collection<int, DailyContent>  $allDays
     * @return Collection<int, DailyContent>
     */
    private function getMonthlyDays(
        Collection $allDays,
        ?DailyContent $referenceDay
    ): Collection {
        if ($allDays->isEmpty()) {
            return collect();
        }

        $monthDate = $referenceDay?->date ?? $allDays->sortBy('date')->first()?->date;
        if (! $monthDate) {
            return collect();
        }

        $monthItems = $allDays->filter(
            fn (DailyContent $d) => $d->date && $d->date->isSameMonth($monthDate)
        )->values();

        if ($monthItems->isNotEmpty()) {
            return $monthItems;
        }

        return $allDays->take(0);
    }

    /**
     * Best available reference day for period defaults.
     */
    private function getReferenceDay(Collection $allDays, Carbon $anchorDate): ?DailyContent
    {
        if ($allDays->isEmpty()) {
            return null;
        }

        $todayContent = $allDays->first(
            fn (DailyContent $d) => $d->date && $d->date->isSameDay($anchorDate)
        );
        if ($todayContent) {
            return $todayContent;
        }

        $previousContent = $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->isBefore($anchorDate))
            ->sortByDesc('date')
            ->first();

        if ($previousContent) {
            return $previousContent;
        }

        return $allDays
            ->filter(fn (DailyContent $d) => $d->date && $d->date->isAfter($anchorDate))
            ->sortBy('date')
            ->first();
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


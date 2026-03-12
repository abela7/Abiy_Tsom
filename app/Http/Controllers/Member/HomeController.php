<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\DailyContent;
use App\Models\Lectionary;
use App\Models\LentSeason;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use App\Models\MemberDailyView;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use App\Services\AbiyTsomStructure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Member home / dashboard — shows today's content, checklist, and progress.
 */
class HomeController extends Controller
{
    /**
     * Member dashboard — today's view.
     */
    public function index(Request $request): View
    {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        $today = null;
        $weekTheme = null;

        if ($season) {
            $today = DailyContent::where('lent_season_id', $season->id)
                ->where('date', Carbon::today()->toDateString())
                ->where('is_published', true)
                ->with(['weeklyTheme'])
                ->first();

            $weekTheme = $this->resolveWeekThemeForDate($season->id, Carbon::today());

            if ($today && $weekTheme) {
                $today->setRelation('weeklyTheme', $weekTheme);
            }
        }

        $easterTimezone = config('app.easter_timezone', 'Europe/London');
        $easterAt = Carbon::parse(
            config('app.easter_date', '2026-04-12 03:00'),
            $easterTimezone
        );
        $lentStartAt = Carbon::parse(
            config('app.lent_start_date', '2026-02-15 03:00'),
            $easterTimezone
        );

        $announcements = Announcement::orderByDesc('created_at')->get();

        $banners = Banner::active()->orderBy('sort_order')->get();

        $todayUnavailable = false;

        // View Today target: today's content if in Lent, else recommended day (never crash)
        $viewTodayTarget = $today;
        if (! $viewTodayTarget && $season) {
            $now = Carbon::today();
            $baseQuery = fn () => DailyContent::where('lent_season_id', $season->id)->where('is_published', true);

            if ($now->between(Carbon::parse($season->start_date), Carbon::parse($season->end_date))) {
                $todayUnavailable = true;
            } elseif ($now->lt($season->start_date)) {
                $viewTodayTarget = ($baseQuery)()->orderBy('day_number')->first();
            } elseif ($now->gt($season->end_date)) {
                $viewTodayTarget = ($baseQuery)()->orderByDesc('day_number')->first();
            } else {
                $viewTodayTarget = ($baseQuery)()->where('date', '>=', $now)->orderBy('date')->first()
                    ?? ($baseQuery)()->where('date', '<=', $now)->orderByDesc('date')->first();
            }
        }

        return view('member.home', compact(
            'member', 'season', 'today', 'weekTheme', 'easterAt', 'lentStartAt', 'easterTimezone', 'announcements',
            'banners', 'viewTodayTarget', 'todayUnavailable'
        ));
    }

    public function todayUnavailable(Request $request): View|RedirectResponse
    {
        $season = LentSeason::active();
        $today = null;

        if ($season) {
            $today = DailyContent::where('lent_season_id', $season->id)
                ->where('date', Carbon::today()->toDateString())
                ->where('is_published', true)
                ->first();
        }

        if ($today) {
            return redirect()->route('member.day', $today);
        }

        return view('member.today-unavailable', [
            'member' => $request->attributes->get('member'),
        ]);
    }

    /**
     * Show the full 55-day Lent calendar in a clean grid.
     */
    public function calendar(Request $request): View
    {
        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        $weeks = [];
        $contentByDay = collect();
        $checklistByDay = collect();
        $dailyContentIds = [];

        if ($season) {
            $published = DailyContent::where('lent_season_id', $season->id)
                ->where('is_published', true)
                ->with('weeklyTheme')
                ->get();

            $contentByDay = $published->keyBy('day_number');
            $contentByDate = $published
                ->filter(fn (DailyContent $content): bool => $content->date !== null)
                ->keyBy(fn (DailyContent $content): string => $content->date->toDateString());
            $dailyContentIds = $published->pluck('id')->toArray();

            $activitiesCount = Activity::where('lent_season_id', $season->id)->where('is_active', true)->count();

            if ($member) {
                $checklistByDay = MemberChecklist::where('member_id', $member->id)
                    ->whereIn('daily_content_id', $dailyContentIds)
                    ->get()
                    ->groupBy('daily_content_id');
            }

            $themesByWeek = $season->weeklyThemes()->orderBy('week_number')->get()->keyBy('week_number');
            $locale = app()->getLocale();

            $seasonStart = Carbon::parse($season->start_date)->startOfDay();
            $seasonEnd = Carbon::parse($season->end_date)->startOfDay();

            if ($themesByWeek->isNotEmpty()) {
                $seenDates = [];

                foreach ($themesByWeek as $theme) {
                    $weekStart = Carbon::parse($theme->week_start_date)->startOfDay();
                    $weekEnd = Carbon::parse($theme->week_end_date)->startOfDay();

                    if ($weekEnd->lt($weekStart)) {
                        continue;
                    }

                    if ($weekStart->lt($seasonStart)) {
                        $weekStart = $seasonStart->copy();
                    }

                    if ($weekEnd->gt($seasonEnd)) {
                        $weekEnd = $seasonEnd->copy();
                    }

                    if ($weekEnd->lt($weekStart)) {
                        continue;
                    }

                    $daysInWeek = [];
                    $cursor = $weekStart->copy();

                    while ($cursor->lte($weekEnd)) {
                        $dateKey = $cursor->toDateString();

                        if (isset($seenDates[$dateKey])) {
                            $cursor->addDay();
                            continue;
                        }

                        $seenDates[$dateKey] = true;
                        $content = $contentByDate->get($dateKey);
                        $completedCount = 0;

                        if ($content && $member) {
                            $checks = $checklistByDay->get($content->id, collect());
                            $completedCount = $checks->where('completed', true)->count();
                        }

                        $pct = ($activitiesCount > 0 && $content) ? round(($completedCount / $activitiesCount) * 100) : 0;
                        $isToday = $cursor->isToday();
                        $isPast = $cursor->isPast() && ! $isToday;
                        $isFuture = $cursor->isFuture();

                        $daysInWeek[] = [
                            'day_number' => $content?->day_number ?? ($seasonStart->diffInDays($cursor) + 1),
                            'date' => $cursor->copy(),
                            'content' => $content,
                            'is_today' => $isToday,
                            'is_past' => $isPast,
                            'is_future' => $isFuture,
                            'pct' => $pct,
                            'has_content' => (bool) $content,
                        ];

                        $cursor->addDay();
                    }

                    if ($daysInWeek === []) {
                        continue;
                    }

                    $weeks[] = [
                        'number' => $theme->week_number,
                        'name' => $locale === 'am'
                            ? ($theme->name_am ?? $theme->name_geez ?? $theme->name_en ?? '-')
                            : ($theme->name_en ?? $theme->name_am ?? $theme->name_geez ?? '-'),
                        'meaning' => $locale === 'am'
                            ? ($theme->meaning_am ?? $theme->meaning ?? '')
                            : ($theme->meaning ?? $theme->meaning_am ?? ''),
                        'days' => $daysInWeek,
                    ];
                }
            } else {
                $start = $seasonStart->copy();
                $allWeeks = AbiyTsomStructure::getWeeks();

                foreach ($allWeeks as $weekNum => $weekInfo) {
                    $daysInWeek = [];
                    for ($d = $weekInfo['day_start']; $d <= $weekInfo['day_end']; $d++) {
                        $date = $start->copy()->addDays($d - 1);
                        $content = $contentByDay->get($d);
                        $completedCount = 0;
                        if ($content && $member) {
                            $checks = $checklistByDay->get($content->id, collect());
                            $completedCount = $checks->where('completed', true)->count();
                        }
                        $pct = ($activitiesCount > 0 && $content) ? round(($completedCount / $activitiesCount) * 100) : 0;

                        $isToday = $date->isToday();
                        $isPast = $date->isPast() && ! $isToday;
                        $isFuture = $date->isFuture();

                        $daysInWeek[] = [
                            'day_number' => $d,
                            'date' => $date,
                            'content' => $content,
                            'is_today' => $isToday,
                            'is_past' => $isPast,
                            'is_future' => $isFuture,
                            'pct' => $pct,
                            'has_content' => (bool) $content,
                        ];
                    }

                    $theme = $themesByWeek->get($weekNum);
                    if ($theme) {
                        $weekName = $locale === 'am'
                            ? ($theme->name_am ?? $theme->name_geez ?? $theme->name_en)
                            : ($theme->name_en ?? $theme->name_am ?? $theme->name_geez);
                    } else {
                        $weekName = $locale === 'am'
                            ? ($weekInfo['name_am'] ?? $weekInfo['name_geez'] ?? $weekInfo['name_en'])
                            : $weekInfo['name_en'];
                    }

                    $weeks[] = [
                        'number' => $weekNum,
                        'name' => $weekName ?? $weekInfo['name_en'],
                        'meaning' => $theme
                            ? ($locale === 'am' ? ($theme->meaning_am ?? $theme->meaning) : ($theme->meaning ?? $theme->meaning_am))
                            : $weekInfo['meaning'],
                        'days' => $daysInWeek,
                    ];
                }
            }
        }

        $activities = $season
            ? Activity::where('lent_season_id', $season->id)->where('is_active', true)->get()
            : collect();

        return view('member.calendar', compact('member', 'season', 'weeks', 'activities'));
    }

    /**
     * Show a specific day's content.
     */
    public function day(Request $request, DailyContent $daily, EthiopianCalendarService $ethCalendar): View
    {
        if (! $daily->is_published) {
            abort(404);
        }

        $member = $request->attributes->get('member');

        // Track unique view (member or anonymous by IP)
        if ($member) {
            MemberDailyView::firstOrCreate(
                ['member_id' => $member->id, 'daily_content_id' => $daily->id],
                ['ip_address' => $request->ip(), 'viewed_at' => now()]
            );
        } else {
            $ip = $request->ip();
            MemberDailyView::firstOrCreate(
                ['ip_address' => $ip, 'daily_content_id' => $daily->id, 'member_id' => null],
                ['viewed_at' => now()]
            );
        }

        // Load books relation for multiple spiritual books per day
        $daily->load(['weeklyTheme', 'mezmurs', 'references', 'books', 'sinksarImages']);

        $resolvedWeekTheme = $this->resolveWeekThemeForDate($daily->lent_season_id, $daily->date);
        if ($resolvedWeekTheme) {
            $daily->setRelation('weeklyTheme', $resolvedWeekTheme);
        }

        // Ethiopian calendar date + celebration
        $ethDateInfo = $ethCalendar->getDateInfo($daily->date, app()->getLocale());

        $activities = Activity::where('lent_season_id', $daily->lent_season_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $customActivities = $member
            ? $member->customActivities()->orderBy('sort_order')->get()
            : collect();

        $checklist = collect();
        $customChecklist = collect();
        if ($member) {
            $checklist = MemberChecklist::where('member_id', $member->id)
                ->where('daily_content_id', $daily->id)
                ->get()
                ->keyBy('activity_id');

            $customChecklist = MemberCustomChecklist::where('member_id', $member->id)
                ->where('daily_content_id', $daily->id)
                ->get()
                ->keyBy('member_custom_activity_id');
        }

        $prevDay = DailyContent::where('lent_season_id', $daily->lent_season_id)
            ->where('day_number', $daily->day_number - 1)
            ->where('is_published', true)
            ->first();

        $nextDay = DailyContent::where('lent_season_id', $daily->lent_season_id)
            ->where('day_number', $daily->day_number + 1)
            ->where('is_published', true)
            ->first();

        $lectionary = Lectionary::where('month', $ethDateInfo['ethiopian_date']['month'])
            ->where('day', $ethDateInfo['ethiopian_date']['day'])
            ->first();

        return view('member.day', compact('member', 'daily', 'activities', 'checklist', 'customActivities', 'customChecklist', 'ethDateInfo', 'prevDay', 'nextDay', 'lectionary'));
    }

    /**
     * Show the commemorations (annual & monthly) for a specific day.
     */
    public function commemorations(Request $request, DailyContent $daily, EthiopianCalendarService $ethCalendar): View
    {
        if (! $daily->is_published) {
            abort(404);
        }

        $ethDateInfo = $ethCalendar->getDateInfo($daily->date, app()->getLocale());

        return view('member.commemorations', compact('daily', 'ethDateInfo'));
    }

    /**
     * Show a weekly theme's full details.
     */
    public function week(Request $request, WeeklyTheme $weeklyTheme): View
    {
        $member = $request->attributes->get('member');

        return view('member.week', compact('member', 'weeklyTheme'));
    }

    private function resolveWeekThemeForDate(int|string|null $seasonId, Carbon|string|null $date): ?WeeklyTheme
    {
        if ($seasonId === null || ! is_numeric((string) $seasonId) || $date === null) {
            return null;
        }

        $resolvedDate = $date instanceof Carbon
            ? $date->copy()
            : Carbon::parse($date);

        return WeeklyTheme::where('lent_season_id', (int) $seasonId)
            ->whereDate('week_start_date', '<=', $resolvedDate)
            ->whereDate('week_end_date', '>=', $resolvedDate)
            ->first();
    }
}

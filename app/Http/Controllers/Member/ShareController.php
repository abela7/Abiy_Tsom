<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Concerns\DetectsPreviewBots;
use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\Lectionary;
use App\Models\Member;
use App\Models\MemberReminderOpen;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Public share landing page — serves OG meta tags for social
 * crawlers and only honors reminder codes for an already-authenticated
 * matching member session.
 */
class ShareController extends Controller
{
    use DetectsPreviewBots;

    /**
     * Render a lightweight page with OG meta tags for the day.
     *
     * Social crawlers (WhatsApp, Telegram, Facebook) receive OG tags only.
     * Human visitors without a matching active member session always fall
     * back to the public day view, even if a reminder code is present.
     */
    public function day(
        Request $request,
        DailyContent $daily,
        TelegramAuthService $telegramAuthService,
        MemberSessionService $memberSessionService
    ): View|Response|RedirectResponse {
        if (! $daily->is_published) {
            abort(404);
        }

        $daily->load('weeklyTheme');

        $resolvedWeekTheme = WeeklyTheme::where('lent_season_id', $daily->lent_season_id)
            ->whereDate('week_start_date', '<=', $daily->date)
            ->whereDate('week_end_date', '>=', $daily->date)
            ->first();

        if ($resolvedWeekTheme) {
            $daily->setRelation('weeklyTheme', $resolvedWeekTheme);
        }

        $weekName = $daily->weeklyTheme
            ? (localized($daily->weeklyTheme, 'name')
                ?? $daily->weeklyTheme->name_en
                ?? '-')
            : '';

        $dayTitle = localized($daily, 'day_title')
            ?? __('app.day_x', ['day' => $daily->day_number]);

        $ogTitle = $weekName
            ? ($weekName.' - '.$dayTitle)
            : $dayTitle;

        $ogDescription = __('app.share_day_description');
        $publicDayUrl = route('share.day.public', $daily);

        // Preview bots get OG tags only — code is NOT consumed.
        if ($this->isPreviewBot($request)) {
            return view('member.share-day', compact(
                'daily',
                'ogTitle',
                'ogDescription',
                'publicDayUrl',
            ));
        }

        $currentMember = $memberSessionService->resolveMember($request);
        $code = (string) $request->query('code', '');

        if ($code !== '' && preg_match('/^[A-Za-z0-9]{20,128}$/', $code)) {
            $token = $telegramAuthService->peekCode(
                $code,
                TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS
            );

            if ($token && $telegramAuthService->isMemberToken($token) && $token->actor instanceof Member) {
                $intendedMember = $token->actor;

                if ($currentMember instanceof Member && $currentMember->is($intendedMember)) {
                    $consumedToken = $telegramAuthService->consumeCode(
                        $code,
                        TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS
                    );
                    $member = $consumedToken?->actor;

                    if ($member instanceof Member) {
                        $this->trackReminderOpen($request, $intendedMember, $daily, authenticatedSession: true);

                        $redirectUrl = $member->passcode_enabled
                            ? route('member.passcode')
                            : $telegramAuthService->sanitizeRedirectPath(
                                $consumedToken->redirect_to,
                                $daily->memberDayUrl(false)
                            );

                        return redirect($redirectUrl);
                    }
                } else {
                    $this->trackReminderOpen($request, $intendedMember, $daily, authenticatedSession: false);
                }
            }
        }

        // Human visitors do not need the intermediate OG landing page.
        // Redirect them straight to the public read-only day view.
        return redirect($publicDayUrl);
    }

    private function trackReminderOpen(
        Request $request,
        Member $member,
        DailyContent $daily,
        bool $authenticatedSession
    ): void {
        $now = now();
        $open = MemberReminderOpen::query()->firstOrNew([
            'member_id' => $member->getKey(),
            'daily_content_id' => $daily->getKey(),
        ]);

        if (! $open->exists) {
            $open->forceFill([
                'first_opened_at' => $now,
                'open_count' => 0,
                'authenticated_open_count' => 0,
                'public_open_count' => 0,
            ]);
        }

        $open->forceFill([
            'last_opened_at' => $now,
            'last_open_state' => $authenticatedSession ? 'authenticated_session' : 'link_only',
            'last_ip_address' => $request->ip(),
            'last_user_agent' => $this->truncateUserAgent($request),
            'open_count' => $open->open_count + 1,
            'authenticated_open_count' => $open->authenticated_open_count + ($authenticatedSession ? 1 : 0),
            'public_open_count' => $open->public_open_count + ($authenticatedSession ? 0 : 1),
            'last_authenticated_open_at' => $authenticatedSession
                ? $now
                : $open->last_authenticated_open_at,
        ]);
        $open->save();
    }

    private function truncateUserAgent(Request $request): ?string
    {
        $userAgent = trim((string) $request->userAgent());

        return $userAgent === ''
            ? null
            : Str::limit($userAgent, 512, '');
    }

    /**
     * Public, read-only day view for users without an authenticated member session.
     */
    public function publicDay(Request $request, DailyContent $daily, EthiopianCalendarService $ethCalendar): View
    {
        if (! $daily->is_published) {
            abort(404);
        }

        $member = null;
        $publicPreview = true;

        $daily->load(['weeklyTheme', 'mezmurs', 'references', 'books', 'sinksarImages']);
        $ethDateInfo = $ethCalendar->getDateInfo($daily->date, app()->getLocale());
        $activities = collect();
        $checklist = collect();
        $customActivities = collect();
        $customChecklist = collect();

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

        $prevDayUrl = $prevDay ? route('share.day.public', ['daily' => $prevDay]) : null;
        $nextDayUrl = $nextDay ? route('share.day.public', ['daily' => $nextDay]) : null;
        $commemorationsUrl = null;

        return view('member.day', compact(
            'member',
            'daily',
            'activities',
            'checklist',
            'customActivities',
            'customChecklist',
            'publicPreview',
            'ethDateInfo',
            'prevDay',
            'nextDay',
            'prevDayUrl',
            'nextDayUrl',
            'commemorationsUrl',
            'lectionary',
        ));
    }
}

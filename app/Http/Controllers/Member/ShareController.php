<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Concerns\DetectsPreviewBots;
use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\Lectionary;
use App\Models\Member;
use App\Services\EthiopianCalendarService;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Public share landing page — serves OG meta tags for social
 * crawlers, then authenticates real users server-side before
 * redirecting to the member day page.
 */
class ShareController extends Controller
{
    use DetectsPreviewBots;

    /**
     * Render a lightweight page with OG meta tags for the day.
     *
     * Social crawlers (WhatsApp, Telegram, Facebook) receive OG tags only.
     * Human visitors with a valid auth code are authenticated server-side
     * and redirected to the member day page — no client-side JS redirect
     * chain needed, which fixes WhatsApp in-app browser cookie issues.
     */
    public function day(
        Request $request,
        DailyContent $daily,
        TelegramAuthService $telegramAuthService,
        MemberSessionService $memberSessionService
    ): View|Response {
        if (! $daily->is_published) {
            abort(404);
        }

        $daily->load('weeklyTheme');

        $locale = app()->getLocale();
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

        // Human browser with a valid auth code: authenticate server-side.
        $code = (string) $request->query('code', '');

        if ($code !== '' && preg_match('/^[A-Za-z0-9]{20,128}$/', $code)) {
            $token = $telegramAuthService->consumeCode(
                $code,
                TelegramAuthService::PURPOSE_MEMBER_ACCESS
            );

            if ($token && $telegramAuthService->isMemberToken($token)) {
                $member = $token->actor;

                if ($member instanceof Member) {
                    $memberSessionService->establishSession($member, $request);
                    $request->session()->regenerate();

                    $redirectUrl = $member->passcode_enabled
                        ? route('member.passcode')
                        : $telegramAuthService->sanitizeRedirectPath(
                            $token->redirect_to,
                            route('member.day', ['daily' => $daily], false)
                        );

                    // Return 200 HTML so cookies are reliably stored before
                    // navigating to the cookie-protected page.
                    return response()->view('auth.authenticated', [
                        'redirectUrl' => $redirectUrl,
                    ]);
                }
            }
        }

        // No code, invalid code, or expired code — show OG page
        // which redirects to public day view.
        return view('member.share-day', compact(
            'daily',
            'ogTitle',
            'ogDescription',
            'publicDayUrl',
        ));
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

        $lectionary = Lectionary::where('month', $ethDateInfo['month'])
            ->where('day', $ethDateInfo['day'])
            ->first();

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
            'lectionary',
        ));
    }
}

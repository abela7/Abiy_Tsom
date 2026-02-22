<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public share landing page — serves OG meta tags for social
 * crawlers, then redirects real users to the member day page.
 */
class ShareController extends Controller
{
    /**
     * Render a lightweight page with OG meta tags for the day.
     * Social crawlers (WhatsApp, Telegram, Facebook) will read the
     * og:title, og:description, and og:image from the HTML head.
     * Human visitors get redirected to the member day page.
     */
    public function day(Request $request, DailyContent $daily): View
    {
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
    public function publicDay(Request $request, DailyContent $daily): View
    {
        if (! $daily->is_published) {
            abort(404);
        }

        $member = null;
        $publicPreview = true;

        $daily->load(['weeklyTheme', 'mezmurs', 'references', 'books']);
        $activities = collect();
        $checklist = collect();
        $customActivities = collect();
        $customChecklist = collect();

        return view('member.day', compact(
            'member',
            'daily',
            'activities',
            'checklist',
            'customActivities',
            'customChecklist',
            'publicPreview',
        ));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\DailyContent;
use App\Models\WeeklyTheme;
use App\Services\EthiopianCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public (unauthenticated) access to content pages.
 *
 * Delegates to the same Blade views as the member controllers, but with
 * $member = null so checklists and progress are hidden.
 */
class PublicContentController extends Controller
{
    public function showDay(
        Request $request,
        string $dayNumber,
        DailyContent $daily,
        EthiopianCalendarService $ethCalendar
    ): View|RedirectResponse {
        if (! $daily->is_published) {
            abort(404);
        }

        if ((int) $dayNumber !== (int) $daily->day_number) {
            return redirect("/day/{$daily->day_number}-{$daily->id}");
        }

        // Delegate to the member HomeController which already handles $member = null.
        return app(\App\Http\Controllers\Member\HomeController::class)
            ->showDay($request, $dayNumber, $daily, $ethCalendar);
    }

    public function calendar(Request $request): View
    {
        return app(\App\Http\Controllers\Member\HomeController::class)
            ->calendar($request);
    }

    public function week(Request $request, WeeklyTheme $weeklyTheme): View
    {
        return app(\App\Http\Controllers\Member\HomeController::class)
            ->week($request, $weeklyTheme);
    }

    public function commemorations(
        Request $request,
        string $dayNumber,
        DailyContent $daily,
        EthiopianCalendarService $ethCalendar
    ): View|RedirectResponse {
        if (! $daily->is_published) {
            abort(404);
        }

        if ((int) $dayNumber !== (int) $daily->day_number) {
            return redirect("/day/{$daily->day_number}-{$daily->id}/commemorations");
        }

        return app(\App\Http\Controllers\Member\HomeController::class)
            ->showCommemorations($request, $dayNumber, $daily, $ethCalendar);
    }

    public function announcement(Announcement $announcement): View
    {
        return view('member.announcement.show', [
            'announcement' => $announcement,
            'member' => null,
        ]);
    }
}

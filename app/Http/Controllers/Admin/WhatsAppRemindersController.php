<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\View\View;

/**
 * Admin view of members with WhatsApp reminders enabled.
 */
class WhatsAppRemindersController extends Controller
{
    /**
     * Members with WhatsApp reminders enabled.
     */
    private function optedInQuery()
    {
        return Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time');
    }

    /**
     * List members with WhatsApp reminders and stats.
     */
    public function index(): View
    {
        $totalOptedIn = (clone $this->optedInQuery())->count();

        $byTime = (clone $this->optedInQuery())
            ->selectRaw('whatsapp_reminder_time as time, count(*) as count')
            ->groupBy('whatsapp_reminder_time')
            ->orderBy('whatsapp_reminder_time')
            ->get();

        $members = (clone $this->optedInQuery())
            ->orderBy('whatsapp_reminder_time')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.whatsapp.reminders', compact(
            'totalOptedIn',
            'byTime',
            'members'
        ));
    }

    /**
     * Update a member's reminder settings.
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $this->ensureOptedIn($member);

        $request->merge([
            'whatsapp_phone' => normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone', '')),
        ]);

        $validated = $request->validate([
            'baptism_name' => ['required', 'string', 'max:255'],
            'whatsapp_phone' => ['required', 'string', 'regex:/^\+447\d{9}$/'],
            'whatsapp_reminder_time' => ['required', 'date_format:H:i'],
        ]);

        $time = $validated['whatsapp_reminder_time'];
        if (! str_ends_with($time, ':00')) {
            $time .= ':00';
        }

        $member->update([
            'baptism_name' => $validated['baptism_name'],
            'whatsapp_phone' => $validated['whatsapp_phone'],
            'whatsapp_reminder_time' => $time,
        ]);

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.reminder_updated'));
    }

    /**
     * Disable reminder for a member (removes from list).
     */
    public function disable(Member $member): RedirectResponse
    {
        $this->ensureOptedIn($member);

        $member->update([
            'whatsapp_reminder_enabled' => false,
            'whatsapp_phone' => null,
            'whatsapp_reminder_time' => null,
        ]);

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.reminder_disabled'));
    }

    /**
     * Delete a member entirely.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $this->ensureOptedIn($member);

        $member->delete();

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.member_deleted'));
    }

    /**
     * Send today's reminder to a member on demand (admin-triggered).
     */
    public function sendReminder(Member $member, UltraMsgService $ultraMsg): JsonResponse
    {
        $this->ensureOptedIn($member);

        if (! $ultraMsg->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('app.whatsapp_not_configured'),
            ], 400);
        }

        $timezone = 'Europe/London';
        $today = CarbonImmutable::now($timezone)->toDateString();

        $season = LentSeason::active();
        if (! $season) {
            return response()->json([
                'success' => false,
                'message' => __('app.no_active_season'),
            ], 400);
        }

        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return response()->json([
                'success' => false,
                'message' => __('app.timetable_no_content_today'),
            ], 400);
        }

        $lang = in_array((string) $member->whatsapp_language, ['en', 'am'], true)
            ? (string) $member->whatsapp_language
            : 'en';
        $dayUrl = route('member.day', ['daily' => $dailyContent]).'?token='.urlencode((string) $member->token);
        $dayUrl = $this->ensureHttpsUrl($dayUrl);

        $message = Lang::get('app.whatsapp_daily_reminder_message', [
            'day' => $dailyContent->day_number,
            'url' => $dayUrl,
        ], $lang);

        $sent = $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);

        if ($sent) {
            $member->forceFill(['whatsapp_last_sent_date' => $today])->save();
        }

        return response()->json([
            'success' => true,
            'sent' => $sent,
            'message' => $sent ? __('app.timetable_reminder_sent') : __('app.whatsapp_test_failed'),
        ]);
    }

    private function ensureOptedIn(Member $member): void
    {
        if (! $member->whatsapp_reminder_enabled || ! $member->whatsapp_phone || ! $member->whatsapp_reminder_time) {
            abort(404);
        }
    }

    /**
     * Ensure reminder links are sent as full HTTPS URLs
     * on non-local environments for best WhatsApp clickability.
     */
    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

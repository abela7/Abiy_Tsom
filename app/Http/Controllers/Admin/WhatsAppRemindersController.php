<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\Translation;
use App\Services\TelegramAuthService;
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
     * Members with WhatsApp reminders enabled and confirmed.
     */
    private function optedInQuery()
    {
        return Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->where('whatsapp_confirmation_status', 'confirmed')
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time');
    }

    /**
     * Members who have set up WhatsApp (phone + time) — includes pending confirmation.
     */
    private function membersWithWhatsAppQuery()
    {
        return Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time')
            ->whereIn('whatsapp_confirmation_status', ['confirmed', 'pending']);
    }

    /**
     * List members with WhatsApp reminders and stats.
     */
    public function index(): View
    {
        $totalOptedIn = (clone $this->optedInQuery())->count();
        $totalPending = (clone $this->membersWithWhatsAppQuery())
            ->where('whatsapp_confirmation_status', 'pending')
            ->count();

        $byTime = (clone $this->optedInQuery())
            ->selectRaw('whatsapp_reminder_time as time, count(*) as count')
            ->groupBy('whatsapp_reminder_time')
            ->orderBy('whatsapp_reminder_time')
            ->get();

        $members = (clone $this->membersWithWhatsAppQuery())
            ->orderByRaw("CASE WHEN whatsapp_confirmation_status = 'confirmed' THEN 0 ELSE 1 END")
            ->orderBy('whatsapp_reminder_time')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.whatsapp.reminders', compact(
            'totalOptedIn',
            'totalPending',
            'byTime',
            'members'
        ));
    }

    /**
     * Require member to have phone + time set (allows pending or confirmed).
     */
    private function ensureHasWhatsAppSetup(Member $member): void
    {
        if (! $member->whatsapp_phone || ! $member->whatsapp_reminder_time) {
            abort(404);
        }
    }

    /**
     * Update a member's reminder settings (works for pending or confirmed).
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $this->ensureHasWhatsAppSetup($member);

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
     * Disable reminder for a member (removes from list). Works for pending or confirmed.
     */
    public function disable(Member $member): RedirectResponse
    {
        $this->ensureHasWhatsAppSetup($member);

        $member->update([
            'whatsapp_reminder_enabled' => false,
            'whatsapp_phone' => null,
            'whatsapp_reminder_time' => null,
            'whatsapp_confirmation_status' => 'none',
            'whatsapp_confirmation_requested_at' => null,
            'whatsapp_confirmation_responded_at' => null,
            'whatsapp_last_sent_date' => null,
        ]);

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.reminder_disabled'));
    }

    /**
     * Delete a member entirely. Works for pending or confirmed.
     */
    public function destroy(Member $member): RedirectResponse
    {
        $this->ensureHasWhatsAppSetup($member);

        $member->delete();

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.member_deleted'));
    }

    /**
     * Manually confirm a member (bypasses webhook YES reply). For pending only.
     */
    public function confirm(Member $member): RedirectResponse
    {
        $this->ensureHasWhatsAppSetup($member);

        if ($member->whatsapp_confirmation_status !== 'pending') {
            return redirect()
                ->route('admin.whatsapp.reminders')
                ->with('info', __('app.reminder_already_confirmed'));
        }

        $member->update([
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_confirmation_responded_at' => now(),
        ]);

        return redirect()
            ->route('admin.whatsapp.reminders')
            ->with('success', __('app.reminder_manually_confirmed'));
    }

    /**
     * Send today's reminder to a member on demand (admin-triggered).
     */
    public function sendReminder(
        Member $member,
        UltraMsgService $ultraMsg,
        TelegramAuthService $telegramAuthService
    ): JsonResponse
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
        Translation::loadFromDb($lang);

            $code = $telegramAuthService->createCode(
                $member,
                TelegramAuthService::PURPOSE_MEMBER_ACCESS,
                route('member.day', ['daily' => $dailyContent], false)
            );
            $dayUrl = route('share.day', [
                'daily' => $dailyContent,
                'code' => $code,
            ]);
            $dayUrl = $this->ensureHttpsUrl($dayUrl);

        $header = Lang::get('app.whatsapp_daily_reminder_header', [
            'baptism_name' => $member->baptism_name ?? '',
            'day' => $dailyContent->day_number,
        ], $lang);
        $content = Lang::get('app.whatsapp_daily_reminder_content', [
            'url' => $dayUrl,
        ], $lang);
        $message = $header."\n".$content;

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
        if (! $member->whatsapp_reminder_enabled
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || ! $member->whatsapp_reminder_time) {
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

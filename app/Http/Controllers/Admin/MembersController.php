<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomActivity;
use App\Models\MemberCustomChecklist;
use App\Models\MemberSession;
use App\Models\TelegramAccessToken;
use App\Services\TelegramAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member analytics, listing, and management for admins.
 */
class MembersController extends Controller
{
    /**
     * Show member stats and paginated member list.
     */
    public function index(Request $request): View
    {
        $activeFilter = $request->query('active', '');
        $totalMembers = Member::count();

        $registrationsByDay = Member::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $firstRegistration = $registrationsByDay->first()?->date;
        $lastRegistration = $registrationsByDay->last()?->date;

        $last7Days = Member::where('created_at', '>=', now()->subDays(7))->count();
        $last30Days = Member::where('created_at', '>=', now()->subDays(30))->count();

        $localeDistribution = Member::query()
            ->selectRaw('locale, COUNT(*) as count')
            ->groupBy('locale')
            ->orderByDesc('count')
            ->get();

        $themeDistribution = Member::query()
            ->selectRaw('theme, COUNT(*) as count')
            ->groupBy('theme')
            ->orderByDesc('count')
            ->get();

        $passcodeEnabled = Member::where('passcode_enabled', true)->count();
        $tourCompletedCount = Member::whereNotNull('tour_completed_at')->count();

        $totalChecklistCompletions = MemberChecklist::where('completed', true)->count();
        $totalCustomCompletions = MemberCustomChecklist::where('completed', true)->count();
        $engagedMembers = Member::whereHas('checklists', fn ($q) => $q->where('completed', true))
            ->orWhereHas('customChecklists', fn ($q) => $q->where('completed', true))
            ->count();

        $nonUkRequested = Member::where('whatsapp_non_uk_requested', true)->count();

        $telegramBotUsername = ltrim((string) config('services.telegram.bot_username', ''), '@');
        $telegramMemberLinks = (array) session('telegram_member_links', []);
        $membersQuery = Member::with(['sessions' => function ($q) {
            $q->whereNull('revoked_at')
              ->orderByDesc('last_used_at')
              ->limit(1);
        }]);

        if ($activeFilter === 'today') {
            $membersQuery->whereHas('sessions', fn ($q) => $q->whereNull('revoked_at')->where('last_used_at', '>=', now()->startOfDay()));
        } elseif (preg_match('/^(\d+)d$/', $activeFilter, $m)) {
            // Inactive for N+ days: no active session in the last N days
            $cutoff = now()->subDays((int) $m[1]);
            $membersQuery->where(function ($q) use ($cutoff) {
                $q->whereDoesntHave('sessions', fn ($s) => $s->whereNull('revoked_at'))
                  ->orWhereDoesntHave('sessions', fn ($s) => $s->whereNull('revoked_at')->where('last_used_at', '>=', $cutoff));
            });
        } elseif ($activeFilter === 'custom') {
            $from = $request->query('from');
            $to = $request->query('to');
            if ($from && $to) {
                $membersQuery->whereHas('sessions', fn ($q) => $q->whereNull('revoked_at')
                    ->whereBetween('last_used_at', [$from . ' 00:00:00', $to . ' 23:59:59']));
            }
        }

        $members = $membersQuery->orderByDesc('created_at')->paginate(25)->appends($request->only('active', 'from', 'to'));

        return view('admin.members.index', compact(
            'activeFilter',
            'totalMembers',
            'registrationsByDay',
            'firstRegistration',
            'lastRegistration',
            'last7Days',
            'last30Days',
            'localeDistribution',
            'themeDistribution',
            'passcodeEnabled',
            'tourCompletedCount',
            'totalChecklistCompletions',
            'totalCustomCompletions',
            'engagedMembers',
            'nonUkRequested',
            'members',
            'telegramBotUsername',
            'telegramMemberLinks'
        ));
    }

    /**
     * Delete a single member and all their associated data.
     */
    public function destroy(Member $member): RedirectResponse
    {
        // Disable WhatsApp reminders first so the cron job cannot send a
        // message in the brief window between this call and the actual delete.
        $member->forceFill([
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'none',
        ])->save();

        TelegramAccessToken::where('actor_type', Member::class)
            ->where('actor_id', $member->id)
            ->delete();

        $member->sessions()->delete();
        $member->checklists()->delete();
        $member->customChecklists()->delete();
        $member->customActivities()->delete();
        $member->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.member_deleted'));
    }

    /**
     * Reset the app tour for a member so it will show again on their next home visit.
     */
    public function restartTour(Member $member): RedirectResponse
    {
        $member->update(['tour_completed_at' => null]);

        return redirect()->route('admin.members.index')
            ->with('success', __('app.tour_restarted_for_member', ['name' => $member->baptism_name]));
    }

    /**
     * Wipe all activity/checklist data for a member but keep their account.
     */
    public function wipeData(Member $member): RedirectResponse
    {
        $member->checklists()->delete();
        $member->customChecklists()->delete();
        $member->customActivities()->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.member_data_wiped'));
    }

    /**
     * Wipe every member and all their data (nuclear option).
     * Uses DELETE instead of TRUNCATE to avoid MySQL FK constraint errors.
     */
    public function wipeAll(): RedirectResponse
    {
        // Kill all WhatsApp reminders first so the cron job cannot pick up
        // any member during the brief window while rows are being deleted.
        Member::query()->update([
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'none',
        ]);

        TelegramAccessToken::where('actor_type', Member::class)->delete();
        MemberSession::query()->delete();
        MemberChecklist::query()->delete();
        MemberCustomChecklist::query()->delete();
        MemberCustomActivity::query()->delete();
        Member::query()->delete();

        return redirect()->route('admin.members.index')
            ->with('success', __('app.all_members_wiped'));
    }

    /**
     * Generate a one-time Telegram mini-app launch link for this member.
     */
    public function createTelegramMiniLink(
        Member $member,
        TelegramAuthService $telegramAuthService
    ): RedirectResponse {
        $telegramBotUsername = ltrim((string) config('services.telegram.bot_username', ''), '@');
        if ($telegramBotUsername === '') {
            return redirect()
                ->route('admin.members.index')
                ->with('error', __('app.telegram_bot_username_missing'));
        }

        $code = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.home'),
            120
        );

        $payload = 'member:' . $code;
        $link = 'https://t.me/' . $telegramBotUsername . '?startapp=' . rawurlencode($payload);

        $links = (array) session('telegram_member_links', []);
        $links[$member->id] = $link;

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'One-time Telegram mini-app link generated for ' . $member->baptism_name . '.')
            ->with('telegram_member_links', $links);
    }
}

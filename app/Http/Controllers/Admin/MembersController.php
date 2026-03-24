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
use App\Services\UltraMsgService;
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
        $activeFilter = (string) $request->query('active', '');
        $searchQuery = trim((string) $request->query('q', ''));
        $whatsappFilter = (string) $request->query('whatsapp', '');
        $localeFilter = (string) $request->query('locale', '');
        $tourFilter = (string) $request->query('tour', '');
        $sortBy = (string) $request->query('sort', 'newest');

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

        $verifiedMembers = Member::where(function ($q) {
            $q->whereNotNull('phone_verified_at')
                ->orWhereNotNull('email_verified_at');
        })->count();
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

        // Search by name, phone, or email
        if ($searchQuery !== '') {
            $membersQuery->where(function ($q) use ($searchQuery) {
                $q->where('baptism_name', 'like', '%'.$searchQuery.'%')
                    ->orWhere('whatsapp_phone', 'like', '%'.$searchQuery.'%')
                    ->orWhere('email', 'like', '%'.$searchQuery.'%');
            });
        }

        // Activity filter
        if ($activeFilter === 'today') {
            $membersQuery->whereHas('sessions', fn ($q) => $q->whereNull('revoked_at')->where('last_used_at', '>=', now()->startOfDay()));
        } elseif (preg_match('/^(\d+)d$/', $activeFilter, $m)) {
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
                    ->whereBetween('last_used_at', [$from.' 00:00:00', $to.' 23:59:59']));
            }
        }

        // WhatsApp status filter
        if ($whatsappFilter === 'confirmed') {
            $membersQuery->where('whatsapp_confirmation_status', 'confirmed');
        } elseif ($whatsappFilter === 'pending') {
            $membersQuery->where('whatsapp_confirmation_status', 'pending');
        } elseif ($whatsappFilter === 'rejected') {
            $membersQuery->where('whatsapp_confirmation_status', 'rejected');
        } elseif ($whatsappFilter === 'non_uk') {
            $membersQuery->where('whatsapp_non_uk_requested', true);
        } elseif ($whatsappFilter === 'none') {
            $membersQuery->where(function ($q) {
                $q->whereNull('whatsapp_confirmation_status')
                    ->orWhere('whatsapp_confirmation_status', 'none');
            })->where('whatsapp_non_uk_requested', false);
        }

        // Locale filter
        if ($localeFilter !== '') {
            $membersQuery->where('locale', $localeFilter);
        }

        // Tour filter
        if ($tourFilter === 'completed') {
            $membersQuery->whereNotNull('tour_completed_at');
        } elseif ($tourFilter === 'not_completed') {
            $membersQuery->whereNull('tour_completed_at');
        }

        // Sort
        if ($sortBy === 'oldest') {
            $membersQuery->orderBy('created_at');
        } elseif ($sortBy === 'name_asc') {
            $membersQuery->orderBy('baptism_name');
        } elseif ($sortBy === 'name_desc') {
            $membersQuery->orderByDesc('baptism_name');
        } elseif ($sortBy === 'last_active') {
            $membersQuery->addSelect(['last_active_at' => MemberSession::selectRaw('MAX(last_used_at)')
                ->whereColumn('member_sessions.member_id', 'members.id')
                ->whereNull('revoked_at'),
            ])->orderByDesc('last_active_at');
        } else {
            $membersQuery->orderByDesc('created_at');
        }

        $members = $membersQuery->paginate(25)->appends($request->only('active', 'from', 'to', 'q', 'whatsapp', 'locale', 'tour', 'sort'));

        return view('admin.members.index', compact(
            'activeFilter',
            'searchQuery',
            'whatsappFilter',
            'localeFilter',
            'tourFilter',
            'sortBy',
            'totalMembers',
            'registrationsByDay',
            'firstRegistration',
            'lastRegistration',
            'last7Days',
            'last30Days',
            'localeDistribution',
            'themeDistribution',
            'verifiedMembers',
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
     * Update member profile fields (e.g. baptism name shown in the member app header).
     */
    public function update(Request $request, Member $member): RedirectResponse
    {
        $validated = $request->validate([
            'baptism_name' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $member->update([
            'baptism_name' => trim($validated['baptism_name']),
        ]);

        return redirect()
            ->route('admin.members.show', $member)
            ->with('success', __('app.admin_member_baptism_name_updated'));
    }

    /**
     * Show detailed member profile.
     */
    public function show(Member $member): View
    {
        $member->load(['referrer']);

        // All sessions (active + revoked), newest first
        $sessions = MemberSession::where('member_id', $member->id)
            ->orderByDesc('last_used_at')
            ->get();

        // Reminder link opens, newest first
        $reminderOpens = $member->reminderLinkOpens()
            ->with('dailyContent')
            ->orderByDesc('last_opened_at')
            ->limit(50)
            ->get();

        // Daily views count
        $totalDailyViews = $member->dailyViews()->count();

        // Checklist stats
        $totalChecklists = $member->checklists()->where('completed', true)->count();
        $totalCustomChecklists = $member->customChecklists()->where('completed', true)->count();
        $customActivities = $member->customActivities()->orderBy('sort_order')->get();

        // Fundraising responses
        $fundraisingResponses = [];
        if (class_exists(\App\Models\MemberFundraisingResponse::class)) {
            $fundraisingResponses = \App\Models\MemberFundraisingResponse::where('member_id', $member->id)
                ->with('campaign')
                ->get();
        }

        return view('admin.members.show', compact(
            'member',
            'sessions',
            'reminderOpens',
            'totalDailyViews',
            'totalChecklists',
            'totalCustomChecklists',
            'customActivities',
            'fundraisingResponses'
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
     * Re-invite a pending member: delete their session/data, keep their phone
     * on record, and send a WhatsApp message with a fresh registration link.
     */
    public function reInvite(Member $member, UltraMsgService $ultraMsg): RedirectResponse
    {
        if (! $member->whatsapp_phone) {
            return redirect()->route('admin.members.index')
                ->with('success', 'No WhatsApp phone on file for '.$member->baptism_name.'.');
        }

        $name = $member->baptism_name;
        $phone = $member->whatsapp_phone;
        $locale = $member->locale ?? 'en';
        $siteUrl = config('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        // Build the message
        $message = $locale === 'am'
            ? __('app.whatsapp_reinvite_message_am', ['name' => $name, 'url' => $siteUrl])
            : __('app.whatsapp_reinvite_message_en', ['name' => $name, 'url' => $siteUrl]);

        // Delete sessions and member data — keep phone number noted in the message log
        $member->sessions()->delete();
        $member->checklists()->delete();
        $member->customChecklists()->delete();
        $member->customActivities()->delete();

        TelegramAccessToken::where('actor_type', Member::class)
            ->where('actor_id', $member->id)
            ->delete();

        $member->delete();

        // Send the WhatsApp message
        $sent = $ultraMsg->sendTextMessage($phone, $message);

        $status = $sent
            ? 'Re-invite sent to '.$name.' ('.$phone.'). Member record deleted.'
            : 'Failed to send WhatsApp to '.$phone.'. Member record was still deleted.';

        return redirect()->route('admin.members.index')->with('success', $status);
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

        $payload = 'member:'.$code;
        $link = 'https://t.me/'.$telegramBotUsername.'?startapp='.rawurlencode($payload);

        $links = (array) session('telegram_member_links', []);
        $links[$member->id] = $link;

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'One-time Telegram mini-app link generated for '.$member->baptism_name.'.')
            ->with('telegram_member_links', $links);
    }
}

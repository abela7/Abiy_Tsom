<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\View\View;

/**
 * Report: members and staff who linked the Telegram bot (stored chat IDs).
 */
class TelegramLinkedUsersController extends Controller
{
    /**
     * Show counts and paginated lists of linked Telegram accounts.
     */
    public function index(): View
    {
        $linkedMembersBase = Member::query()
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '');

        $memberCount = (clone $linkedMembersBase)->count();

        $staffLinked = User::query()
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $staffCount = $staffLinked->count();

        $duplicateMemberChatIds = Member::query()
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->select('telegram_chat_id')
            ->groupBy('telegram_chat_id')
            ->havingRaw('count(*) > 1')
            ->pluck('telegram_chat_id')
            ->all();

        $members = (clone $linkedMembersBase)
            ->orderByDesc('id')
            ->paginate(40)
            ->withQueryString();

        return view('admin.telegram.linked-users', [
            'memberCount' => $memberCount,
            'staffCount' => $staffCount,
            'connectionTotal' => $memberCount + $staffCount,
            'duplicateMemberChatIds' => $duplicateMemberChatIds,
            'members' => $members,
            'staffLinked' => $staffLinked,
        ]);
    }
}

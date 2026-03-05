<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin raw view of all members with WhatsApp data — for debugging.
 */
class WhatsAppMembersDataController extends Controller
{
    public function index(): View
    {
        $duplicatePhones = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->select('whatsapp_phone')
            ->groupBy('whatsapp_phone')
            ->havingRaw('count(*) > 1')
            ->pluck('whatsapp_phone')
            ->all();

        $members = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->withCount(['checklists', 'customActivities', 'sessions'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.whatsapp.members-data', compact('members', 'duplicatePhones'));
    }

    public function destroy(Member $member): RedirectResponse
    {
        $member->delete();

        return redirect()
            ->route('admin.whatsapp.members-data')
            ->with('success', __('app.member_deleted'));
    }
}

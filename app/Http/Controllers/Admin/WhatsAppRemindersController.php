<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\View\View;

/**
 * Admin view of members with WhatsApp reminders enabled.
 */
class WhatsAppRemindersController extends Controller
{
    /**
     * List members with WhatsApp reminders and stats.
     */
    public function index(): View
    {
        $totalOptedIn = Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time')
            ->count();

        $byTime = Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time')
            ->selectRaw('whatsapp_reminder_time as time, count(*) as count')
            ->groupBy('whatsapp_reminder_time')
            ->orderBy('whatsapp_reminder_time')
            ->get();

        $members = Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time')
            ->orderBy('whatsapp_reminder_time')
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return view('admin.whatsapp.reminders', compact(
            'totalOptedIn',
            'byTime',
            'members'
        ));
    }
}

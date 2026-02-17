<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\View\View;

/**
 * Admin timetable view: which members subscribe to which reminder hour.
 */
class WhatsAppTimetableController extends Controller
{
    private function optedInQuery()
    {
        return Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time');
    }

    public function index(): View
    {
        $byTime = (clone $this->optedInQuery())
            ->selectRaw('whatsapp_reminder_time as time, count(*) as count')
            ->groupBy('whatsapp_reminder_time')
            ->orderBy('whatsapp_reminder_time')
            ->get();

        $membersByTime = (clone $this->optedInQuery())
            ->orderBy('baptism_name')
            ->get()
            ->groupBy('whatsapp_reminder_time');

        $totalOptedIn = (clone $this->optedInQuery())->count();

        return view('admin.whatsapp.timetable', compact('byTime', 'membersByTime', 'totalOptedIn'));
    }
}

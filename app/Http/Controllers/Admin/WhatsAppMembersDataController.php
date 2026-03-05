<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
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
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.whatsapp.members-data', compact('members', 'duplicatePhones'));
    }
}

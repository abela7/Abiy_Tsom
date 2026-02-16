<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\View\View;

/**
 * Show single announcement (post) to members.
 */
class AnnouncementController extends Controller
{
    /**
     * Display the full announcement post.
     */
    public function show(Announcement $announcement): View
    {
        return view('member.announcement.show', compact('announcement'));
    }
}

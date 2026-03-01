<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Member app tour — completion and reset.
 */
class TourController extends Controller
{
    /**
     * Mark the tour as completed for the current member.
     */
    public function complete(Request $request): JsonResponse
    {
        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');
        $member->update(['tour_completed_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Reset the tour for the current member (so it will show again).
     */
    public function reset(Request $request): JsonResponse
    {
        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');
        $member->update(['tour_completed_at' => null]);

        return response()->json(['success' => true]);
    }
}

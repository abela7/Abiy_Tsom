<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\MemberChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Toggle checklist items — called via AJAX.
 * All data is scoped by member_id to ensure per-user isolation.
 */
class ChecklistController extends Controller
{
    /**
     * Toggle a single checklist item. Saves per unique member.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'daily_content_id' => ['required', 'exists:daily_contents,id'],
            'activity_id' => ['required', 'exists:activities,id'],
            'completed' => ['required', 'boolean'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $dailyContent = DailyContent::find($request->input('daily_content_id'));
        $activity = Activity::find($request->input('activity_id'));

        if (! $dailyContent || ! $activity) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 422);
        }

        if ($dailyContent->lent_season_id !== $activity->lent_season_id) {
            return response()->json(['success' => false, 'message' => 'Activity does not belong to this day.'], 422);
        }

        $checklist = MemberChecklist::updateOrCreate(
            [
                'member_id' => $member->id,
                'daily_content_id' => $request->input('daily_content_id'),
                'activity_id' => $request->input('activity_id'),
            ],
            ['completed' => $request->boolean('completed')]
        );

        return response()->json([
            'success' => true,
            'completed' => $checklist->completed,
        ]);
    }
}

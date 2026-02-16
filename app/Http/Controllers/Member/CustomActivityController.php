<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberCustomActivity;
use App\Models\MemberCustomChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CRUD for member custom activities and toggle for custom checklist.
 */
class CustomActivityController extends Controller
{
    /**
     * Store a new custom activity for the member.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $maxOrder = $member->customActivities()->max('sort_order') ?? 0;

        $activity = MemberCustomActivity::create([
            'member_id' => $member->id,
            'name' => $request->input('name'),
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'activity' => $activity,
        ]);
    }

    /**
     * Delete a custom activity. Must belong to the member.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'id' => ['required', 'integer', 'exists:member_custom_activities,id'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $activity = MemberCustomActivity::where('id', $request->input('id'))
            ->where('member_id', $member->id)
            ->first();

        if (! $activity) {
            return response()->json(['success' => false, 'message' => 'Custom activity not found or does not belong to you.'], 404);
        }

        $activity->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Toggle custom activity completion for a day.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'daily_content_id' => ['required', 'exists:daily_contents,id'],
            'member_custom_activity_id' => ['required', 'exists:member_custom_activities,id'],
            'completed' => ['required', 'boolean'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $activity = MemberCustomActivity::where('id', $request->input('member_custom_activity_id'))
            ->where('member_id', $member->id)
            ->first();

        if (! $activity) {
            return response()->json(['success' => false, 'message' => 'Custom activity not found or does not belong to you.'], 404);
        }

        $checklist = MemberCustomChecklist::updateOrCreate(
            [
                'member_id' => $member->id,
                'daily_content_id' => $request->input('daily_content_id'),
                'member_custom_activity_id' => $request->input('member_custom_activity_id'),
            ],
            ['completed' => $request->boolean('completed')]
        );

        return response()->json([
            'success' => true,
            'completed' => $checklist->completed,
        ]);
    }
}

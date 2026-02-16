<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member settings — theme, language, passcode.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->attributes->get('member');
        $customActivities = $member
            ? $member->customActivities()->orderBy('sort_order')->get()
            : collect();

        return view('member.settings', compact('member', 'customActivities'));
    }

    /**
     * Update member preferences (theme, locale).
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => ['nullable', 'string', 'in:en,am'],
            'theme' => ['nullable', 'string', 'in:light,dark'],
            'baptism_name' => ['nullable', 'string', 'min:1', 'max:255'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $updates = [];
        if ($request->filled('locale')) {
            $updates['locale'] = $request->input('locale');
            session(['locale' => $request->input('locale')]);
        }
        if ($request->filled('theme')) {
            $updates['theme'] = $request->input('theme');
        }
        if ($request->filled('baptism_name')) {
            $trimmed = trim($request->input('baptism_name'));
            if ($trimmed !== '') {
                $updates['baptism_name'] = $trimmed;
            }
        }

        if (! empty($updates)) {
            $member->update($updates);
        }

        return response()->json(['success' => true, 'member' => $member->fresh()]);
    }
}

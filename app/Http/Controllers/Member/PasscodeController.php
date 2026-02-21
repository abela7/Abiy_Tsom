<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\MemberSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Handles member passcode lock/unlock.
 */
class PasscodeController extends Controller
{
    /**
     * Lock the app by clearing the passcode-unlocked session state.
     */
    public function lock(Request $request): RedirectResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('member');

        if ($member && $member->passcode_enabled) {
            session()->forget("member_unlocked_{$member->id}");
        }

        return redirect()->route('member.passcode');
    }

    /**
     * Show the passcode entry screen.
     */
    public function show(): View
    {
        return view('member.passcode');
    }

    /**
     * Verify the passcode for the authenticated member session.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'passcode' => ['required', 'string'],
        ]);

        /** @var Member|null $member */
        $member = $request->attributes->get('member');

        if (! $member || ! $member->passcode_enabled) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 400);
        }

        if (Hash::check($request->input('passcode'), (string) $member->passcode)) {
            session(["member_unlocked_{$member->id}" => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Incorrect passcode.'], 401);
    }

    /**
     * Set or update the passcode for the authenticated member session.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'passcode' => ['nullable', 'string', 'min:4', 'max:6'],
            'enabled' => ['required', 'boolean'],
        ]);

        /** @var Member|null $member */
        $member = $request->attributes->get('member');

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        if ($request->boolean('enabled') && $request->filled('passcode')) {
            $member->update([
                'passcode' => Hash::make((string) $request->input('passcode')),
                'passcode_enabled' => true,
            ]);
        } else {
            $member->update([
                'passcode' => null,
                'passcode_enabled' => false,
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reset app access for this member on this device.
     */
    public function reset(Request $request, MemberSessionService $sessions): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('member');

        if ($member) {
            $sessions->revokeAllMemberSessions($member, releaseTrustedDevice: true);
            session()->forget("member_unlocked_{$member->id}");
        }

        $sessions->revokeCurrentSession($request);
        $sessions->forgetCookies();

        return response()->json(['success' => true]);
    }
}

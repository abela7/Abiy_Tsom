<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
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
     * Lock the app by clearing the session and redirecting to passcode screen.
     */
    public function lock(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        $member = $token ? Member::where('token', $token)->first() : null;

        if ($member && $member->passcode_enabled) {
            session()->forget("member_unlocked_{$member->id}");
        }

        return redirect()->route('member.passcode', ['token' => $token]);
    }

    /**
     * Show the passcode entry screen.
     */
    public function show(Request $request): View
    {
        return view('member.passcode');
    }

    /**
     * Verify the passcode.
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'passcode' => ['required', 'string'],
        ]);

        $member = Member::where('token', $request->input('token'))->first();

        if (! $member || ! $member->passcode_enabled) {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 400);
        }

        if (Hash::check($request->input('passcode'), $member->passcode)) {
            session(["member_unlocked_{$member->id}" => true]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Incorrect passcode.'], 401);
    }

    /**
     * Set or update the passcode.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'passcode' => ['nullable', 'string', 'min:4', 'max:6'],
            'enabled' => ['required', 'boolean'],
        ]);

        $member = Member::where('token', $request->input('token'))->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        if ($request->boolean('enabled') && $request->filled('passcode')) {
            $member->update([
                'passcode' => Hash::make($request->input('passcode')),
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
}

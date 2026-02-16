<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handles member registration (just baptism name) and identification.
 */
class OnboardingController extends Controller
{
    /**
     * Show the welcome / onboarding page.
     */
    public function welcome(): View
    {
        $season = LentSeason::active();

        return view('member.welcome', compact('season'));
    }

    /**
     * Register a new member — returns a unique token.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'baptism_name' => ['required', 'string', 'max:255'],
        ]);

        $token = Str::random(64);
        while (Member::where('token', $token)->exists()) {
            $token = Str::random(64);
        }

        $member = Member::create([
            'baptism_name' => $validated['baptism_name'],
            'token' => $token,
            'locale' => app()->getLocale(),
            'theme' => 'light',
        ]);

        return response()->json([
            'success' => true,
            'token' => $member->token,
            'member' => [
                'id' => $member->id,
                'baptism_name' => $member->baptism_name,
            ],
        ]);
    }

    /**
     * Identify an existing member by their token.
     */
    public function identify(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        $member = Member::where('token', $request->input('token'))->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'baptism_name' => $member->baptism_name,
                'passcode_enabled' => $member->passcode_enabled,
                'locale' => $member->locale,
                'theme' => $member->theme,
            ],
        ]);
    }
}

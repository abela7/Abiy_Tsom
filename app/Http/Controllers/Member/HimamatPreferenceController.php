<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\MemberHimamatPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HimamatPreferenceController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'intro_enabled' => ['nullable', 'boolean'],
            'third_enabled' => ['nullable', 'boolean'],
            'sixth_enabled' => ['nullable', 'boolean'],
            'ninth_enabled' => ['nullable', 'boolean'],
            'eleventh_enabled' => ['nullable', 'boolean'],
        ]);

        $member = $request->attributes->get('member');
        $season = LentSeason::active();

        if (! $member || ! $season) {
            return response()->json([
                'success' => false,
                'message' => __('app.himamat_unavailable_title'),
            ], 422);
        }

        $preferences = MemberHimamatPreference::query()->firstOrCreate(
            [
                'member_id' => $member->id,
                'lent_season_id' => $season->id,
            ],
            MemberHimamatPreference::defaultValues()
        );

        $updates = [];
        foreach (array_keys(MemberHimamatPreference::defaultValues()) as $key) {
            if ($request->exists($key)) {
                $updates[$key] = $request->boolean($key);
            }
        }

        if ($updates !== []) {
            $preferences->update($updates);
        }

        return response()->json([
            'success' => true,
            'message' => __('app.himamat_preferences_saved'),
            'preferences' => $preferences->fresh(),
        ]);
    }
}

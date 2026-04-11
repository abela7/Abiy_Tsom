<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberFeedback;
use App\Models\Translation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FasikaController extends Controller
{
    public function show(Request $request): View
    {
        $member = $this->resolveMember($request);

        if ($member) {
            $locale = $member->locale ?? 'en';
            if (in_array($locale, ['en', 'am'], true)) {
                app()->setLocale($locale);
                Translation::loadFromDb($locale);
            }
        }

        $season = LentSeason::active();
        $daysCompleted = 0;
        $totalDays = 55;
        $surveyToken = null;

        if ($season) {
            $totalDays = $season->total_days ?? 55;

            if ($member) {
                $daysCompleted = DailyContent::where('lent_season_id', $season->id)
                    ->where('is_published', true)
                    ->where('date', '<=', now())
                    ->count();

                $feedback = MemberFeedback::where('member_id', $member->id)->first();
                if ($feedback && $feedback->status !== 'submitted') {
                    $surveyToken = $feedback->token;
                }
            }
        }

        return view('member.fasika', [
            'currentMember' => $member,
            'member'        => $member,
            'daysCompleted' => min($daysCompleted, $totalDays),
            'totalDays'     => $totalDays,
            'surveyToken'   => $surveyToken,
            'year'          => now()->year,
        ]);
    }

    private function resolveMember(Request $request): ?Member
    {
        // Try session-based member
        $memberId = session('member_id');
        if ($memberId) {
            return Member::find($memberId);
        }

        return null;
    }
}

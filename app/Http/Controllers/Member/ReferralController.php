<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\ReferralClick;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    private const COOKIE_NAME = 'ref';
    private const COOKIE_DAYS = 30;

    /**
     * Track a referral click, set attribution cookie, and redirect to homepage.
     */
    public function track(Request $request, string $code): RedirectResponse
    {
        $affiliate = Member::where('referral_code', $code)->first();

        if (! $affiliate) {
            return redirect('/');
        }

        $visitorHash = hash('sha256', ($request->ip() ?? '') . '|' . ($request->userAgent() ?? ''));

        $isUnique = ! ReferralClick::where('member_id', $affiliate->id)
            ->where('visitor_hash', $visitorHash)
            ->exists();

        ReferralClick::create([
            'member_id'    => $affiliate->id,
            'visitor_hash' => $visitorHash,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent() ? mb_substr($request->userAgent(), 0, 512) : null,
            'referer'      => $request->header('referer') ? mb_substr($request->header('referer'), 0, 1024) : null,
            'is_unique'    => $isUnique,
            'created_at'   => now(),
        ]);

        $minutes = self::COOKIE_DAYS * 24 * 60;

        $cookie = cookie(
            self::COOKIE_NAME,
            $affiliate->referral_code,
            $minutes,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        return redirect('/')->withCookie($cookie);
    }
}

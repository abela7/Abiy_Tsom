<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberSession;
use App\Services\MemberSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the current member via secure session cookies.
 */
class IdentifyMember
{
    public function __construct(
        private readonly MemberSessionService $sessions
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $this->sessions->resolveMember($request);

        if ($member) {
            $request->attributes->set('member', $member);
            view()->share('currentMember', $member);

            // Track activity (IP, last active, user agent)
            $this->trackActivity($member, $request);

            $urlLang = $request->query('lang');
            $locale = in_array($urlLang, ['en', 'am'], true)
                ? $urlLang
                : ($member->locale && in_array($member->locale, ['en', 'am'], true) ? $member->locale : null);
            if ($locale) {
                app()->setLocale($locale);
                \App\Models\Translation::loadFromDb($locale);
            }

            return $next($request);
        }

        // Clear stale cookies so the browser stops sending dead session tokens
        // on every subsequent request (avoids repeated wasted DB lookups).
        $hasSessionCookie = is_string($request->cookie(MemberSessionService::SESSION_COOKIE))
            && trim((string) $request->cookie(MemberSessionService::SESSION_COOKIE)) !== '';

        if ($hasSessionCookie) {
            $this->sessions->forgetCookies();
        }

        if (! $request->is('/', 'member/register', 'member/identify', 'member/reset', 'api/*')) {
            return redirect('/');
        }

        return $next($request);
    }

    private function trackActivity(Member $member, Request $request): void
    {
        $ip = (string) $request->ip();
        $userAgent = $request->userAgent();
        $deviceHash = hash('sha256', ($member->token ?? $member->id) . '|' . $ip);

        $session = MemberSession::where('member_id', $member->id)
            ->where('device_hash', $deviceHash)
            ->whereNull('revoked_at')
            ->first();

        if ($session) {
            if ($session->last_used_at && $session->last_used_at->diffInSeconds(now()) < 60) {
                return;
            }
            $session->forceFill([
                'last_used_at' => now(),
                'ip_address' => $ip,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
            ])->save();
        } else {
            MemberSession::create([
                'member_id' => $member->id,
                'token_hash' => hash('sha256', $member->token ?? ''),
                'device_hash' => $deviceHash,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
                'last_used_at' => now(),
                'expires_at' => now()->addDays(120),
            ]);
        }
    }
}

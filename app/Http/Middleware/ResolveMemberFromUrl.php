<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Models\MemberSession;
use App\Models\Translation;
use App\Services\MemberSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current member from the {token} route parameter.
 *
 * This replaces cookie/session-based member identification with a
 * stateless token-in-URL approach that works on every device and browser.
 * Also tracks activity (IP, last active, user agent) in member_sessions.
 */
class ResolveMemberFromUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (! is_string($token) || ! preg_match('/^[A-Za-z0-9]{64}$/', $token)) {
            return $this->unauthenticated($request);
        }

        $member = Member::where('token', $token)->first();

        if (! $member) {
            return $this->unauthenticated($request);
        }

        $request->attributes->set('member', $member);
        view()->share('currentMember', $member);

        // Establish a cookie-based session so the member is recognised
        // when they later visit "/" (the root / landing page) without
        // the token in the URL.
        $this->ensureCookieSession($member, $request);

        // Track activity: create or update a session keyed by token + IP.
        $this->trackActivity($member, $request);

        // Log page view (throttled — only if no log in last 5 minutes for same URL path)
        $this->logPageView($member, $request);

        // Resolve locale: URL ?lang= param > session > member DB preference.
        // SetLocale middleware already ran and set locale from session.
        // Only override if there's an explicit ?lang= param or if the
        // session has no locale and the member has a DB preference.
        $urlLang = $request->query('lang');
        if (in_array($urlLang, ['en', 'am'], true)) {
            $locale = $urlLang;
            session(['locale' => $locale]);
        } elseif (! session()->has('locale') && $member->locale && in_array($member->locale, ['en', 'am'], true)) {
            $locale = $member->locale;
            session(['locale' => $locale]);
        } else {
            $locale = null; // SetLocale already handled it
        }

        if ($locale) {
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

        return $next($request);
    }

    /**
     * If the visitor doesn't already have a valid cookie session,
     * establish one so "/" redirects to their home page.
     */
    private function ensureCookieSession(Member $member, Request $request): void
    {
        $sessionService = app(MemberSessionService::class);

        // Already has a valid cookie session — nothing to do.
        if ($sessionService->resolveMember($request)) {
            return;
        }

        $sessionService->establishSession($member, $request);
    }

    /**
     * Create or update a MemberSession for activity tracking.
     * Uses a device_hash derived from token + IP so each device gets its own row.
     */
    private function trackActivity(Member $member, Request $request): void
    {
        $ip = (string) $request->ip();
        $userAgent = $request->userAgent();
        $deviceHash = hash('sha256', $member->token . '|' . $ip);

        // Use a device-specific token_hash so each device gets its own unique row.
        // This avoids the unique constraint violation when the same member
        // visits from multiple devices/IPs.
        $tokenHash = hash('sha256', $member->token . '|device|' . $deviceHash);

        try {
            $session = MemberSession::where('token_hash', $tokenHash)
                ->whereNull('revoked_at')
                ->first();

            if ($session) {
                // Throttle updates to once per minute to reduce DB writes.
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
                    'token_hash' => $tokenHash,
                    'device_hash' => $deviceHash,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
                    'last_used_at' => now(),
                    'expires_at' => now()->addDays(120),
                ]);
            }
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Race condition — another request already created it. Just update.
            MemberSession::where('token_hash', $tokenHash)
                ->update([
                    'last_used_at' => now(),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
                ]);
        }
    }

    private function logPageView(Member $member, Request $request): void
    {
        // Only log GET page requests, skip API and asset requests
        if (! $request->isMethod('GET') || $request->is('api/*')) {
            return;
        }

        $path = $request->path();

        // Throttle: skip if same member visited same path in the last 5 minutes
        $recentExists = MemberActivityLog::where('member_id', $member->id)
            ->where('action', 'page_view')
            ->where('url', 'like', '%' . $path)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if (! $recentExists) {
            MemberActivityLog::log($member, 'page_view', $path, $request);
        }
    }

    private function unauthenticated(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        return redirect('/');
    }
}

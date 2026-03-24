<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Models\MemberSession;
use App\Models\Translation;
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

        // Track activity: create or update a session keyed by token + IP.
        $this->trackActivity($member, $request);

        // Log page view (throttled — only if no log in last 5 minutes for same URL path)
        $this->logPageView($member, $request);

        // Resolve locale from URL query param or member preference.
        $urlLang = $request->query('lang');
        $locale = in_array($urlLang, ['en', 'am'], true)
            ? $urlLang
            : ($member->locale && in_array($member->locale, ['en', 'am'], true) ? $member->locale : null);

        if ($locale) {
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

        return $next($request);
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

        $session = MemberSession::where('member_id', $member->id)
            ->where('device_hash', $deviceHash)
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
                'token_hash' => hash('sha256', $member->token),
                'device_hash' => $deviceHash,
                'ip_address' => $ip,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 512) : null,
                'last_used_at' => now(),
                'expires_at' => now()->addDays(120),
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

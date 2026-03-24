<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberActivityLog;
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

            // Log page view (throttled)
            $this->logPageView($member, $request);

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

    private function logPageView(Member $member, Request $request): void
    {
        if (! $request->isMethod('GET') || $request->is('api/*')) {
            return;
        }

        $path = $request->path();

        $recentExists = MemberActivityLog::where('member_id', $member->id)
            ->where('action', 'page_view')
            ->where('url', 'like', '%' . $path)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if (! $recentExists) {
            MemberActivityLog::log($member, 'page_view', $path, $request);
        }
    }
}

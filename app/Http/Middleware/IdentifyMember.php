<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyMember
{
    public function __construct(
        private readonly MemberSessionService $sessions,
        private readonly PersistentLoginService $persistentLogins
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $this->sessions->resolveMember($request);
        $persistentPayload = null;

        if (! $member) {
            $persistentDevice = $this->persistentLogins->resolveFromRequest($request);
            if ($persistentDevice && $persistentDevice->member) {
                $member = $persistentDevice->member;
                $persistentPayload = $this->persistentLogins->currentPayload($request);
                $this->sessions->establishSession($member, $request);
                $this->persistentLogins->touch($persistentDevice, $request, $persistentPayload);
            }
        }

        if ($member) {
            if ($persistentPayload === null) {
                $persistentDevice = $this->persistentLogins->resolveFromRequest($request);
                $persistentMatches = $persistentDevice !== null
                    && $persistentDevice->member !== null
                    && $persistentDevice->member->is($member);

                if ($persistentMatches) {
                    $persistentPayload = $this->persistentLogins->currentPayload($request);
                    $this->persistentLogins->touch($persistentDevice, $request, $persistentPayload);
                } else {
                    $persistentPayload = $this->persistentLogins->issue($member, $request);
                }
            }

            $request->attributes->set('member', $member);
            $request->attributes->set('guest_access', false);
            $request->attributes->set('member_full_access', true);
            $request->attributes->set('member_access_mode', 'full');
            $request->attributes->set('member_persistent_payload', $persistentPayload);
            $request->attributes->set('member_clean_url', $request->fullUrl());

            view()->share('currentMember', $member);
            view()->share('guestAccess', false);
            view()->share('memberFullAccess', true);
            view()->share('memberAccessMode', 'full');
            view()->share('memberPersistentPayload', $persistentPayload);
            view()->share('memberCleanUrl', $request->fullUrl());

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

        $hasSessionCookie = is_string($request->cookie(MemberSessionService::SESSION_COOKIE))
            && trim((string) $request->cookie(MemberSessionService::SESSION_COOKIE)) !== '';

        if ($hasSessionCookie) {
            $this->sessions->forgetCookies();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }

        if ($request->isMethod('GET') && $request->is('member/*')) {
            return redirect()->route('member.auth.bridge', ['redirect' => $request->fullUrl()]);
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
            ->where('url', 'like', '%'.$path)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if (! $recentExists) {
            MemberActivityLog::log($member, 'page_view', $path, $request);
        }
    }
}

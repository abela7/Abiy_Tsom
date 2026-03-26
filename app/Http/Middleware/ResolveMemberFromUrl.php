<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\MemberActivityLog;
use App\Models\MemberSession;
use App\Models\Translation;
use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveMemberFromUrl
{
    public function __construct(
        private readonly MemberSessionService $sessions,
        private readonly PersistentLoginService $persistentLogins
    ) {}

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

        $sessionMember = $this->sessions->resolveMember($request);
        $persistentDevice = $this->persistentLogins->resolveFromRequest($request);

        $sessionMatches = $sessionMember !== null && $sessionMember->is($member);
        $persistentMatches = $persistentDevice !== null
            && $persistentDevice->member !== null
            && $persistentDevice->member->is($member);

        $fullAccess = $sessionMatches || $persistentMatches;
        $guestAccess = ! $fullAccess;
        $persistentPayload = null;

        if ($persistentMatches) {
            $persistentPayload = $this->persistentLogins->currentPayload($request);
            $this->persistentLogins->touch($persistentDevice, $request, $persistentPayload);
        }

        if ($sessionMatches && ! $persistentMatches) {
            $persistentPayload = $this->persistentLogins->issue($member, $request);
        }

        if ($persistentMatches && ! $sessionMatches) {
            $this->sessions->establishSession($member, $request);
        }

        if ($guestAccess && $request->is('api/m/*') && ! $request->routeIs('member.device.send-code', 'member.device.verify-code')) {
            return response()->json([
                'success' => false,
                'guest_verification_required' => true,
                'message' => __('app.member_guest_banner_body'),
            ], 403);
        }

        $cleanUrl = $this->cleanMemberUrl($request);

        $request->attributes->set('member', $member);
        $request->attributes->set('guest_access', $guestAccess);
        $request->attributes->set('member_full_access', $fullAccess);
        $request->attributes->set('member_access_mode', $guestAccess ? 'guest' : 'full');
        $request->attributes->set('member_persistent_payload', $persistentPayload);
        $request->attributes->set('member_clean_url', $cleanUrl);

        view()->share('currentMember', $member);
        view()->share('guestAccess', $guestAccess);
        view()->share('memberFullAccess', $fullAccess);
        view()->share('memberAccessMode', $guestAccess ? 'guest' : 'full');
        view()->share('memberPersistentPayload', $persistentPayload);
        view()->share('memberCleanUrl', $cleanUrl);

        $this->trackActivity($member, $request);
        $this->logPageView($member, $request);

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

    private function trackActivity(Member $member, Request $request): void
    {
        $ip = (string) $request->ip();
        $userAgent = $request->userAgent();
        $deviceHash = hash('sha256', $member->token.'|'.$ip);

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

            return;
        }

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

    private function cleanMemberUrl(Request $request): string
    {
        $path = $request->path();
        $cleanPath = preg_replace('/^m\/[A-Za-z0-9]{64}/', 'member', $path) ?: 'member/home';
        $url = url('/'.$cleanPath);
        $query = $request->getQueryString();

        return $query ? ($url.'?'.$query) : $url;
    }
}

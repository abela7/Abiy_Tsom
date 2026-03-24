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
 * Resolves member from secure session for API routes.
 */
class EnsureMemberFromToken
{
    public function __construct(
        private readonly MemberSessionService $sessions
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $this->sessions->resolveMember($request);

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $request->attributes->set('member', $member);

        // Track activity (IP, last active, user agent)
        $this->trackActivity($member, $request);

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

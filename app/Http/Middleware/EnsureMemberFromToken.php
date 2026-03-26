<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberFromToken
{
    public function __construct(
        private readonly MemberSessionService $sessions,
        private readonly PersistentLoginService $persistentLogins
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $member = $this->sessions->resolveMember($request);

        if (! $member) {
            $persistentDevice = $this->persistentLogins->resolveFromRequest($request);
            if ($persistentDevice && $persistentDevice->member) {
                $member = $persistentDevice->member;
                $this->sessions->establishSession($member, $request);
                $this->persistentLogins->touch(
                    $persistentDevice,
                    $request,
                    $this->persistentLogins->currentPayload($request)
                );
            }
        }

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Authentication required.'], 401);
        }

        $request->attributes->set('member', $member);

        return $next($request);
    }
}

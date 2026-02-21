<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

        return $next($request);
    }
}

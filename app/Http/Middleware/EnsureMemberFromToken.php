<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves member from token for API routes.
 * Token is the ONLY source of member identity — ensures no cross-member data mixing.
 */
class EnsureMemberFromToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Member-Token')
            ?? $request->cookie('member_token')
            ?? $request->query('token')
            ?? (is_array($request->input('token')) ? null : $request->input('token'));

        if (! $token || ! is_string($token)) {
            return response()->json(['success' => false, 'message' => 'Token required.'], 401);
        }

        $member = Member::where('token', $token)->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        $request->attributes->set('member', $member);

        return $next($request);
    }
}

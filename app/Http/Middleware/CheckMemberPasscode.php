<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * If the member has passcode protection enabled,
 * checks that the session is unlocked before proceeding.
 */
class CheckMemberPasscode
{
    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->attributes->get('member');

        if (! $member) {
            return $next($request);
        }

        // If passcode is enabled and the session hasn't been unlocked
        if ($member->passcode_enabled && ! session("member_unlocked_{$member->id}")) {
            // Allow access to the passcode verification route
            if ($request->is('member/passcode', 'member/passcode/verify')) {
                return $next($request);
            }

            return redirect('/member/passcode');
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict admin routes to specific roles.
 */
class EnsureAdminRole
{
    /**
     * @param  list<string>  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        // Super admin can access all admin-role routes.
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        if ($roles === [] || in_array($user->role, $roles, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized for this role.');
    }
}

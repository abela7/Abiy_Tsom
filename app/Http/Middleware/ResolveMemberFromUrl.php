<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\Translation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the current member from the {token} route parameter.
 *
 * This replaces cookie/session-based member identification with a
 * stateless token-in-URL approach that works on every device and browser.
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

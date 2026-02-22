<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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

            // Apply member locale: explicit URL lang wins, else stored preference.
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

        // No valid member - redirect to onboarding.
        if (! $request->is('/', 'member/register', 'member/identify', 'api/*')) {
            return redirect('/');
        }

        return $next($request);
    }
}

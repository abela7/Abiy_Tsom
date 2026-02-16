<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifies the current member via their token (sent as header or cookie).
 * If no member found, redirects to the welcome/registration page.
 */
class IdentifyMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Member-Token')
            ?? $request->cookie('member_token')
            ?? $request->query('token');

        if ($token) {
            $member = Member::where('token', $token)->first();

            if ($member) {
                // Share member with the entire request lifecycle
                $request->attributes->set('member', $member);
                view()->share('currentMember', $member);

                // Apply member's locale: prefer explicit ?lang= from URL, else member's stored preference
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
        }

        // No valid member — redirect to onboarding
        if (! $request->is('/', 'member/register', 'member/identify', 'api/*')) {
            return redirect('/');
        }

        return $next($request);
    }
}

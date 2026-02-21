<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Member;
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

        // One-time login via URL token, then strip token from URL.
        if (! $member && $request->isMethod('GET')) {
            $token = $request->query('token');
            if (is_string($token) && preg_match('/^[A-Za-z0-9]{20,128}$/', $token)) {
                $memberFromLink = Member::where('token', $token)->first();
                if ($memberFromLink && $this->sessions->establishSession($memberFromLink, $request)) {
                    $query = $request->query();
                    unset($query['token']);
                    $targetUrl = $request->url().(empty($query) ? '' : ('?'.http_build_query($query)));

                    return redirect()->to($targetUrl);
                }
            }
        }

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

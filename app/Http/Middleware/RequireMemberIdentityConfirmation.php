<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects sensitive member actions by requiring identity confirmation.
 *
 * The member must confirm their phone or email before accessing settings,
 * deleting their account, or managing data. Confirmation is cached in the
 * session for 30 minutes.
 */
class RequireMemberIdentityConfirmation
{
    private const SESSION_KEY = 'member_identity_confirmed_at';

    private const LIFETIME_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $member = $request->attributes->get('member');

        if (! $member) {
            return $this->denyAccess($request);
        }

        // Skip if the member has no phone and no email — nothing to confirm against.
        if (empty($member->whatsapp_phone) && empty($member->email)) {
            return $next($request);
        }

        // Check if identity was recently confirmed in this session.
        $confirmedAt = session(self::SESSION_KEY);
        if ($confirmedAt && now()->diffInMinutes($confirmedAt) < self::LIFETIME_MINUTES) {
            return $next($request);
        }

        // For API requests, check the confirm_identity field inline.
        if ($request->expectsJson() || $request->is('api/*')) {
            $input = $request->input('confirm_identity');

            if ($input && $this->matchesIdentity($member, (string) $input)) {
                session([self::SESSION_KEY => now()]);

                return $next($request);
            }

            return response()->json([
                'success' => false,
                'identity_required' => true,
                'message' => __('app.identity_confirmation_required'),
            ], 403);
        }

        // For web requests (settings page), the view handles the gate UI.
        return $next($request);
    }

    private function matchesIdentity($member, string $input): bool
    {
        $input = mb_strtolower(trim($input));

        // Match full phone number.
        if ($member->whatsapp_phone && mb_strtolower($member->whatsapp_phone) === $input) {
            return true;
        }

        // Match email.
        if ($member->email && mb_strtolower($member->email) === $input) {
            return true;
        }

        return false;
    }

    private function denyAccess(Request $request): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        return redirect('/');
    }

    /**
     * Static helper: check if the current session has confirmed identity.
     */
    public static function isConfirmed(): bool
    {
        $confirmedAt = session(self::SESSION_KEY);

        return $confirmedAt && now()->diffInMinutes($confirmedAt) < self::LIFETIME_MINUTES;
    }

    /**
     * Static helper: confirm identity in the current session.
     */
    public static function confirm(): void
    {
        session([self::SESSION_KEY => now()]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects sensitive member write actions by requiring identity confirmation.
 *
 * Three ways to pass:
 * 1. Trusted device cookie (long-lived, user opted in)
 * 2. Session confirmation (30 min after confirming)
 * 3. Inline confirm_identity field in the request
 */
class RequireMemberIdentityConfirmation
{
    private const SESSION_KEY = 'member_identity_confirmed_at';

    private const LIFETIME_MINUTES = 30;

    private const COOKIE_NAME = 'trusted_device';

    /** Cookie lasts 1 year. */
    private const COOKIE_LIFETIME_MINUTES = 525600;

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

        // 1. Check trusted device cookie.
        if ($this->isTrustedDevice($request, $member)) {
            return $next($request);
        }

        // 2. Check session confirmation.
        $confirmedAt = session(self::SESSION_KEY);
        if ($confirmedAt && now()->diffInMinutes($confirmedAt) < self::LIFETIME_MINUTES) {
            return $next($request);
        }

        // 3. For API requests, check inline confirm_identity field.
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

        return $next($request);
    }

    private function isTrustedDevice(Request $request, $member): bool
    {
        $cookie = $request->cookie(self::COOKIE_NAME);
        if (! $cookie) {
            return false;
        }

        try {
            $payload = Crypt::decrypt($cookie);

            return is_array($payload)
                && ($payload['member_id'] ?? null) === $member->id;
        } catch (\Throwable) {
            return false;
        }
    }

    private function matchesIdentity($member, string $input): bool
    {
        $input = mb_strtolower(trim($input));

        if ($member->whatsapp_phone) {
            $storedPhone = mb_strtolower($member->whatsapp_phone);
            // Exact match
            if ($storedPhone === $input) {
                return true;
            }
            // Normalize user input (handles 07..., +447..., 447..., etc.)
            $normalizedInput = normalizeUkWhatsAppPhone($input);
            if ($normalizedInput && mb_strtolower($normalizedInput) === $storedPhone) {
                return true;
            }
        }

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
     * Check if the current session or device is confirmed.
     */
    public static function isConfirmed(): bool
    {
        $confirmedAt = session(self::SESSION_KEY);

        return $confirmedAt && now()->diffInMinutes($confirmedAt) < self::LIFETIME_MINUTES;
    }

    /**
     * Confirm identity in the current session.
     */
    public static function confirm(): void
    {
        session([self::SESSION_KEY => now()]);
    }

    /**
     * Create a trusted device cookie for the given member.
     */
    public static function makeTrustedCookie($member): \Symfony\Component\HttpFoundation\Cookie
    {
        $payload = Crypt::encrypt([
            'member_id' => $member->id,
            'created_at' => now()->toIso8601String(),
        ]);

        return cookie(
            self::COOKIE_NAME,
            $payload,
            self::COOKIE_LIFETIME_MINUTES,
            '/',
            null,
            true,  // secure
            true,  // httpOnly
            false,  // raw
            'Lax'   // sameSite
        );
    }
}

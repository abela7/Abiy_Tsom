<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\MemberSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class MemberSessionService
{
    public const SESSION_COOKIE = 'member_session';

    public const DEVICE_COOKIE = 'member_device';

    private const SESSION_TTL_DAYS = 120;

    public function resolveMember(Request $request): ?Member
    {
        $sessionToken = $this->normalizeCookieValue($request->cookie(self::SESSION_COOKIE), 40);
        $deviceId = $this->normalizeCookieValue($request->cookie(self::DEVICE_COOKIE), 20);
        if (! $sessionToken || ! $deviceId) {
            return null;
        }

        $session = MemberSession::with('member')
            ->where('token_hash', hash('sha256', $sessionToken))
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $session || ! $session->member) {
            return null;
        }

        $deviceHash = hash('sha256', $deviceId);
        if (! hash_equals($session->device_hash, $deviceHash)) {
            return null;
        }

        // Each MemberSession row is already bound to its own device_hash.
        // No global trusted_device_hash check — members may use multiple
        // browsers (Safari, WhatsApp in-app, Chrome) without losing access.

        $session->forceFill([
            'last_used_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $this->normalizeUserAgent($request->userAgent()),
        ])->save();

        return $session->member;
    }

    /**
     * Create a new session for the member on the current device.
     * Only revokes existing sessions for the same device — other devices
     * (e.g. Safari while opening a WhatsApp link) keep their sessions.
     */
    public function establishSession(Member $member, Request $request): bool
    {
        $deviceId = $this->normalizeCookieValue($request->cookie(self::DEVICE_COOKIE), 20) ?? Str::random(80);
        $deviceHash = hash('sha256', $deviceId);

        // Revoke any existing session for THIS device only (not all devices).
        // This allows the member to stay logged in on Safari while also
        // opening a WhatsApp link in the in-app browser without losing
        // their Safari session.
        MemberSession::where('member_id', $member->id)
            ->where('device_hash', $deviceHash)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $plainToken = Str::random(80);

        MemberSession::create([
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $plainToken),
            'device_hash' => $deviceHash,
            'ip_address' => $request->ip(),
            'user_agent' => $this->normalizeUserAgent($request->userAgent()),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::SESSION_TTL_DAYS),
        ]);

        $minutes = self::SESSION_TTL_DAYS * 24 * 60;
        $secure = $request->isSecure() || app()->environment('production');

        Cookie::queue(cookie(
            self::DEVICE_COOKIE,
            $deviceId,
            $minutes,
            '/',
            null,
            $secure,
            true,
            false,
            'lax'
        ));

        Cookie::queue(cookie(
            self::SESSION_COOKIE,
            $plainToken,
            $minutes,
            '/',
            null,
            $secure,
            true,
            false,
            'lax'
        ));

        return true;
    }

    public function revokeCurrentSession(Request $request): void
    {
        $sessionToken = $this->normalizeCookieValue($request->cookie(self::SESSION_COOKIE), 40);
        if (! $sessionToken) {
            return;
        }

        MemberSession::where('token_hash', hash('sha256', $sessionToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function revokeAllMemberSessions(Member $member, bool $releaseTrustedDevice = false): void
    {
        MemberSession::where('member_id', $member->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        if ($releaseTrustedDevice) {
            $member->forceFill([
                'trusted_device_hash' => null,
            ])->save();
        }
    }

    public function forgetCookies(): void
    {
        Cookie::queue(Cookie::forget(self::SESSION_COOKIE));
        Cookie::queue(Cookie::forget(self::DEVICE_COOKIE));
        Cookie::queue(Cookie::forget('member_token'));
    }

    private function normalizeCookieValue(mixed $value, int $minLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);
        if (strlen($normalized) < $minLength || ! preg_match('/^[A-Za-z0-9]+$/', $normalized)) {
            return null;
        }

        return $normalized;
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 512);
    }
}

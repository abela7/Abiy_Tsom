<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\MemberPersistentDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class PersistentLoginService
{
    public const COOKIE_NAME = 'member_persistent';

    public const STORAGE_KEY = 'abiy_tsom_member_persistent_v1';

    private const SELECTOR_LENGTH = 24;

    private const TOKEN_LENGTH = 64;

    public function resolveFromRequest(Request $request): ?MemberPersistentDevice
    {
        return $this->resolvePayload($request->cookie(self::COOKIE_NAME));
    }

    public function resolvePayload(mixed $payload): ?MemberPersistentDevice
    {
        [$selector, $token] = $this->normalizePayload($payload) ?? [null, null];
        if (! $selector || ! $token) {
            return null;
        }

        $device = MemberPersistentDevice::with('member')
            ->where('selector', $selector)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $device || ! $device->member) {
            return null;
        }

        if (! hash_equals($device->token_hash, hash('sha256', $token))) {
            return null;
        }

        return $device;
    }

    public function currentPayload(Request $request): ?string
    {
        $payload = $request->cookie(self::COOKIE_NAME);

        return $this->normalizePayload($payload) ? trim((string) $payload) : null;
    }

    public function issue(Member $member, Request $request, ?MemberPersistentDevice $replace = null): string
    {
        if ($replace) {
            $replace->forceFill([
                'revoked_at' => now(),
            ])->save();
        }

        $selector = Str::random(self::SELECTOR_LENGTH);
        $token = Str::random(self::TOKEN_LENGTH);
        $payload = $selector.'.'.$token;

        MemberPersistentDevice::create([
            'member_id' => $member->id,
            'selector' => $selector,
            'token_hash' => hash('sha256', $token),
            'device_hash' => hash('sha256', $this->deviceFingerprint($request)),
            'ip_address' => $request->ip(),
            'user_agent' => $this->normalizeUserAgent($request->userAgent()),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($this->ttlDays()),
        ]);

        $this->queueCookie($payload, $request);

        return $payload;
    }

    public function touch(MemberPersistentDevice $device, Request $request, ?string $payload = null): void
    {
        $lastUsedAt = $device->last_used_at;
        if ($lastUsedAt && $lastUsedAt->diffInHours(now()) < 24) {
            if ($payload) {
                $this->queueCookie($payload, $request);
            }

            return;
        }

        $device->forceFill([
            'device_hash' => hash('sha256', $this->deviceFingerprint($request)),
            'ip_address' => $request->ip(),
            'user_agent' => $this->normalizeUserAgent($request->userAgent()),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($this->ttlDays()),
        ])->save();

        if ($payload) {
            $this->queueCookie($payload, $request);
        }
    }

    public function forget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE_NAME));
    }

    private function queueCookie(string $payload, Request $request): void
    {
        $minutes = $this->ttlDays() * 24 * 60;
        $secure = $request->isSecure() || app()->environment('production');

        Cookie::queue(cookie(
            self::COOKIE_NAME,
            $payload,
            $minutes,
            '/',
            null,
            $secure,
            true,
            false,
            'lax'
        ));
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function normalizePayload(mixed $payload): ?array
    {
        if (! is_string($payload)) {
            return null;
        }

        $normalized = trim($payload);
        if (! preg_match('/^([A-Za-z0-9]{24})\.([A-Za-z0-9]{64})$/', $normalized, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    private function ttlDays(): int
    {
        return max(30, (int) config('session.member_persistent_days', 365));
    }

    private function deviceFingerprint(Request $request): string
    {
        $userAgent = $this->normalizeUserAgent($request->userAgent()) ?? 'unknown';
        $language = mb_substr((string) $request->header('Accept-Language', ''), 0, 64);
        $platform = mb_substr((string) $request->header('Sec-CH-UA-Platform', ''), 0, 64);

        return mb_substr($userAgent.'|'.$language.'|'.$platform, 0, 512);
    }

    private function normalizeUserAgent(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || trim($userAgent) === '') {
            return null;
        }

        return mb_substr($userAgent, 0, 512);
    }
}

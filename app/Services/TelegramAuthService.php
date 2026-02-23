<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\TelegramAccessToken;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class TelegramAuthService
{
    public const PURPOSE_MEMBER_ACCESS = 'member_access';
    public const PURPOSE_ADMIN_ACCESS = 'admin_access';
    private const CODE_LENGTH = 32;

    private const DEFAULT_TTL_MINUTES = [
        self::PURPOSE_MEMBER_ACCESS => 1440, // 24h
        self::PURPOSE_ADMIN_ACCESS => 15, // 15 minutes
    ];

    public function createCode(
        Model $actor,
        string $purpose,
        ?string $redirectTo = null,
        ?int $ttlMinutes = null
    ): string {
        $this->cleanupExpiredTokens();

        if (! $this->isValidPurpose($purpose)) {
            throw new \InvalidArgumentException('Invalid Telegram access purpose.');
        }

        $plainToken = Str::random(self::CODE_LENGTH);
        $ttl = $ttlMinutes !== null
            ? max(1, $ttlMinutes)
            : self::DEFAULT_TTL_MINUTES[$purpose];

        TelegramAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'purpose' => $purpose,
            'actor_type' => get_class($actor),
            'actor_id' => $actor->getKey(),
            'redirect_to' => $this->sanitizeRedirectPath($redirectTo, '/'),
            'expires_at' => CarbonImmutable::now()->addMinutes($ttl),
        ]);

        return $plainToken;
    }

    public function resolveCode(string $code, ?string $purpose = null): ?TelegramAccessToken
    {
        $this->cleanupExpiredTokens();

        $normalized = trim($code);
        if (! preg_match('/^[A-Za-z0-9]{20,128}$/', $normalized)) {
            return null;
        }

        $query = TelegramAccessToken::query()
            ->where('token_hash', hash('sha256', $normalized))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());

        if ($purpose !== null) {
            $query->where('purpose', $purpose);
        }

        $token = $query->first();
        if (! $token) {
            return null;
        }

        $consumed = TelegramAccessToken::query()
            ->whereKey($token->getKey())
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        if (! $consumed) {
            return null;
        }

        $token->forceFill(['consumed_at' => now()]);

        return $token->load('actor');
    }

    public function consumeCode(string $code, ?string $purpose = null): ?TelegramAccessToken
    {
        return $this->resolveCode($code, $purpose);
    }

    public function actorTypeFromModel(Model $actor): string
    {
        return get_class($actor);
    }

    public function isMemberToken(TelegramAccessToken $token): bool
    {
        return $token->actor_type === Member::class && $token->actor_id !== null;
    }

    public function isAdminToken(TelegramAccessToken $token): bool
    {
        return $token->actor_type === User::class && $token->actor_id !== null;
    }

    private function cleanupExpiredTokens(): void
    {
        TelegramAccessToken::query()
            ->where(function ($query): void {
                $query->where('expires_at', '<=', now())
                    ->orWhereNotNull('consumed_at');
            })
            ->delete();
    }

    private function isValidPurpose(string $purpose): bool
    {
        return in_array($purpose, [self::PURPOSE_MEMBER_ACCESS, self::PURPOSE_ADMIN_ACCESS], true);
    }

    private function isPotentialAbsoluteUrl(string $path): bool
    {
        return preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $path) === 1;
    }

    public function sanitizeRedirectPath(?string $path, string $fallback): string
    {
        if (! is_string($path) || trim($path) === '') {
            return $fallback;
        }

        $path = trim($path);
        if (str_starts_with($path, '/')) {
            return $path;
        }

        if (! $this->isPotentialAbsoluteUrl($path)) {
            return $fallback;
        }

        $parsed = parse_url($path);
        if (! is_array($parsed)) {
            return $fallback;
        }

        $normalizedPath = (string) ($parsed['path'] ?? '');
        if ($normalizedPath === '') {
            return $fallback;
        }

        if (! str_starts_with($normalizedPath, '/')) {
            return $fallback;
        }

        $host = (string) ($parsed['host'] ?? '');
        if ($host !== '' && strtolower($host) !== strtolower((string) request()->getHost())) {
            return $fallback;
        }

        $query = (string) ($parsed['query'] ?? '');
        if ($query !== '') {
            $normalizedPath .= '?'.$query;
        }

        return $normalizedPath;
    }
}

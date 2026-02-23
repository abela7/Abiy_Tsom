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
    private const WEB_APP_AUTH_MAX_AGE_SECONDS = 86400;

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

    /**
     * Create a member link code with a short display code for typing in the bot.
     * Returns [full_token, short_code].
     *
     * @return array{0: string, 1: string}
     */
    public function createMemberLinkCode(Member $member, ?string $redirectTo = null, int $ttlMinutes = 30): array
    {
        $this->cleanupExpiredTokens();

        $plainToken = Str::random(self::CODE_LENGTH);
        $shortCode = $this->generateUniqueShortCode();

        TelegramAccessToken::create([
            'token_hash' => hash('sha256', $plainToken),
            'short_code' => strtoupper($shortCode),
            'purpose' => self::PURPOSE_MEMBER_ACCESS,
            'actor_type' => Member::class,
            'actor_id' => $member->getKey(),
            'redirect_to' => $this->sanitizeRedirectPath($redirectTo, '/'),
            'expires_at' => CarbonImmutable::now()->addMinutes($ttlMinutes),
        ]);

        return [$plainToken, strtoupper($shortCode)];
    }

    /**
     * Consume a token by its short code (6–8 alphanumeric).
     */
    public function consumeByShortCode(string $shortCode): ?TelegramAccessToken
    {
        $this->cleanupExpiredTokens();

        $normalized = strtoupper(trim($shortCode));
        if (! preg_match('/^[A-Z0-9]{6,8}$/', $normalized)) {
            return null;
        }

        $token = TelegramAccessToken::query()
            ->where('short_code', $normalized)
            ->where('purpose', self::PURPOSE_MEMBER_ACCESS)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->first();

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

    private function generateUniqueShortCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxAttempts = 20;

        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            if (! TelegramAccessToken::query()->where('short_code', strtoupper($code))->exists()) {
                return $code;
            }
        }

        return strtoupper(Str::random(6));
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

    public function parseWebAppInitData(?string $initData): ?array
    {
        if (trim((string) config('services.telegram.bot_token', '')) === '') {
            return null;
        }

        $payload = (string) $initData;
        if ($payload === '') {
            return null;
        }

        parse_str($payload, $parts);
        if (! is_array($parts)) {
            return null;
        }

        if (! isset($parts['hash']) || ! is_string($parts['hash'])) {
            return null;
        }

        $hash = trim($parts['hash']);
        if ($hash === '') {
            return null;
        }

        unset($parts['hash']);

        $checkData = [];
        foreach ($parts as $key => $value) {
            if (is_array($value)) {
                $value = (string) reset($value);
            }
            if (! is_scalar($value)) {
                continue;
            }
            $checkData[(string) $key] = (string) $value;
        }

        ksort($checkData, SORT_STRING);
        $dataCheck = [];
        foreach ($checkData as $key => $value) {
            $dataCheck[] = "{$key}={$value}";
        }

        $secretKey = hash('sha256', (string) config('services.telegram.bot_token', ''), true);
        $calcHash = hash_hmac('sha256', implode("\n", $dataCheck), $secretKey);
        if (! hash_equals($hash, $calcHash)) {
            return null;
        }

        $authDate = (int) ($parts['auth_date'] ?? 0);
        if ($authDate <= 0) {
            return null;
        }

        if ((now()->timestamp - $authDate) > self::WEB_APP_AUTH_MAX_AGE_SECONDS) {
            return null;
        }

        if (! isset($parts['user']) || ! is_string($parts['user'])) {
            return null;
        }

        $userPayload = json_decode($parts['user'], true);
        if (! is_array($userPayload)) {
            return null;
        }

        $telegramUserId = isset($userPayload['id']) ? (string) $userPayload['id'] : '';
        if (! preg_match('/^[0-9]+$/', $telegramUserId)) {
            return null;
        }

        return [
            'user_id' => $telegramUserId,
            'user' => $userPayload,
            'start_param' => isset($parts['start_param']) && is_string($parts['start_param']) && trim($parts['start_param']) !== ''
                ? trim($parts['start_param'])
                : null,
            'auth_date' => $authDate,
            'raw' => $payload,
            'hash' => $hash,
        ];
    }

    public function actorFromTelegramId(string $telegramId): Member|User|null
    {
        $telegramId = trim($telegramId);
        if ($telegramId === '') {
            return null;
        }

        $member = Member::query()->where('telegram_chat_id', $telegramId)->first();
        if ($member instanceof Member) {
            return $member;
        }

        return User::query()->where('telegram_chat_id', $telegramId)->first();
    }

    public function bindActorToTelegramId(Member|User $actor, string $telegramId): void
    {
        $telegramId = trim($telegramId);
        if ($telegramId === '') {
            return;
        }

        $this->releaseTelegramChatId($telegramId);

        if ((string) $actor->telegram_chat_id === $telegramId) {
            return;
        }

        $actor->forceFill([
            'telegram_chat_id' => $telegramId,
        ])->save();
    }

    private function releaseTelegramChatId(string $telegramId): void
    {
        Member::query()
            ->where('telegram_chat_id', $telegramId)
            ->update(['telegram_chat_id' => null]);

        User::query()
            ->where('telegram_chat_id', $telegramId)
            ->update(['telegram_chat_id' => null]);
    }
}

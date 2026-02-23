<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stores transient multi-step wizard state for the Telegram bot.
 *
 * One record per (chat_id, action) pair. Records expire after 1 hour.
 * State machine is driven by the TelegramWebhookController.
 */
class TelegramBotState extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'chat_id',
        'action',
        'step',
        'data',
        'expires_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Return the active (non-expired) state for a given chat + action, or null.
     */
    public static function getActive(string $chatId, string $action): ?self
    {
        return self::query()
            ->where('chat_id', $chatId)
            ->where('action', $action)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Return ANY active state for a chat (any action), or null.
     */
    public static function getAnyActive(string $chatId): ?self
    {
        return self::query()
            ->where('chat_id', $chatId)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Create or replace an active state for the given chat + action.
     */
    public static function startFor(string $chatId, string $action, string $step, array $data = []): self
    {
        // Remove any existing state for this chat+action
        self::query()
            ->where('chat_id', $chatId)
            ->where('action', $action)
            ->delete();

        return self::create([
            'chat_id' => $chatId,
            'action' => $action,
            'step' => $step,
            'data' => $data,
            'expires_at' => now()->addHour(),
        ]);
    }

    /**
     * Advance to the next step, merging new data into the existing JSON blob.
     */
    public function advance(string $step, array $mergeData = []): void
    {
        $this->step = $step;
        $this->data = array_merge($this->data ?? [], $mergeData);
        $this->expires_at = now()->addHour();
        $this->save();
    }

    /**
     * Clear this state record.
     */
    public function clear(): void
    {
        $this->delete();
    }

    /**
     * Delete all expired states (for periodic cleanup).
     */
    public static function clearExpired(): int
    {
        return self::query()
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Read a data field with an optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return ($this->data ?? [])[$key] ?? $default;
    }
}

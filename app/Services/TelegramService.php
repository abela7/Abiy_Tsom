<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Minimal Telegram Bot API client for admin-configured outbound text messages.
 */
final class TelegramService
{
    /**
     * Check if Telegram bot token is configured.
     */
    public function isConfigured(): bool
    {
        return $this->botToken() !== '';
    }

    /**
     * Send a text message to a Telegram chat.
     */
    public function sendTextMessage(string $chatId, string $body): bool
    {
        if (! $this->isConfigured() || $chatId === '') {
            return false;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->post($this->sendMessageEndpoint(), [
                'chat_id' => trim($chatId),
                'text' => $body,
            ]);

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage failed.', [
                'status' => $response->status(),
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);

            return false;
        }

        $payload = $response->json();
        $sent = is_array($payload) ? (bool) ($payload['ok'] ?? false) : false;

        if (! $sent) {
            Log::warning('Telegram API did not confirm message delivery.', [
                'chat_id' => $chatId,
                'payload' => $payload,
            ]);
        }

        return $sent;
    }

    private function sendMessageEndpoint(): string
    {
        return sprintf('https://api.telegram.org/bot%s/sendMessage', $this->botToken());
    }

    private function botToken(): string
    {
        return trim((string) config('services.telegram.bot_token', ''));
    }
}

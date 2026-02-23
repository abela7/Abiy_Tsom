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
    public function sendTextMessage(string $chatId, string $body, array $options = []): bool
    {
        if (! $this->isConfigured() || $chatId === '') {
            return false;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->post($this->apiEndpoint('sendMessage'), array_merge([
                'chat_id' => trim($chatId),
                'text' => $body,
            ], $options));

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

    /**
     * Register bot command menu for all chat users.
     *
     * @param array<int, array{command:string,description:string}> $commands
     */
    public function setMyCommands(array $commands): bool
    {
        $payload = [
            'commands' => $commands,
        ];

        return $this->apiCall('setMyCommands', $payload);
    }

    /**
     * Configure bot default menu button to open the mini app.
     */
    public function setChatMenuButton(string $text, string $url): bool
    {
        return $this->apiCall('setChatMenuButton', [
            'menu_button' => [
                'type' => 'web_app',
                'text' => $text,
                'web_app' => [
                    'url' => $url,
                ],
            ],
        ]);
    }

    /**
     * Acknowledge callback button taps from inline keyboards.
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        return $this->apiCall('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    private function apiCall(string $method, array $payload): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->post($this->apiEndpoint($method), $payload);

        if (! $response->successful()) {
            Log::warning("Telegram API method {$method} failed.", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        }

        $payloadJson = $response->json();
        $ok = is_array($payloadJson) ? (bool) ($payloadJson['ok'] ?? false) : false;

        if (! $ok) {
            Log::warning("Telegram API method {$method} returned ok=false.", [
                'response' => $payloadJson,
            ]);
        }

        return $ok;
    }

    private function apiEndpoint(string $method): string
    {
        return sprintf('https://api.telegram.org/bot%s/%s', $this->botToken(), ltrim($method, '/'));
    }

    private function botToken(): string
    {
        return trim((string) config('services.telegram.bot_token', ''));
    }
}

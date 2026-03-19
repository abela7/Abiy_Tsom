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
     * @param  array<int, array{command:string,description:string}>  $commands
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
     * Send a text message and return the sent message's ID (or null on failure).
     */
    public function sendAndGetMessageId(string $chatId, string $body, array $options = []): ?int
    {
        if (! $this->isConfigured() || $chatId === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->post($this->apiEndpoint('sendMessage'), array_merge([
                'chat_id' => trim($chatId),
                'text' => $body,
            ], $options));

        if (! $response->successful()) {
            return null;
        }

        $payload = $response->json();

        return is_array($payload) && ($payload['ok'] ?? false)
            ? (int) data_get($payload, 'result.message_id', 0) ?: null
            : null;
    }

    /**
     * Send a photo to a Telegram chat (by URL).
     */
    public function sendPhoto(string $chatId, string $photoUrl, string $caption = '', ?string $parseMode = null): bool
    {
        if (! $this->isConfigured() || $chatId === '') {
            return false;
        }

        $payload = [
            'chat_id' => trim($chatId),
            'photo' => $photoUrl,
        ];
        if ($caption !== '') {
            $payload['caption'] = $caption;
        }
        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        return $this->apiCall('sendPhoto', $payload);
    }

    /**
     * Delete a message from a chat.
     */
    public function deleteMessage(string $chatId, int $messageId): bool
    {
        if (! $this->isConfigured() || $chatId === '' || $messageId <= 0) {
            return false;
        }

        return $this->apiCall('deleteMessage', [
            'chat_id' => trim($chatId),
            'message_id' => $messageId,
        ]);
    }

    /**
     * Edit an existing message's text and optional reply markup.
     */
    public function editMessageText(string $chatId, int $messageId, string $text, array $replyMarkup = [], ?string $parseMode = null): bool
    {
        if (! $this->isConfigured() || $chatId === '' || $messageId <= 0) {
            return false;
        }

        $payload = [
            'chat_id' => trim($chatId),
            'message_id' => $messageId,
            'text' => $text,
        ];

        if (! empty($replyMarkup)) {
            $payload['reply_markup'] = $replyMarkup;
        }
        if ($parseMode !== null) {
            $payload['parse_mode'] = $parseMode;
        }

        return $this->apiCall('editMessageText', $payload);
    }

    /**
     * Acknowledge callback button taps from inline keyboards.
     * Only include 'text' when non-empty — passing text:'' causes some Telegram
     * clients to show a "Loading..." placeholder toast unnecessarily.
     */
    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): bool
    {
        $payload = [
            'callback_query_id' => $callbackQueryId,
            'show_alert' => $showAlert,
        ];
        if ($text !== '') {
            $payload['text'] = $text;
        }

        return $this->apiCall('answerCallbackQuery', $payload);
    }

    /**
     * Download a file that was uploaded to the Telegram bot.
     *
     * @return array{contents:string,file_path:string,mime_type:string|null,extension:string|null}|null
     */
    public function downloadFile(string $fileId): ?array
    {
        if (! $this->isConfigured() || trim($fileId) === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->timeout(20)
            ->post($this->apiEndpoint('getFile'), [
                'file_id' => trim($fileId),
            ]);

        if (! $response->successful()) {
            Log::warning('Telegram getFile failed.', [
                'status' => $response->status(),
                'file_id' => $fileId,
                'response' => $response->body(),
            ]);

            return null;
        }

        $payload = $response->json();
        $filePath = is_array($payload) ? (string) data_get($payload, 'result.file_path', '') : '';
        if ($filePath === '') {
            Log::warning('Telegram getFile returned no file path.', [
                'file_id' => $fileId,
                'payload' => $payload,
            ]);

            return null;
        }

        $fileResponse = Http::timeout(30)->get($this->fileEndpoint($filePath));
        if (! $fileResponse->successful()) {
            Log::warning('Telegram file download failed.', [
                'status' => $fileResponse->status(),
                'file_id' => $fileId,
                'file_path' => $filePath,
            ]);

            return null;
        }

        return [
            'contents' => $fileResponse->body(),
            'file_path' => $filePath,
            'mime_type' => $fileResponse->header('Content-Type'),
            'extension' => pathinfo($filePath, PATHINFO_EXTENSION) ?: null,
        ];
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

    private function fileEndpoint(string $filePath): string
    {
        return sprintf('https://api.telegram.org/file/bot%s/%s', $this->botToken(), ltrim($filePath, '/'));
    }

    private function botToken(): string
    {
        return trim((string) config('services.telegram.bot_token', ''));
    }
}

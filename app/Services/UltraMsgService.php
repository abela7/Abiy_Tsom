<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lightweight UltraMsg client for sending WhatsApp text messages.
 */
final class UltraMsgService
{
    /**
     * Check if required UltraMsg credentials exist.
     */
    public function isConfigured(): bool
    {
        return $this->instanceId() !== '' && $this->token() !== '';
    }

    /**
     * Send a WhatsApp text message.
     */
    public function sendTextMessage(string $to, string $body): bool
    {
        $response = Http::asForm()
            ->acceptJson()
            ->timeout(20)
            ->post($this->messagesEndpoint(), [
                'token' => $this->token(),
                'to' => $to,
                'body' => $body,
            ]);

        if (! $response->successful()) {
            Log::warning('UltraMsg request failed.', [
                'status' => $response->status(),
                'to' => $to,
                'response' => $response->body(),
            ]);

            return false;
        }

        $payload = $response->json();
        $sent = $this->toBoolean(is_array($payload) ? ($payload['sent'] ?? null) : null);

        if (! $sent) {
            Log::warning('UltraMsg did not confirm message delivery.', [
                'to' => $to,
                'payload' => $payload,
            ]);
        }

        return $sent;
    }

    private function messagesEndpoint(): string
    {
        return sprintf(
            '%s/%s/messages/chat',
            rtrim((string) config('services.ultramsg.base_url', 'https://api.ultramsg.com'), '/'),
            $this->instanceId()
        );
    }

    private function instanceId(): string
    {
        return trim((string) config('services.ultramsg.instance_id', ''));
    }

    private function token(): string
    {
        return trim((string) config('services.ultramsg.token', ''));
    }

    private function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (! is_string($value)) {
            return false;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'ok'], true);
    }
}

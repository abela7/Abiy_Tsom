<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
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
        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post($this->messagesEndpoint(), [
                    'token' => $this->token(),
                    'to' => $to,
                    'body' => $body,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('UltraMsg connection error sending message.', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

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

    /**
     * Get current instance settings from UltraMsg.
     *
     * @return array<string, mixed>|null
     */
    public function getInstanceSettings(): ?array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->get($this->instanceSettingsEndpoint(), [
                    'token' => $this->token(),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('UltraMsg connection error getting settings.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('UltraMsg get settings failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * Update instance webhook settings on UltraMsg.
     *
     * @param  array<string, mixed>  $settings
     */
    public function updateInstanceSettings(array $settings): bool
    {
        $payload = array_merge([
            'token' => $this->token(),
        ], $settings);

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(20)
                ->post($this->instanceSettingsEndpoint(), $payload);
        } catch (ConnectionException $e) {
            Log::warning('UltraMsg connection error updating settings.', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('UltraMsg update settings failed.', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function messagesEndpoint(): string
    {
        return sprintf(
            '%s/%s/messages/chat',
            rtrim((string) config('services.ultramsg.base_url', 'https://api.ultramsg.com'), '/'),
            $this->instanceId()
        );
    }

    private function instanceSettingsEndpoint(): string
    {
        return sprintf(
            '%s/%s/instance/settings',
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

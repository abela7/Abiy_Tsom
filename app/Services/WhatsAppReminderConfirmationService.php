<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\Lang;

/**
 * Handles WhatsApp reminder opt-in confirmation workflow.
 */
final class WhatsAppReminderConfirmationService
{
    public function __construct(private readonly UltraMsgService $ultraMsg) {}

    /**
     * Send "Reply YES or NO" prompt to member.
     */
    public function sendOptInPrompt(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = Lang::get('app.whatsapp_confirmation_prompt_message', [
            'name' => $member->baptism_name,
        ], $locale);

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Send activation confirmed message.
     */
    public function sendConfirmedNotice(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = Lang::get('app.whatsapp_confirmation_activated_message', [], $locale);

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Send activation rejected message.
     */
    public function sendRejectedNotice(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = Lang::get('app.whatsapp_confirmation_rejected_message', [], $locale);

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Parse YES/NO reply from inbound text.
     *
     * @return 'yes'|'no'|null
     */
    public function parseReply(?string $text): ?string
    {
        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        $normalized = strtolower(trim($text));
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $token = explode(' ', $normalized)[0];
        $token = trim($token, " \t\n\r\0\x0B.,!?;:\"'`()[]{}");

        if (in_array($token, ['yes', 'y', 'ok', 'okay', 'confirm', 'confirmed', 'አዎ', 'እሺ'], true)) {
            return 'yes';
        }

        if (in_array($token, ['no', 'n', 'cancel', 'stop', 'reject', 'አይ'], true)) {
            return 'no';
        }

        return null;
    }

    private function memberLocale(Member $member): string
    {
        $locale = (string) ($member->whatsapp_language ?? 'en');

        return in_array($locale, ['en', 'am'], true) ? $locale : 'en';
    }
}

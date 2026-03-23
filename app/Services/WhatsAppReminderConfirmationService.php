<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Member;
use App\Models\Translation;
use Illuminate\Support\Facades\Lang;

/**
 * Handles WhatsApp reminder opt-in confirmation workflow.
 */
final class WhatsAppReminderConfirmationService
{
    public function __construct(
        private readonly UltraMsgService $ultraMsg,
        private readonly WhatsAppTemplateService $templates,
    ) {}

    /**
     * Send "Please reply YES or NO only" re-prompt after an invalid reply.
     */
    public function sendInvalidReplyPrompt(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_invalid_reply_message',
            $member,
            $this->buildWebsiteUrl($member),
            $this->telegramUrl(),
            $locale
        );

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Send "Reply YES or NO" prompt to member.
     */
    public function sendOptInPrompt(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_confirmation_prompt_message',
            $member,
            $this->buildWebsiteUrl($member),
            $this->telegramUrl(),
            $locale
        );

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Send a "Are you trying to login?" prompt on WhatsApp.
     * Reuses the YES/NO reply mechanism.
     */
    public function sendLoginPrompt(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);
        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_login_prompt_message',
            $member,
            $this->buildWebsiteUrl($member),
            $this->telegramUrl(),
            $locale
        );

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    /**
     * Send a follow-up message directing the member back to the website
     * and mentioning the Telegram bot as an alternative.
     */
    public function sendGoBackMessage(Member $member): bool
    {
        if (! $this->ultraMsg->isConfigured() || ! $member->whatsapp_phone) {
            return false;
        }

        $locale = $this->memberLocale($member);

        $websiteUrl = $this->buildWebsiteUrl($member);

        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_confirmation_go_back_message',
            $member,
            $websiteUrl,
            $this->telegramUrl(),
            $locale
        );
        $warning = $this->warningMessage($locale);
        $message = trim($message) !== ''
            ? trim($message)."\n\n".$warning
            : $warning;

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
        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_confirmation_activated_message',
            $member,
            $this->buildWebsiteUrl($member),
            $this->telegramUrl(),
            $locale
        );

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
        $message = $this->templates->renderConfirmationTemplate(
            'app.whatsapp_confirmation_rejected_message',
            $member,
            $this->buildWebsiteUrl($member),
            $this->telegramUrl(),
            $locale
        );

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

    /**
     * Build a direct token URL for WhatsApp messages.
     */
    private function buildWebsiteUrl(Member $member = null): string
    {
        $url = $member ? $member->personalUrl('/home') : url('/');

        if (! app()->environment('local')) {
            $url = preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
        }

        return $url;
    }

    private function telegramUrl(): string
    {
        $telegramUsername = ltrim(trim((string) config('services.telegram.bot_username', '')), '@');

        return $telegramUsername !== '' ? 'https://t.me/'.$telegramUsername : 'https://t.me/AbiyTsomBot';
    }

    private function warningMessage(string $locale): string
    {
        return trim(Lang::get('app.whatsapp_confirmation_link_warning', locale: $locale));
    }

    /**
     * Resolve the member's locale and ensure DB translations are loaded
     * so that Lang::get() returns admin-edited values, not lang file defaults.
     */
    private function memberLocale(Member $member): string
    {
        $locale = (string) ($member->whatsapp_language ?? 'en');
        $locale = in_array($locale, ['en', 'am'], true) ? $locale : 'en';

        Translation::loadFromDb($locale);

        return $locale;
    }
}

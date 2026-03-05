<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyContent;
use App\Models\Member;
use App\Models\Translation;
use Illuminate\Support\Facades\Lang;

/**
 * Shared template renderer for member-facing WhatsApp messages.
 *
 * Each message flow gets a single supported variable bag so template sections
 * can reuse the same placeholders safely without inventing arbitrary runtime
 * variables.
 */
final class WhatsAppTemplateService
{
    /** @var list<string> */
    public const DAILY_REMINDER_PLACEHOLDERS = [
        'name',
        'baptism_name',
        'day',
        'day_title',
        'date',
        'saint_commemoration',
        'bible_reference',
        'url',
    ];

    /** @var list<string> */
    public const CONFIRMATION_PLACEHOLDERS = [
        'name',
        'baptism_name',
        'url',
        'telegram_url',
    ];

    /**
     * @return array{locale: string, variables: array<string, string>, header: string, content: string, message: string}
     */
    public function renderDailyReminder(
        Member $member,
        DailyContent $dailyContent,
        string $url,
        ?string $locale = null
    ): array {
        $resolvedLocale = $this->normalizeLocale($locale ?? (string) ($member->whatsapp_language ?? $member->locale ?? 'en'));
        $variables = $this->dailyReminderVariables($member, $dailyContent, $url, $resolvedLocale);

        $header = $this->translate('app.whatsapp_daily_reminder_header', $variables, $resolvedLocale);
        $content = $this->translate('app.whatsapp_daily_reminder_content', $variables, $resolvedLocale);

        return [
            'locale' => $resolvedLocale,
            'variables' => $variables,
            'header' => $header,
            'content' => $content,
            'message' => $header."\n".$content,
        ];
    }

    public function renderConfirmationTemplate(
        string $translationKey,
        Member $member,
        string $url,
        string $telegramUrl,
        ?string $locale = null
    ): string {
        $resolvedLocale = $this->normalizeLocale($locale ?? (string) ($member->whatsapp_language ?? $member->locale ?? 'en'));
        $variables = $this->confirmationVariables($member, $url, $telegramUrl);

        return $this->translate($translationKey, $variables, $resolvedLocale);
    }

    /**
     * @return array<string, string>
     */
    private function dailyReminderVariables(
        Member $member,
        DailyContent $dailyContent,
        string $url,
        string $locale
    ): array {
        $name = trim((string) ($member->baptism_name ?? ''));
        $dateValue = $dailyContent->date;
        $date = $dateValue instanceof \DateTimeInterface
            ? $dateValue->format('Y-m-d')
            : trim((string) $dateValue);

        return [
            'name' => $name,
            'baptism_name' => $name,
            'day' => trim((string) $dailyContent->day_number),
            'day_title' => $this->localizedDailyValue($dailyContent, 'day_title', $locale),
            'date' => $date,
            'saint_commemoration' => $this->localizedDailyValue($dailyContent, 'sinksar_title', $locale),
            'bible_reference' => $this->localizedDailyValue($dailyContent, 'bible_reference', $locale),
            'url' => $url,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function confirmationVariables(Member $member, string $url, string $telegramUrl): array
    {
        $name = trim((string) ($member->baptism_name ?? ''));

        return [
            'name' => $name,
            'baptism_name' => $name,
            'url' => $url,
            'telegram_url' => $telegramUrl,
        ];
    }

    private function translate(string $translationKey, array $variables, string $locale): string
    {
        Translation::loadFromDb($locale);

        return Lang::get($translationKey, $variables, $locale);
    }

    private function normalizeLocale(string $locale): string
    {
        return in_array($locale, ['en', 'am'], true) ? $locale : 'en';
    }

    private function localizedDailyValue(DailyContent $dailyContent, string $baseField, string $locale): string
    {
        $preferredField = $baseField.'_'.$locale;
        $fallbackField = $baseField.'_'.$this->fallbackLocale($locale);

        $preferredValue = trim((string) ($dailyContent->{$preferredField} ?? ''));
        if ($preferredValue !== '') {
            return $preferredValue;
        }

        return trim((string) ($dailyContent->{$fallbackField} ?? ''));
    }

    private function fallbackLocale(string $locale): string
    {
        return $locale === 'am' ? 'en' : 'am';
    }
}


<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\Member;
use App\Models\Translation;
use Illuminate\Support\Facades\Lang;

class HimamatWhatsAppTemplateService
{
    /**
     * @return array{locale: string, message: string}
     */
    public function renderReminder(
        Member $member,
        HimamatDay $day,
        HimamatSlot $slot,
        string $url
    ): array {
        $locale = $this->preferredLocale($member);
        $variables = [
            'name' => trim((string) ($member->baptism_name ?? '')),
            'day_title' => localized($day, 'title', $locale) ?? '',
            'slot_header' => localized($slot, 'slot_header', $locale) ?? '',
            'reminder_header' => localized($slot, 'reminder_header', $locale) ?? '',
            'spiritual_significance' => localized($slot, 'spiritual_significance', $locale) ?? '',
            'reading_reference' => localized($slot, 'reading_reference', $locale) ?? '',
            'short_prayer' => localized($slot, 'short_prayer', $locale) ?? '',
            'prostration_count' => (string) $slot->prostration_count,
            'url' => $this->ensureHttpsUrl($url),
        ];

        $lines = array_values(array_filter([
            $variables['reminder_header'],
            $variables['spiritual_significance'],
            $variables['reading_reference'] !== ''
                ? Lang::get('app.himamat_reminder_reading_line', ['reading_reference' => $variables['reading_reference']], $locale)
                : '',
            $variables['short_prayer'] !== ''
                ? Lang::get('app.himamat_reminder_prayer_line', ['short_prayer' => $variables['short_prayer']], $locale)
                : '',
            $slot->prostration_count > 0
                ? Lang::get('app.himamat_reminder_bows_line', ['count' => $slot->prostration_count], $locale)
                : '',
            Lang::get('app.himamat_reminder_open_line', ['url' => $variables['url']], $locale),
        ], static fn (?string $line): bool => is_string($line) && trim($line) !== ''));

        return [
            'locale' => $locale,
            'message' => trim(implode("\n\n", $lines)),
        ];
    }

    private function preferredLocale(Member $member): string
    {
        $locale = (string) ($member->locale ?: $member->whatsapp_language ?: 'en');
        $locale = in_array($locale, ['en', 'am'], true) ? $locale : 'en';

        Translation::loadFromDb($locale);

        return $locale;
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

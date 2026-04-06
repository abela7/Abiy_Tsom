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
     * @var array<string, string>
     */
    private const TEMPLATE_KEYS = [
        'third' => 'app.whatsapp_himamat_third_content',
        'sixth' => 'app.whatsapp_himamat_sixth_content',
        'ninth' => 'app.whatsapp_himamat_ninth_content',
        'eleventh' => 'app.whatsapp_himamat_eleventh_content',
    ];

    /**
     * @return array{locale: string, message: string}
     */
    public function renderReminder(
        Member $member,
        HimamatDay $day,
        HimamatSlot $slot,
        string $url,
        ?string $locale = null
    ): array {
        $locale = $this->preferredLocale($member, $locale);
        $slotHeader = trim((string) (localized($slot, 'slot_header', $locale) ?? ''));
        $reminderHeader = trim((string) (localized($slot, 'reminder_header', $locale) ?? ''));
        $reminderContent = trim((string) (localized($slot, 'reminder_content', $locale) ?? ''));

        $variables = [
            'name' => trim((string) ($member->baptism_name ?? '')),
            'day_title' => localized($day, 'title', $locale) ?? '',
            'slot_header' => $slotHeader,
            'reminder_header' => $slotHeader !== '' ? $slotHeader : $reminderHeader,
            'reminder_content' => $reminderContent,
            'url' => $this->ensureHttpsUrl($url),
        ];

        $templateKey = $this->templateKeyForSlot((string) $slot->slot_key);
        $message = '';

        if ($templateKey !== null) {
            $template = trim((string) Lang::get($templateKey, [], $locale));

            if ($this->templateLooksDynamic($template)) {
                $message = trim((string) Lang::get($templateKey, $variables, $locale));
            }
        }

        if ($message === '' || $message === $templateKey) {
            $lines = array_values(array_filter([
                $variables['reminder_header'],
                $variables['reminder_content'] !== ''
                    ? $variables['reminder_content']
                    : '',
                Lang::get('app.himamat_slot_reminder_open_line', ['url' => $variables['url']], $locale),
            ], static fn (?string $line): bool => is_string($line) && trim($line) !== ''));

            $message = trim(implode("\n\n", $lines));
        }

        if ($variables['reminder_header'] !== '' && ! str_contains($message, $variables['reminder_header'])) {
            $message = trim($variables['reminder_header']."\n\n".$message);
        }

        if ($variables['reminder_content'] !== '' && ! str_contains($message, $variables['reminder_content'])) {
            $cta = Lang::get('app.himamat_slot_reminder_open_line', ['url' => $variables['url']], $locale);

            if ($cta !== '' && str_contains($message, $cta)) {
                $message = str_replace($cta, trim($variables['reminder_content'])."\n\n".$cta, $message);
            } else {
                $message = trim($message."\n\n".$variables['reminder_content']);
            }
        }

        $cta = Lang::get('app.himamat_slot_reminder_open_line', ['url' => $variables['url']], $locale);
        if ($cta !== '' && ! str_contains($message, $variables['url'])) {
            $message = trim($message."\n\n".$cta);
        }

        return [
            'locale' => $locale,
            'message' => trim($message),
        ];
    }

    private function preferredLocale(Member $member, ?string $locale = null): string
    {
        $locale = trim((string) ($locale ?? ''));
        if ($locale === '') {
            $locale = (string) ($member->locale ?: $member->whatsapp_language ?: 'en');
        }

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

    private function templateKeyForSlot(string $slotKey): ?string
    {
        return self::TEMPLATE_KEYS[$slotKey] ?? null;
    }

    private function templateLooksDynamic(string $template): bool
    {
        if ($template === '') {
            return false;
        }

        if (! str_contains($template, ':url')) {
            return false;
        }

        return str_contains($template, ':reminder_header')
            || str_contains($template, ':reminder_content');
    }
}

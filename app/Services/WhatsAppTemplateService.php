<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyContent;
use App\Models\Member;
use App\Models\Translation;
use Carbon\Carbon;
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
    /** @var array<int, string> */
    private const GREGORIAN_MONTH_NAMES_EN = [
        1 => 'January',
        2 => 'February',
        3 => 'March',
        4 => 'April',
        5 => 'May',
        6 => 'June',
        7 => 'July',
        8 => 'August',
        9 => 'September',
        10 => 'October',
        11 => 'November',
        12 => 'December',
    ];

    /** @var array<int, string> */
    private const GREGORIAN_MONTH_NAMES_AM = [
        1 => 'ጃንዩወሪ',
        2 => 'ፌብሩወሪ',
        3 => 'ማርች',
        4 => 'ኤፕሪል',
        5 => 'ሜይ',
        6 => 'ጁን',
        7 => 'ጁላይ',
        8 => 'ኦገስት',
        9 => 'ሴፕቴምበር',
        10 => 'ኦክቶበር',
        11 => 'ኖቬምበር',
        12 => 'ዲሴምበር',
    ];

    public function __construct(
        private readonly EthiopianCalendarService $ethiopianCalendarService
    ) {
    }

    /** @var list<string> */
    public const DAILY_REMINDER_PLACEHOLDERS = [
        'name',
        'baptism_name',
        'day',
        'day_title',
        'date',
        'gregorian_date',
        'ethiopian_date',
        'saint_commemoration',
        'annual_commemorations',
        'annual_commemorations_bullets',
        'yearly_commemorations',
        'yearly_commemorations_bullets',
        'monthly_commemorations',
        'monthly_commemorations_bullets',
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
        $dateInfo = $this->dailyDateInfo($dateValue, $locale);
        $annualCelebrationNames = $this->localizedCelebrationNames($dateInfo['annual_celebrations'] ?? [], $locale);
        $monthlyCelebrationNames = $this->localizedCelebrationNames($dateInfo['monthly_celebrations'] ?? [], $locale);
        $annualCommemorations = $this->formatInlineCelebrationList($annualCelebrationNames);
        $monthlyCommemorations = $this->formatInlineCelebrationList($monthlyCelebrationNames);

        return [
            'name' => $name,
            'baptism_name' => $name,
            'day' => trim((string) $dailyContent->day_number),
            'day_title' => $this->localizedDailyValue($dailyContent, 'day_title', $locale),
            'date' => $date,
            'gregorian_date' => $this->formatGregorianMonthDay($dateValue, $locale),
            'ethiopian_date' => $this->formatEthiopianMonthDay($dateInfo['ethiopian_date'] ?? [], $locale),
            'saint_commemoration' => $this->localizedDailyValue($dailyContent, 'sinksar_title', $locale),
            'annual_commemorations' => $annualCommemorations,
            'annual_commemorations_bullets' => $this->formatBulletCelebrationList($annualCelebrationNames),
            'yearly_commemorations' => $annualCommemorations,
            'yearly_commemorations_bullets' => $this->formatBulletCelebrationList($annualCelebrationNames),
            'monthly_commemorations' => $monthlyCommemorations,
            'monthly_commemorations_bullets' => $this->formatBulletCelebrationList($monthlyCelebrationNames),
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

    /**
     * @return array{ethiopian_date?: array<string, mixed>, annual_celebrations?: iterable<mixed>, monthly_celebrations?: iterable<mixed>}
     */
    private function dailyDateInfo(mixed $dateValue, string $locale): array
    {
        $date = $this->parseCarbonDate($dateValue);
        if ($date === null) {
            return [];
        }

        /** @var array{ethiopian_date?: array<string, mixed>, annual_celebrations?: iterable<mixed>, monthly_celebrations?: iterable<mixed>} $dateInfo */
        $dateInfo = $this->ethiopianCalendarService->getDateInfo($date, $locale);

        return $dateInfo;
    }

    private function parseCarbonDate(mixed $dateValue): ?Carbon
    {
        if ($dateValue instanceof Carbon) {
            return $dateValue->copy();
        }

        if ($dateValue instanceof \DateTimeInterface) {
            return Carbon::instance($dateValue);
        }

        $rawDate = trim((string) $dateValue);
        if ($rawDate === '') {
            return null;
        }

        try {
            return Carbon::parse($rawDate);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $ethiopianDate
     */
    private function formatEthiopianMonthDay(array $ethiopianDate, string $locale): string
    {
        $monthNameKey = $locale === 'am' ? 'month_name_am' : 'month_name_en';
        $monthName = trim((string) ($ethiopianDate[$monthNameKey] ?? ''));
        $day = trim((string) ($ethiopianDate['day'] ?? ''));

        return trim($monthName.' '.$day);
    }

    private function formatGregorianMonthDay(mixed $dateValue, string $locale): string
    {
        $date = $this->parseCarbonDate($dateValue);
        if ($date === null) {
            return '';
        }

        $monthNames = $locale === 'am'
            ? self::GREGORIAN_MONTH_NAMES_AM
            : self::GREGORIAN_MONTH_NAMES_EN;

        $monthName = $monthNames[(int) $date->format('n')] ?? '';
        $day = $date->format('j');

        return trim($monthName.' '.$day);
    }

    /**
     * @param  iterable<mixed>  $celebrations
     * @return list<string>
     */
    private function localizedCelebrationNames(iterable $celebrations, string $locale): array
    {
        $preferredField = 'celebration_'.$locale;
        $fallbackField = 'celebration_'.$this->fallbackLocale($locale);
        $names = [];

        foreach ($celebrations as $celebration) {
            $preferred = trim((string) ($celebration->{$preferredField} ?? ''));
            $fallback = trim((string) ($celebration->{$fallbackField} ?? ''));
            $name = $preferred !== '' ? $preferred : $fallback;

            if ($name !== '') {
                $names[] = $name;
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * @param  list<string>  $names
     */
    private function formatInlineCelebrationList(array $names): string
    {
        return implode(', ', $names);
    }

    /**
     * @param  list<string>  $names
     */
    private function formatBulletCelebrationList(array $names): string
    {
        if ($names === []) {
            return '';
        }

        return '- '.implode("\n- ", $names);
    }
}

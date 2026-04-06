<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyContent;
use App\Models\HimamatDay;
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
    ) {}

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
        'header_en',
        'commemorations_block_en',
        'footer_en',
        'header_am',
        'commemorations_block_am',
        'footer_am',
        'header',
        'commemorations_block',
        'footer',
        'bible_reference',
        'url',
    ];

    /** @var list<string> */
    public const DAILY_REMINDER_FINAL_PLACEHOLDERS = [
        'header_en',
        'commemorations_block_en',
        'footer_en',
        'header_am',
        'commemorations_block_am',
        'footer_am',
        'header',
        'commemorations_block',
        'footer',
    ];

    /** @var list<string> */
    public const DAILY_REMINDER_SECTION_PLACEHOLDERS = [
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

    /** @var list<string> */
    public const HIMAMAT_INTRO_PLACEHOLDERS = [
        'name',
        'baptism_name',
        'day',
        'himamat_weekday',
        'himamat_ordinal',
        'day_reminder_title',
        'day_theme_meaning',
        'himamat_day_title',
        'himamat_day_meaning',
        'url',
    ];

    /** @var list<string> */
    public const HIMAMAT_SLOT_PLACEHOLDERS = [
        'reminder_header',
        'reminder_content',
        'url',
    ];

    /** @var list<string> */
    public const BULK_MESSAGE_PLACEHOLDERS = [
        'name',
    ];

    /** @var list<string> */
    private const HIMAMAT_WEEKDAY_EN = [
        50 => 'Monday',
        51 => 'Tuesday',
        52 => 'Wednesday',
        53 => 'Thursday',
        54 => 'Friday',
        55 => 'Saturday',
    ];

    /** @var list<string> */
    private const HIMAMAT_WEEKDAY_AM = [
        50 => 'ሰኞ',
        51 => 'ማክሰኞ',
        52 => 'ረቡዕ',
        53 => 'ሐሙስ',
        54 => 'ዓርብ',
        55 => 'ቅዳሜ',
    ];

    /** @var list<string> */
    private const HIMAMAT_ORDINAL_EN = [
        50 => 'first',
        51 => 'second',
        52 => 'third',
        53 => 'fourth',
        54 => 'fifth',
        55 => 'sixth',
    ];

    /** @var list<string> */
    private const HIMAMAT_ORDINAL_AM = [
        50 => 'የመጀመሪያው',
        51 => 'ሁለተኛው',
        52 => 'ሦስተኛው',
        53 => 'አራተኛው',
        54 => 'አምስተኛው',
        55 => 'ስድስተኛው',
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
        $resolvedLocale = $this->preferredLocale($member, $locale);
        $englishVariables = $this->dailyReminderVariables($member, $dailyContent, $url, 'en');
        $amharicVariables = $this->dailyReminderVariables($member, $dailyContent, $url, 'am');

        $headerEn = $this->renderReminderSection('app.whatsapp_daily_reminder_header', $englishVariables, 'en');
        $headerAm = $this->renderReminderSection('app.whatsapp_daily_reminder_header', $amharicVariables, 'am');
        $footerEn = $this->renderReminderSection('app.whatsapp_daily_reminder_footer', $englishVariables, 'en');
        $footerAm = $this->renderReminderSection('app.whatsapp_daily_reminder_footer', $amharicVariables, 'am');
        $commemorationsBlockEn = $this->renderCommemorationsBlock($englishVariables, 'en');
        $commemorationsBlockAm = $this->renderCommemorationsBlock($amharicVariables, 'am');

        $variables = $resolvedLocale === 'am' ? $amharicVariables : $englishVariables;
        $variables['header_en'] = $headerEn;
        $variables['commemorations_block_en'] = $commemorationsBlockEn;
        $variables['footer_en'] = $footerEn;
        $variables['header_am'] = $headerAm;
        $variables['commemorations_block_am'] = $commemorationsBlockAm;
        $variables['footer_am'] = $footerAm;
        $variables['header'] = $resolvedLocale === 'am' ? $headerAm : $headerEn;
        $variables['commemorations_block'] = $resolvedLocale === 'am' ? $commemorationsBlockAm : $commemorationsBlockEn;
        $variables['footer'] = $resolvedLocale === 'am' ? $footerAm : $footerEn;

        $content = $this->normalizeRenderedText(
            $this->translate('app.whatsapp_daily_reminder_content', $variables, $resolvedLocale)
        );

        if ($content === '') {
            $content = $this->normalizeRenderedText(
                implode("\n\n", array_values(array_filter([
                    $variables['header'],
                    $variables['commemorations_block'],
                    $variables['footer'],
                ], static fn (string $value): bool => $value !== '')))
            );
        }

        return [
            'locale' => $resolvedLocale,
            'variables' => $variables,
            'header' => $variables['header'],
            'footer' => $variables['footer'],
            'content' => $content,
            'message' => $content,
        ];
    }

    public function renderConfirmationTemplate(
        string $translationKey,
        Member $member,
        string $url,
        string $telegramUrl,
        ?string $locale = null
    ): string {
        $resolvedLocale = $this->preferredLocale($member, $locale);
        $variables = $this->confirmationVariables($member, $url, $telegramUrl);

        return $this->translate($translationKey, $variables, $resolvedLocale);
    }

    /**
     * @return array{locale: string, variables: array<string, string>, header: string, content: string, message: string}
     */
    public function renderBulkMessage(
        Member $member,
        string $englishMessage,
        string $amharicMessage,
        ?string $locale = null
    ): array {
        $resolvedLocale = $this->preferredLocale($member, $locale);
        $variables = $this->bulkMessageVariables($member);
        $selectedTemplate = $resolvedLocale === 'am' ? $amharicMessage : $englishMessage;
        $message = $this->normalizeRenderedText(
            $this->replaceTemplatePlaceholders($selectedTemplate, $variables)
        );

        return [
            'locale' => $resolvedLocale,
            'variables' => $variables,
            'header' => '',
            'content' => '',
            'message' => $message,
        ];
    }

    /**
     * @return array{locale: string, variables: array<string, string>, content: string, message: string}
     */
    public function renderHimamatIntroReminder(
        Member $member,
        DailyContent $dailyContent,
        HimamatDay $himamatDay,
        string $url,
        ?string $locale = null
    ): array {
        $resolvedLocale = $this->preferredLocale($member, $locale);
        $variables = $this->himamatIntroVariables($member, $dailyContent, $himamatDay, $url, $resolvedLocale);

        $content = $this->normalizeRenderedText(
            $this->translate('app.whatsapp_himamat_intro_content', $variables, $resolvedLocale)
        );

        return [
            'locale' => $resolvedLocale,
            'variables' => $variables,
            'content' => $content,
            'message' => $content,
        ];
    }

    public function himamatIntroIsReady(HimamatDay $himamatDay, ?string $locale = null): bool
    {
        $locale = $this->normalizeLocale($locale ?? app()->getLocale());
        $introSlot = $himamatDay->slots->firstWhere('slot_key', 'intro');

        if (! $introSlot) {
            return false;
        }

        $reminderHeader = trim((string) (localized($introSlot, 'reminder_header', $locale) ?? ''));
        $dayMeaning = trim((string) (localized($himamatDay, 'spiritual_meaning', $locale) ?? ''));

        return $reminderHeader !== '' && $dayMeaning !== '';
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

    /**
     * @return array<string, string>
     */
    private function bulkMessageVariables(
        Member $member
    ): array {
        $name = trim((string) ($member->baptism_name ?? ''));

        return [
            'name' => $name,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function himamatIntroVariables(
        Member $member,
        DailyContent $dailyContent,
        HimamatDay $himamatDay,
        string $url,
        string $locale
    ): array {
        $name = trim((string) ($member->baptism_name ?? ''));
        $dayNumber = (int) $dailyContent->day_number;
        $introSlot = $himamatDay->slots->firstWhere('slot_key', 'intro');
        $dayTitle = trim((string) (
            ($introSlot ? localized($introSlot, 'reminder_header', $locale) : null)
            ?? localized($himamatDay, 'title', $locale)
            ?? $himamatDay->title_en
            ?? ''
        ));
        $dayMeaning = trim((string) (
            localized($himamatDay, 'spiritual_meaning', $locale)
            ?? $himamatDay->spiritual_meaning_en
            ?? ''
        ));

        return [
            'name' => $name,
            'baptism_name' => $name,
            'day' => trim((string) $dayNumber),
            'himamat_weekday' => $this->himamatWeekdayLabel($dayNumber, $locale),
            'himamat_ordinal' => $this->himamatOrdinalLabel($dayNumber, $locale),
            'day_reminder_title' => $dayTitle,
            'day_theme_meaning' => $dayMeaning,
            'himamat_day_title' => $dayTitle,
            'himamat_day_meaning' => $dayMeaning,
            'url' => $url,
        ];
    }

    private function translate(string $translationKey, array $variables, string $locale): string
    {
        Translation::loadFromDb($locale);

        return Lang::get($translationKey, $variables, $locale);
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function replaceTemplatePlaceholders(string $template, array $variables): string
    {
        return preg_replace_callback('/\:([a-z_]+)/i', function (array $matches) use ($variables): string {
            $key = strtolower((string) ($matches[1] ?? ''));

            return array_key_exists($key, $variables)
                ? $variables[$key]
                : (string) ($matches[0] ?? '');
        }, $template) ?? $template;
    }

    private function normalizeLocale(string $locale): string
    {
        return in_array($locale, ['en', 'am'], true) ? $locale : 'en';
    }

    private function preferredLocale(Member $member, ?string $locale = null): string
    {
        if ($locale !== null && trim($locale) !== '') {
            return $this->normalizeLocale($locale);
        }

        $preferred = trim((string) ($member->whatsapp_language ?? $member->locale ?? ''));

        if ($preferred === '') {
            return 'am';
        }

        return in_array($preferred, ['en', 'am'], true) ? $preferred : 'am';
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

    private function himamatWeekdayLabel(int $dayNumber, string $locale): string
    {
        $map = $locale === 'am' ? self::HIMAMAT_WEEKDAY_AM : self::HIMAMAT_WEEKDAY_EN;

        return $map[$dayNumber] ?? '';
    }

    private function himamatOrdinalLabel(int $dayNumber, string $locale): string
    {
        $map = $locale === 'am' ? self::HIMAMAT_ORDINAL_AM : self::HIMAMAT_ORDINAL_EN;

        return $map[$dayNumber] ?? '';
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderCommemorationsBlock(array $variables, string $locale): string
    {
        $blocks = [];

        if (trim((string) ($variables['yearly_commemorations_bullets'] ?? '')) !== '') {
            $blocks[] = $this->normalizeRenderedText(
                $this->translate('app.whatsapp_daily_reminder_yearly_block', $variables, $locale)
            );
        }

        if (trim((string) ($variables['monthly_commemorations_bullets'] ?? '')) !== '') {
            $blocks[] = $this->normalizeRenderedText(
                $this->translate('app.whatsapp_daily_reminder_monthly_block', $variables, $locale)
            );
        }

        return $this->normalizeRenderedText(implode("\n\n", array_filter($blocks, static fn (string $value): bool => $value !== '')));
    }

    /**
     * @param  array<string, string>  $variables
     */
    private function renderReminderSection(string $translationKey, array $variables, string $locale): string
    {
        return $this->normalizeRenderedText(
            $this->translate($translationKey, $variables, $locale)
        );
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

    private function normalizeRenderedText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        $normalized = preg_replace("/\n{3,}/", "\n\n", $normalized) ?? $normalized;

        return trim($normalized);
    }
}

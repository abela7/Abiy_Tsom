<?php

declare(strict_types=1);

namespace App\Services;

use Andegna\DateTime as EthiopianDateTime;
use Andegna\DateTimeFactory;
use App\Models\EthiopianSynaxariumAnnual;
use App\Models\EthiopianSynaxariumMonthly;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EthiopianCalendarService
{
    /** @var array<int, string> */
    private const MONTH_NAMES_EN = [
        1 => 'Meskerem', 2 => 'Tikimt', 3 => 'Hidar',
        4 => 'Tahsas', 5 => 'Tir', 6 => 'Yekatit',
        7 => 'Megabit', 8 => 'Miyazia', 9 => 'Ginbot',
        10 => 'Sene', 11 => 'Hamle', 12 => 'Nehase',
        13 => 'Pagumen',
    ];

    /** @var array<int, string> */
    private const MONTH_NAMES_AM = [
        1 => 'መስከረም', 2 => 'ጥቅምት', 3 => 'ኅዳር',
        4 => 'ታኅሣሥ', 5 => 'ጥር', 6 => 'የካቲት',
        7 => 'መጋቢት', 8 => 'ሚያዝያ', 9 => 'ግንቦት',
        10 => 'ሰኔ', 11 => 'ሐምሌ', 12 => 'ነሐሴ',
        13 => 'ጳጉሜን',
    ];

    /**
     * Convert a Gregorian date to Ethiopian calendar components.
     *
     * @return array{year: int, month: int, day: int, month_name_en: string, month_name_am: string}
     */
    public function gregorianToEthiopian(Carbon $date): array
    {
        $ethDate = new EthiopianDateTime(
            new \DateTime($date->format('Y-m-d'))
        );

        $month = $ethDate->getMonth();

        return [
            'year' => $ethDate->getYear(),
            'month' => $month,
            'day' => $ethDate->getDay(),
            'month_name_en' => self::MONTH_NAMES_EN[$month] ?? 'Unknown',
            'month_name_am' => self::MONTH_NAMES_AM[$month] ?? '',
        ];
    }

    /**
     * Convert Ethiopian calendar components to Gregorian Carbon date.
     * Uses current Ethiopian year when year is not provided.
     */
    public function ethiopianToGregorian(int $month, int $day, ?int $year = null): Carbon
    {
        $ethToday = $this->gregorianToEthiopian(Carbon::today());
        $year = $year ?? $ethToday['year'];

        $ethDate = DateTimeFactory::of($year, $month, $day);
        $gregorian = $ethDate->toGregorian();

        return Carbon::parse($gregorian->format('Y-m-d'));
    }

    /**
     * @return array<int, string>
     */
    public function monthOptions(string $locale = 'en'): array
    {
        return $locale === 'am' ? self::MONTH_NAMES_AM : self::MONTH_NAMES_EN;
    }

    /**
     * Format Ethiopian date as a readable string.
     */
    public function formatEthiopianDate(Carbon $date, string $locale = 'en'): string
    {
        $eth = $this->gregorianToEthiopian($date);

        if ($locale === 'am') {
            return $eth['month_name_am'].' '.$eth['day'].'፣ '.$eth['year'].' ዓ.ም.';
        }

        return $eth['month_name_en'].' '.$eth['day'].', '.$eth['year'].' E.C.';
    }

    public function formatEthiopianMonthDay(int $month, int $day, string $locale = 'en', ?int $year = null): string
    {
        $monthNames = $this->monthOptions($locale);
        $monthName = $monthNames[$month] ?? 'Unknown';
        $resolvedYear = $year ?? $this->gregorianToEthiopian(Carbon::today())['year'];

        if ($locale === 'am') {
            return $monthName.' '.$day.'፣ '.$resolvedYear.' ዓ.ም.';
        }

        return $monthName.' '.$day.', '.$resolvedYear.' E.C.';
    }

    /**
     * Get all celebrations for a given Gregorian date.
     * Returns annuals only when they exist; otherwise monthlies.
     *
     * @return Collection<int, EthiopianSynaxariumAnnual|EthiopianSynaxariumMonthly>
     */
    public function getCelebrationsForDate(Carbon $date): Collection
    {
        $eth = $this->gregorianToEthiopian($date);

        $annuals = EthiopianSynaxariumAnnual::where('month', $eth['month'])
            ->where('day', $eth['day'])
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        if ($annuals->isNotEmpty()) {
            return $annuals;
        }

        return EthiopianSynaxariumMonthly::where('day', $eth['day'])
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get Ethiopian date info and celebrations in one call.
     * Always returns both annual and monthly celebrations separately.
     */
    public function getDateInfo(Carbon $date, string $locale = 'en'): array
    {
        $eth = $this->gregorianToEthiopian($date);

        $annuals = EthiopianSynaxariumAnnual::where('month', $eth['month'])
            ->where('day', $eth['day'])
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        $monthlies = EthiopianSynaxariumMonthly::where('day', $eth['day'])
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        // Combined for backward compat: annuals take priority
        $celebrations = $annuals->isNotEmpty() ? $annuals : $monthlies;
        $mainCelebration = $celebrations->firstWhere('is_main', true) ?? $celebrations->first();

        return [
            'ethiopian_date' => $eth,
            'ethiopian_date_formatted' => $this->formatEthiopianDate($date, $locale),
            'celebrations' => $celebrations,
            'main_celebration' => $mainCelebration,
            'celebration' => $mainCelebration,
            'is_annual_feast' => $mainCelebration instanceof EthiopianSynaxariumAnnual,
            'annual_celebrations' => $annuals,
            'monthly_celebrations' => $monthlies,
        ];
    }

    /**
     * Get Ethiopian date info directly from an Ethiopian month/day selection.
     */
    public function getDateInfoForEthiopianDay(int $month, int $day, string $locale = 'en'): array
    {
        $todayEth = $this->gregorianToEthiopian(Carbon::today());
        $eth = [
            'year' => $todayEth['year'],
            'month' => $month,
            'day' => $day,
            'month_name_en' => self::MONTH_NAMES_EN[$month] ?? 'Unknown',
            'month_name_am' => self::MONTH_NAMES_AM[$month] ?? '',
        ];

        $annuals = EthiopianSynaxariumAnnual::where('month', $month)
            ->where('day', $day)
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        $monthlies = EthiopianSynaxariumMonthly::where('day', $day)
            ->orderByDesc('is_main')
            ->orderBy('sort_order')
            ->get();

        $celebrations = $annuals->isNotEmpty() ? $annuals : $monthlies;
        $mainCelebration = $celebrations->firstWhere('is_main', true) ?? $celebrations->first();

        return [
            'ethiopian_date' => $eth,
            'ethiopian_date_formatted' => $this->formatEthiopianMonthDay($month, $day, $locale, $todayEth['year']),
            'celebrations' => $celebrations,
            'main_celebration' => $mainCelebration,
            'celebration' => $mainCelebration,
            'is_annual_feast' => $mainCelebration instanceof EthiopianSynaxariumAnnual,
            'annual_celebrations' => $annuals,
            'monthly_celebrations' => $monthlies,
        ];
    }
}

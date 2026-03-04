<?php

declare(strict_types=1);

namespace App\Services;

use Andegna\DateTime as EthiopianDateTime;
use App\Models\EthiopianSynaxariumAnnual;
use App\Models\EthiopianSynaxariumMonthly;
use Carbon\Carbon;

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
     * Format Ethiopian date as a readable string.
     */
    public function formatEthiopianDate(Carbon $date, string $locale = 'en'): string
    {
        $eth = $this->gregorianToEthiopian($date);

        if ($locale === 'am') {
            return $eth['month_name_am'] . ' ' . $eth['day'] . '፣ ' . $eth['year'] . ' ዓ.ም.';
        }

        return $eth['month_name_en'] . ' ' . $eth['day'] . ', ' . $eth['year'] . ' E.C.';
    }

    /**
     * Get the celebration for a given Gregorian date.
     * Priority: annual override (specific month+day) > monthly default (day only).
     */
    public function getCelebrationForDate(Carbon $date): EthiopianSynaxariumAnnual|EthiopianSynaxariumMonthly|null
    {
        $eth = $this->gregorianToEthiopian($date);

        $annual = EthiopianSynaxariumAnnual::where('month', $eth['month'])
            ->where('day', $eth['day'])
            ->first();

        if ($annual) {
            return $annual;
        }

        return EthiopianSynaxariumMonthly::where('day', $eth['day'])->first();
    }

    /**
     * Get Ethiopian date info and celebration in one call.
     *
     * @return array{ethiopian_date: array, ethiopian_date_formatted: string, celebration: EthiopianSynaxariumAnnual|EthiopianSynaxariumMonthly|null, is_annual_feast: bool}
     */
    public function getDateInfo(Carbon $date, string $locale = 'en'): array
    {
        $celebration = $this->getCelebrationForDate($date);

        return [
            'ethiopian_date' => $this->gregorianToEthiopian($date),
            'ethiopian_date_formatted' => $this->formatEthiopianDate($date, $locale),
            'celebration' => $celebration,
            'is_annual_feast' => $celebration instanceof EthiopianSynaxariumAnnual,
        ];
    }
}

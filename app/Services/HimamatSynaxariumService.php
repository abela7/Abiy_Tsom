<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HimamatDay;

class HimamatSynaxariumService
{
    public function __construct(
        private readonly EthiopianCalendarService $ethiopianCalendar
    ) {}

    /**
     * @return array<int, string>
     */
    public function monthOptions(string $locale = 'en'): array
    {
        return $this->ethiopianCalendar->monthOptions($locale);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveDateInfo(HimamatDay $day, string $locale = 'en'): ?array
    {
        if (
            $day->synaxarium_source === 'manual'
            && $day->synaxarium_month !== null
            && $day->synaxarium_day !== null
        ) {
            return $this->ethiopianCalendar->getDateInfoForEthiopianDay(
                $day->synaxarium_month,
                $day->synaxarium_day,
                $locale
            );
        }

        if (! $day->date) {
            return null;
        }

        return $this->ethiopianCalendar->getDateInfo($day->date->copy(), $locale);
    }
}

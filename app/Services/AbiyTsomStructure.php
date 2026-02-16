<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;

/**
 * Canonical structure of Abiy Tsom (Great Lent): 55 days across 8 weeks.
 *
 * Three periods:
 * - Tsome Hirkal: Week 1 (8 days) - Emperor Eraclius recovering Holy Cross
 * - Tsome Arba: Weeks 2-7 (40 days) - Christ's 40-day wilderness fast
 * - Himamat: Week 8 (7 days) - Passion Week
 *
 * 8 Sundays (from St. Yared's Tsome Digua):
 * Zewerede, Kidist, Mikurab, Mesague, Debre Zeit, Gebrehere, Nicodemus, Hosanna
 */
final class AbiyTsomStructure
{
    public const TOTAL_DAYS = 55;

    public const TOTAL_WEEKS = 8;

    /**
     * Canonical week definitions with liturgical readings (from abuneteklehaymanot.org).
     *
     * @var array<int, array{
     *     day_start: int,
     *     day_end: int,
     *     name_en: string,
     *     name_geez: string,
     *     name_am: string,
     *     period: string,
     *     meaning: string,
     *     gospel_reference: ?string,
     *     epistles_reference: ?string,
     *     psalm_reference: ?string,
     *     liturgy: ?string,
     *     theme_summary: ?string
     * }>
     */
    private const WEEKS = [
        1 => [
            'day_start' => 1,
            'day_end' => 7,
            'name_en' => 'Zewerede',
            'name_geez' => 'ዘወረደ',
            'name_am' => 'ዘወረደ',
            'period' => 'Tsome Hirkal',
            'meaning' => 'He who descended from above',
            'gospel_reference' => 'John 3:1-21',
            'epistles_reference' => null,
            'psalm_reference' => null,
            'liturgy' => null,
            'theme_summary' => 'Commemorates Jesus coming to save sinners. First week of Lent (Tsome Hirkal) honors Emperor Eraclius recovering the Holy Cross from Persia.',
        ],
        2 => [
            'day_start' => 8,
            'day_end' => 14,
            'name_en' => 'Kidist',
            'name_geez' => 'ቅድስት',
            'name_am' => 'ቅድስት',
            'period' => 'Tsome Arba',
            'meaning' => 'Holy',
            'gospel_reference' => '1 Peter 1:15-17; Matthew 14:13-21',
            'epistles_reference' => null,
            'psalm_reference' => null,
            'liturgy' => null,
            'theme_summary' => 'Holy or Sanctified. St. Yared teaches we must control our senses: eyes from seeing evil, ears from hearing evil, hands from doing evil, feet from running to evil, minds from thinking evil. Tsome Arba (40-day fast) begins this week. "Igzio Tesehalene" (Lord, Have Mercy).',
        ],
        3 => [
            'day_start' => 15,
            'day_end' => 21,
            'name_en' => 'Mikurab',
            'name_geez' => 'ምኩራብ',
            'name_am' => 'ምኩራብ',
            'period' => 'Tsome Arba',
            'meaning' => 'Synagogue / Temple',
            'gospel_reference' => 'John 2:12-end',
            'epistles_reference' => 'Colossians 2:16-end; James 2:14-end; Acts 10:1-9',
            'psalm_reference' => '69:9-10',
            'liturgy' => 'Anaphora of Our Lord',
            'theme_summary' => 'Remembers Jesus cleansing the temple, driving out merchants. "Do not make my Father\'s house a marketplace!" (John 2:16). Believers\' bodies are temples of God. Zeal for God\'s house.',
        ],
        4 => [
            'day_start' => 22,
            'day_end' => 28,
            'name_en' => 'Mesague',
            'name_geez' => 'መፃጉእ',
            'name_am' => 'መፃጉእ',
            'period' => 'Tsome Arba',
            'meaning' => 'One who is infirm / paralytic',
            'gospel_reference' => 'John 5:1-25',
            'epistles_reference' => 'Galatians 5:1-end; James 5:14-end; Acts 3:1-12',
            'psalm_reference' => '41:3-4',
            'liturgy' => 'Anaphora of Our Lord',
            'theme_summary' => 'Commemorates the paralytic at Bethesda (House of Mercy), healed after 38 years. Teaches us to care for the sick, visit prisoners, feed the hungry. God\'s healing and deliverance.',
        ],
        5 => [
            'day_start' => 29,
            'day_end' => 35,
            'name_en' => 'Debre Zeit',
            'name_geez' => 'ደብረዘይት',
            'name_am' => 'ደብረዘይት',
            'period' => 'Tsome Arba',
            'meaning' => 'Mount of Olives',
            'gospel_reference' => 'Matthew 24:1-36',
            'epistles_reference' => '1 Thessalonians 4:13-end; 2 Peter 3:7-15; Acts 24:1-22',
            'psalm_reference' => '50:2-3',
            'liturgy' => 'Anaphora of Athanasius',
            'theme_summary' => 'Feast of Mount Tabor. Christ\'s second coming announced on the Mount of Olives. Signs of the end: false Christs, wars, famine, earthquakes, persecution, false prophets, love growing cold. Be alert, be prepared.',
        ],
        6 => [
            'day_start' => 36,
            'day_end' => 42,
            'name_en' => 'Gebrehere',
            'name_geez' => 'ገብርሄር',
            'name_am' => 'ገብርሄር',
            'period' => 'Tsome Arba',
            'meaning' => 'Good Servant / Faithful Servant',
            'gospel_reference' => 'Matthew 25:14-31',
            'epistles_reference' => '2 Timothy 2:1-16; 1 Peter 5:1-12; Acts 1:6-9',
            'psalm_reference' => '40:8-9',
            'liturgy' => 'Anaphora of St. Basil',
            'theme_summary' => 'Parable of the talents. Faithful service and stewardship. Those who multiply what is given receive more; the lazy servant is condemned. Be faithful in the gift of baptism and Christian life.',
        ],
        7 => [
            'day_start' => 43,
            'day_end' => 49,
            'name_en' => 'Nicodemus',
            'name_geez' => 'ኒቆዲሞስ',
            'name_am' => 'ኒቆዲሞስ',
            'period' => 'Tsome Arba',
            'meaning' => 'Nicodemus',
            'gospel_reference' => 'John 3:1-21',
            'epistles_reference' => null,
            'psalm_reference' => null,
            'liturgy' => null,
            'theme_summary' => 'Commemorates Nicodemus\'s nighttime visit to Jesus. "You must be born again." God so loved the world that He gave His only Son.',
        ],
        8 => [
            'day_start' => 50,
            'day_end' => 55,
            'name_en' => 'Hosanna',
            'name_geez' => 'ሆሳእና',
            'name_am' => 'ሆሳእና',
            'period' => 'Himamat',
            'meaning' => 'Palm Sunday / Salvation',
            'gospel_reference' => 'John 12:12-20',
            'epistles_reference' => 'Hebrews 9:11-end; 1 Peter 4:1-12; Acts 28:11-end',
            'psalm_reference' => '8:2',
            'liturgy' => 'Anaphora of St. Gregory',
            'theme_summary' => 'Passion Week. Jesus\' triumphal entry into Jerusalem. People laid branches and clothes, crying "Hosanna" (salvation). "Out of the mouth of babes and sucklings hast thou ordained strength." The church must be a house of prayer, not a marketplace.',
        ],
    ];

    /**
     * Get week number (1-8) for a given day number (1-55).
     */
    public static function getWeekForDay(int $dayNumber): int
    {
        foreach (self::WEEKS as $weekNum => $info) {
            if ($dayNumber >= $info['day_start'] && $dayNumber <= $info['day_end']) {
                return $weekNum;
            }
        }

        return 1;
    }

    /**
     * Get day range [start, end] for a week (1-8).
     *
     * @return array{0: int, 1: int}
     */
    public static function getDayRangeForWeek(int $weekNumber): array
    {
        $info = self::WEEKS[$weekNumber] ?? null;
        if (! $info) {
            return [1, 7];
        }

        return [$info['day_start'], $info['day_end']];
    }

    /**
     * Compute date for a given day number within a season.
     */
    public static function getDateForDay(\DateTimeInterface|Carbon|string $seasonStartDate, int $dayNumber): Carbon
    {
        $start = Carbon::parse($seasonStartDate);

        return $start->copy()->addDays($dayNumber - 1);
    }

    /**
     * Get canonical week data for all 8 weeks.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getWeeks(): array
    {
        return self::WEEKS;
    }

    /**
     * Get canonical week data for a single week (1-8).
     *
     * @return array<string, mixed>|null
     */
    public static function getWeek(int $weekNumber): ?array
    {
        return self::WEEKS[$weekNumber] ?? null;
    }

    /**
     * Build weekly theme records for a lent season (for DB insertion).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function buildWeeklyThemesForSeason(
        int $lentSeasonId,
        \DateTimeInterface|Carbon|string $startDate
    ): array {
        $start = Carbon::parse($startDate);
        $themes = [];

        foreach (self::WEEKS as $weekNum => $info) {
            $weekStart = $start->copy()->addDays($info['day_start'] - 1);
            $weekEnd = $start->copy()->addDays($info['day_end'] - 1);

            $themes[] = [
                'lent_season_id' => $lentSeasonId,
                'week_number' => $weekNum,
                'name_geez' => $info['name_geez'],
                'name_en' => $info['name_en'],
                'name_am' => $info['name_am'],
                'meaning' => $info['meaning'],
                'description' => $info['period'].'. '.($info['theme_summary'] ?? ''),
                'gospel_reference' => $info['gospel_reference'],
                'epistles_reference' => $info['epistles_reference'] ?? null,
                'psalm_reference' => $info['psalm_reference'] ?? null,
                'liturgy' => $info['liturgy'] ?? null,
                'theme_summary' => $info['theme_summary'],
                'week_start_date' => $weekStart->format('Y-m-d'),
                'week_end_date' => $weekEnd->format('Y-m-d'),
            ];
        }

        return $themes;
    }

    /**
     * Build day metadata for a season (day_number, date, week_number, weekly_theme_id).
     * Use when generating daily content placeholders.
     *
     * @return array<int, array{day_number: int, date: string, week_number: int}>
     */
    public static function buildDayMetadata(\DateTimeInterface|Carbon|string $startDate): array
    {
        $start = Carbon::parse($startDate);
        $days = [];

        for ($d = 1; $d <= self::TOTAL_DAYS; $d++) {
            $days[$d] = [
                'day_number' => $d,
                'date' => $start->copy()->addDays($d - 1)->format('Y-m-d'),
                'week_number' => self::getWeekForDay($d),
            ];
        }

        return $days;
    }
}

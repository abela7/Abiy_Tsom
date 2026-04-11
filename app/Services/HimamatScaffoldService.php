<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class HimamatScaffoldService
{
    public function __construct(
        private readonly HimamatTimelineService $timeline
    ) {}

    /**
     * @return array{season: LentSeason|null, created_days: int, created_slots: int}
     */
    public function scaffoldActiveSeason(?int $actorId = null): array
    {
        $season = LentSeason::active();
        if (! $season) {
            return [
                'season' => null,
                'created_days' => 0,
                'created_slots' => 0,
            ];
        }

        $createdDays = 0;
        $createdSlots = 0;

        DB::transaction(function () use ($actorId, $season, &$createdDays, &$createdSlots): void {
            $definitions = $this->timeline->dayDefinitions();
            $targetDates = $this->targetDatesForSeason($season, count($definitions));
            $knownDefaultTitles = collect($definitions)
                ->pluck('title_en')
                ->push('Hosanna Sunday')
                ->filter(fn (?string $title): bool => is_string($title) && trim($title) !== '')
                ->unique()
                ->values()
                ->all();

            $targetSlugByDate = collect($definitions)
                ->mapWithKeys(fn (array $definition, int $index): array => [
                    ($targetDates[$index] ?? '') => (string) $definition['slug'],
                ])
                ->filter(fn (string $slug, string $date): bool => $date !== '')
                ->all();

            $targetSlugs = array_values(array_unique(array_values($targetSlugByDate)));

            $existingDays = HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->orderBy('date')
                ->orderBy('sort_order')
                ->get();

            foreach ($existingDays as $existingDay) {
                $date = $existingDay->date?->toDateString();
                $expectedSlug = $date !== null ? ($targetSlugByDate[$date] ?? null) : null;
                $slugNeedsParking = $expectedSlug !== null
                    ? $existingDay->slug !== $expectedSlug
                    : in_array($existingDay->slug, $targetSlugs, true);

                if (! $slugNeedsParking) {
                    continue;
                }

                $existingDay->update([
                    'slug' => sprintf('legacy-%d-%s', $existingDay->id, $existingDay->slug),
                ]);
            }

            $existingDays = HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->orderBy('date')
                ->orderBy('sort_order')
                ->get();

            $assignedDayIds = [];

            foreach ($definitions as $index => $definition) {
                $date = $targetDates[$index] ?? null;
                if ($date === null) {
                    continue;
                }

                $slug = (string) $definition['slug'];

                $day = $existingDays
                    ->first(fn (HimamatDay $candidate): bool => $candidate->date?->toDateString() === $date && ! in_array($candidate->id, $assignedDayIds, true))
                    ?? $existingDays
                        ->first(fn (HimamatDay $candidate): bool => $candidate->slug === $slug && ! in_array($candidate->id, $assignedDayIds, true));

                if (! $day) {
                    $day = HimamatDay::create([
                        'lent_season_id' => $season->id,
                        'slug' => $slug,
                        'sort_order' => $index + 1,
                        'date' => $date,
                        'title_en' => (string) $definition['title_en'],
                        'title_am' => $definition['title_am'] ?: null,
                        'is_published' => false,
                        'created_by_id' => $actorId,
                        'updated_by_id' => $actorId,
                    ]);
                    $createdDays++;
                } else {
                    $updates = [
                        'slug' => $slug,
                        'sort_order' => $index + 1,
                        'date' => $date,
                        'updated_by_id' => $actorId,
                    ];

                    if ($this->shouldRefreshTitle($day->title_en, $knownDefaultTitles)) {
                        $updates['title_en'] = (string) $definition['title_en'];
                    }
                    if ($this->shouldRefreshTitle($day->title_am, [])) {
                        $updates['title_am'] = $definition['title_am'];
                    }

                    $day->update($updates);
                }

                $assignedDayIds[] = $day->id;

                foreach ($this->timeline->slotDefinitions() as $slotDefinition) {
                    $slot = HimamatSlot::query()
                        ->where('himamat_day_id', $day->id)
                        ->where('slot_key', (string) $slotDefinition['key'])
                        ->first();

                    if (! $slot) {
                        HimamatSlot::create([
                            'himamat_day_id' => $day->id,
                            'slot_key' => (string) $slotDefinition['key'],
                            'slot_order' => (int) $slotDefinition['order'],
                            'scheduled_time_london' => (string) $slotDefinition['time'],
                            'slot_header_en' => (string) $slotDefinition['default_slot_header_en'],
                            'slot_header_am' => $slotDefinition['default_slot_header_am'] ?: null,
                            'reminder_header_en' => (string) $slotDefinition['default_reminder_header_en'],
                            'reminder_header_am' => $slotDefinition['default_reminder_header_am'] ?: null,
                            'is_published' => false,
                            'created_by_id' => $actorId,
                            'updated_by_id' => $actorId,
                        ]);
                        $createdSlots++;

                        continue;
                    }

                    $updates = [
                        'slot_order' => (int) $slotDefinition['order'],
                        'scheduled_time_london' => (string) $slotDefinition['time'],
                        'updated_by_id' => $actorId,
                    ];

                    if (blank($slot->slot_header_en)) {
                        $updates['slot_header_en'] = (string) $slotDefinition['default_slot_header_en'];
                    }
                    if (blank($slot->slot_header_am) && ! blank($slotDefinition['default_slot_header_am'] ?? null)) {
                        $updates['slot_header_am'] = $slotDefinition['default_slot_header_am'];
                    }
                    if (blank($slot->reminder_header_en)) {
                        $updates['reminder_header_en'] = (string) $slotDefinition['default_reminder_header_en'];
                    }
                    if (blank($slot->reminder_header_am) && ! blank($slotDefinition['default_reminder_header_am'] ?? null)) {
                        $updates['reminder_header_am'] = $slotDefinition['default_reminder_header_am'];
                    }

                    $slot->update($updates);
                }
            }
        });

        return [
            'season' => $season,
            'created_days' => $createdDays,
            'created_slots' => $createdSlots,
        ];
    }

    /**
     * @return list<string>
     */
    public function targetDatesForSeason(LentSeason $season, ?int $expectedCount = null): array
    {
        $expectedCount ??= count($this->timeline->dayDefinitions());

        $dailyDates = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereBetween('day_number', [50, 56])
            ->orderBy('day_number')
            ->pluck('date')
            ->map(fn ($date): string => CarbonImmutable::parse((string) $date)->toDateString())
            ->values()
            ->all();

        if (count($dailyDates) === $expectedCount) {
            return $dailyDates;
        }

        $startDate = CarbonImmutable::parse($season->end_date->toDateString())
            ->subDays($expectedCount);

        return collect(range(0, $expectedCount - 1))
            ->map(fn (int $offset): string => $startDate->addDays($offset)->toDateString())
            ->all();
    }

    /**
     * @param  list<string>  $knownDefaultTitles
     */
    private function shouldRefreshTitle(?string $currentTitle, array $knownDefaultTitles): bool
    {
        $value = trim((string) $currentTitle);

        if ($value === '') {
            return true;
        }

        return in_array($value, $knownDefaultTitles, true);
    }
}

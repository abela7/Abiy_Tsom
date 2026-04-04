<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use Carbon\CarbonImmutable;

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

        $startDate = CarbonImmutable::parse($season->end_date->toDateString())->subDays(count($this->timeline->dayDefinitions()) - 1);

        foreach ($this->timeline->dayDefinitions() as $index => $definition) {
            $date = $startDate->addDays($index)->toDateString();
            $slug = (string) $definition['slug'];

            $day = HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->where('slug', $slug)
                ->first();

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
                    'sort_order' => $index + 1,
                    'date' => $date,
                    'updated_by_id' => $actorId,
                ];

                if (blank($day->title_en)) {
                    $updates['title_en'] = (string) $definition['title_en'];
                }
                if (blank($day->title_am) && ! blank($definition['title_am'] ?? null)) {
                    $updates['title_am'] = $definition['title_am'];
                }

                $day->update($updates);
            }

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

        return [
            'season' => $season,
            'created_days' => $createdDays,
            'created_slots' => $createdSlots,
        ];
    }
}

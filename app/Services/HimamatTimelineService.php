<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class HimamatTimelineService
{
    public function timezone(): string
    {
        return (string) config('himamat.timezone', 'Europe/London');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function dayDefinitions(): array
    {
        /** @var list<array<string, mixed>> $days */
        $days = config('himamat.days', []);

        return $days;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function slotDefinitions(): array
    {
        /** @var list<array<string, mixed>> $slots */
        $slots = config('himamat.slots', []);

        return $slots;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function slotDefinition(string $slotKey): ?array
    {
        foreach ($this->slotDefinitions() as $slot) {
            if (($slot['key'] ?? null) === $slotKey) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @return array{day: HimamatDay, slot: HimamatSlot}|null
     */
    public function resolveLandingTarget(?LentSeason $season, bool $publishedOnly = true): ?array
    {
        if (! $season) {
            return null;
        }

        $query = HimamatDay::query()
            ->where('lent_season_id', $season->id)
            ->orderBy('date')
            ->orderBy('sort_order');

        if ($publishedOnly) {
            $query->where('is_published', true);
        }

        $days = $query
            ->with(['slots' => function ($slotQuery) use ($publishedOnly): void {
                if ($publishedOnly) {
                    $slotQuery->where('is_published', true);
                }

                $slotQuery->orderBy('slot_order');
            }])
            ->get()
            ->filter(fn (HimamatDay $day): bool => $day->slots->isNotEmpty())
            ->values();

        if ($days->isEmpty()) {
            return null;
        }

        $now = CarbonImmutable::now($this->timezone());
        $today = $now->toDateString();

        /** @var HimamatDay|null $day */
        $day = $days->first(fn (HimamatDay $item): bool => $item->date?->toDateString() === $today);

        if (! $day) {
            $day = $days->first(fn (HimamatDay $item): bool => $item->date?->gte($now->startOfDay()))
                ?? $days->last();
        }

        if (! $day) {
            return null;
        }

        $slotKey = $this->defaultSlotKeyForDay($day, $now);
        $slot = $day->slots->firstWhere('slot_key', $slotKey) ?? $day->slots->first();

        if (! $slot) {
            return null;
        }

        return [
            'day' => $day,
            'slot' => $slot,
        ];
    }

    public function currentSlotKey(?CarbonImmutable $nowLondon = null): string
    {
        $nowLondon ??= CarbonImmutable::now($this->timezone());
        $time = $nowLondon->format('H:i:s');

        $resolved = 'intro';
        foreach ($this->slotDefinitions() as $slot) {
            $slotTime = (string) ($slot['time'] ?? '00:00:00');
            if ($time >= $slotTime) {
                $resolved = (string) ($slot['key'] ?? 'intro');
            }
        }

        return $resolved;
    }

    public function preferenceColumn(string $slotKey): string
    {
        return $slotKey.'_enabled';
    }

    /**
     * @return array{
     *     now: CarbonImmutable,
     *     target_slot_key: string,
     *     current_slot_key: string,
     *     is_today: bool,
     *     items: Collection<int, array<string, mixed>>
     * }
     */
    public function buildTimeline(HimamatDay $day, ?string $requestedSlotKey = null): array
    {
        $now = CarbonImmutable::now($this->timezone());
        $currentSlotKey = $this->currentSlotKey($now);
        $targetSlotKey = $requestedSlotKey ?: $this->defaultSlotKeyForDay($day, $now);
        $dayDate = CarbonImmutable::parse($day->date?->toDateString() ?? $now->toDateString(), $this->timezone());
        $isToday = $dayDate->isSameDay($now);

        $items = $day->slots
            ->sortBy('slot_order')
            ->values()
            ->map(function (HimamatSlot $slot) use ($dayDate, $now, $currentSlotKey, $targetSlotKey, $isToday): array {
                $scheduledAt = $dayDate->setTimeFromTimeString((string) $slot->scheduled_time_london);

                if ($scheduledAt->isSameMinute($now) || ($isToday && $slot->slot_key === $currentSlotKey)) {
                    $temporalState = 'current';
                } elseif ($scheduledAt->lt($now)) {
                    $temporalState = 'past';
                } else {
                    $temporalState = 'future';
                }

                return [
                    'slot' => $slot,
                    'scheduled_at' => $scheduledAt,
                    'temporal_state' => $temporalState,
                    'is_target' => $slot->slot_key === $targetSlotKey,
                    'is_current' => $isToday && $slot->slot_key === $currentSlotKey,
                    'is_published' => $slot->is_published,
                ];
            });

        if (! $items->contains(fn (array $item): bool => (bool) $item['is_target'])) {
            $items = $items->map(function (array $item, int $index): array {
                $item['is_target'] = $index === 0;

                return $item;
            });

            $targetSlotKey = (string) ($items->first()['slot']->slot_key ?? 'intro');
        }

        return [
            'now' => $now,
            'target_slot_key' => $targetSlotKey,
            'current_slot_key' => $currentSlotKey,
            'is_today' => $isToday,
            'items' => $items,
        ];
    }

    public function defaultSlotKeyForDay(HimamatDay $day, ?CarbonImmutable $now = null): string
    {
        $now ??= CarbonImmutable::now($this->timezone());
        $dayDate = CarbonImmutable::parse($day->date?->toDateString() ?? $now->toDateString(), $this->timezone());

        if ($dayDate->isSameDay($now)) {
            return $this->currentSlotKey($now);
        }

        return $dayDate->lt($now->startOfDay()) ? 'eleventh' : 'intro';
    }
}

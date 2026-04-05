<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendHimamatReminderJob;
use App\Models\HimamatReminderDispatch;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\MemberHimamatReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class HimamatReminderDispatchService
{
    private const CHANNEL = 'whatsapp';

    public function timezone(): string
    {
        return (string) config('himamat.timezone', 'Europe/London');
    }

    public function dispatchGraceMinutes(): int
    {
        return (int) config('himamat.reminders.dispatch_grace_minutes', 20);
    }

    public function reminderQueueName(): string
    {
        return (string) config('himamat.reminders.queues.reminders', SendHimamatReminderJob::QUEUE_NAME);
    }

    public function testModeMemberId(): ?int
    {
        $memberId = (int) config('himamat.reminders.test_mode_member_id');

        return $memberId > 0 ? $memberId : null;
    }

    /**
     * @return Collection<int, HimamatSlot>
     */
    public function dueSlots(LentSeason $season, CarbonImmutable $nowLondon): Collection
    {
        return HimamatSlot::query()
            ->where('is_published', true)
            ->where('scheduled_time_london', '<=', $nowLondon->format('H:i:00'))
            ->whereHas('himamatDay', function ($query) use ($season, $nowLondon): void {
                $query->where('lent_season_id', $season->id)
                    ->where('is_published', true)
                    ->whereDate('date', $nowLondon->toDateString());
            })
            ->with([
                'himamatDay',
                'reminderDispatches' => fn ($query) => $query
                    ->where('channel', self::CHANNEL),
            ])
            ->orderBy('slot_order')
            ->get();
    }

    public function dueAtLondon(HimamatSlot $slot): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $slot->himamatDay->date?->toDateString().' '.$slot->scheduled_time_london,
            $this->timezone()
        );
    }

    public function previewRecipientCount(HimamatSlot $slot): int
    {
        return (clone $this->recipientQuery($slot))->count();
    }

    /**
     * @return array{status: string, dispatch: HimamatReminderDispatch|null, recipient_count: int, skipped_reason?: string}
     */
    public function processDueSlot(HimamatSlot $slot, CarbonImmutable $nowLondon, bool $shouldQueue): array
    {
        /** @var HimamatReminderDispatch|null $existingDispatch */
        $existingDispatch = $slot->reminderDispatches
            ->firstWhere('channel', self::CHANNEL);

        if ($existingDispatch) {
            $this->refreshDispatch($existingDispatch, $nowLondon);

            return [
                'status' => 'already_tracked',
                'dispatch' => $existingDispatch->fresh(),
                'recipient_count' => (int) $existingDispatch->recipient_count,
            ];
        }

        $dueAtLondon = $this->dueAtLondon($slot);
        $lateByMinutes = max(0, $dueAtLondon->diffInMinutes($nowLondon));

        if ($lateByMinutes > $this->dispatchGraceMinutes()) {
            $dispatch = HimamatReminderDispatch::query()->create([
                'himamat_slot_id' => $slot->id,
                'channel' => self::CHANNEL,
                'due_at_london' => $dueAtLondon,
                'status' => HimamatReminderDispatch::STATUS_MISSED,
                'dispatch_started_at' => $nowLondon,
                'dispatch_finished_at' => $nowLondon,
                'last_error' => sprintf(
                    'Scheduler reached this slot %d minute(s) after the allowed catch-up window.',
                    $lateByMinutes
                ),
            ]);

            return [
                'status' => 'missed',
                'dispatch' => $dispatch,
                'recipient_count' => 0,
                'skipped_reason' => 'outside_grace_window',
            ];
        }

        $recipientCount = $this->previewRecipientCount($slot);
        $dispatch = HimamatReminderDispatch::query()->create([
            'himamat_slot_id' => $slot->id,
            'channel' => self::CHANNEL,
            'due_at_london' => $dueAtLondon,
            'status' => $recipientCount > 0
                ? HimamatReminderDispatch::STATUS_QUEUED
                : HimamatReminderDispatch::STATUS_COMPLETED,
            'recipient_count' => $recipientCount,
            'queued_count' => $recipientCount,
            'dispatch_started_at' => $nowLondon,
            'dispatch_finished_at' => $recipientCount === 0 ? $nowLondon : null,
        ]);

        if ($recipientCount === 0) {
            return [
                'status' => 'no_recipients',
                'dispatch' => $dispatch,
                'recipient_count' => 0,
            ];
        }

        $query = $this->recipientQuery($slot);
        $query->chunkById(500, function ($rows) use ($dispatch, $slot, $dueAtLondon, $shouldQueue): void {
            $memberIds = $rows->pluck('member_id')
                ->filter()
                ->map(static fn ($id): int => (int) $id)
                ->values()
                ->all();

            if ($memberIds === []) {
                return;
            }

            $timestamp = now();
            $insertRows = array_map(function (int $memberId) use ($dispatch, $slot, $dueAtLondon, $timestamp): array {
                return [
                    'member_id' => $memberId,
                    'himamat_slot_id' => $slot->id,
                    'channel' => self::CHANNEL,
                    'himamat_reminder_dispatch_id' => $dispatch->id,
                    'due_at_london' => $dueAtLondon->toDateTimeString(),
                    'status' => 'queued',
                    'attempt_count' => 0,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }, $memberIds);

            DB::table('member_himamat_reminder_deliveries')->insertOrIgnore($insertRows);

            $deliveries = MemberHimamatReminderDelivery::query()
                ->where('himamat_reminder_dispatch_id', $dispatch->id)
                ->where('himamat_slot_id', $slot->id)
                ->where('channel', self::CHANNEL)
                ->whereIn('member_id', $memberIds)
                ->get(['id']);

            foreach ($deliveries as $delivery) {
                if ($shouldQueue) {
                    SendHimamatReminderJob::dispatch((int) $delivery->id)
                        ->onQueue($this->reminderQueueName());

                    continue;
                }

                try {
                    (new SendHimamatReminderJob((int) $delivery->id))->handle(
                        app(UltraMsgService::class),
                        app(HimamatWhatsAppTemplateService::class)
                    );
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        }, 'member_himamat_preferences.id', 'preference_id');

        $this->refreshDispatch($dispatch, $nowLondon);

        return [
            'status' => 'dispatched',
            'dispatch' => $dispatch->fresh(),
            'recipient_count' => $recipientCount,
        ];
    }

    public function refreshOpenDispatches(?CarbonImmutable $nowLondon = null): void
    {
        $nowLondon ??= CarbonImmutable::now($this->timezone());

        HimamatReminderDispatch::query()
            ->where('channel', self::CHANNEL)
            ->whereIn('status', [
                HimamatReminderDispatch::STATUS_QUEUED,
                HimamatReminderDispatch::STATUS_PROCESSING,
            ])
            ->where('due_at_london', '>=', $nowLondon->startOfDay()->toDateTimeString())
            ->with('deliveries')
            ->orderBy('due_at_london')
            ->get()
            ->each(fn (HimamatReminderDispatch $dispatch) => $this->refreshDispatch($dispatch, $nowLondon));
    }

    public function refreshDispatch(HimamatReminderDispatch $dispatch, ?CarbonImmutable $nowLondon = null): HimamatReminderDispatch
    {
        $nowLondon ??= CarbonImmutable::now($this->timezone());

        if ($dispatch->status === HimamatReminderDispatch::STATUS_MISSED) {
            return $dispatch;
        }

        $counts = $dispatch->deliveries()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $queued = (int) ($counts['queued'] ?? 0);
        $sending = (int) ($counts['sending'] ?? 0);
        $sent = (int) ($counts['sent'] ?? 0);
        $failed = (int) ($counts['failed'] ?? 0);
        $skipped = (int) ($counts['skipped'] ?? 0);
        $pending = $queued + $sending;

        if ((int) $dispatch->recipient_count === 0) {
            $status = HimamatReminderDispatch::STATUS_COMPLETED;
        } elseif ($pending > 0) {
            $status = HimamatReminderDispatch::STATUS_PROCESSING;
        } elseif ($failed > 0) {
            $status = HimamatReminderDispatch::STATUS_COMPLETED_WITH_FAILURES;
        } else {
            $status = HimamatReminderDispatch::STATUS_COMPLETED;
        }

        $dispatch->forceFill([
            'status' => $status,
            'queued_count' => $queued,
            'sent_count' => $sent,
            'failed_count' => $failed,
            'skipped_count' => $skipped,
            'dispatch_finished_at' => $pending === 0
                ? ($dispatch->dispatch_finished_at ?: $nowLondon)
                : null,
        ])->save();

        return $dispatch;
    }

    private function recipientQuery(HimamatSlot $slot): \Illuminate\Database\Query\Builder
    {
        $preferenceColumn = $slot->slot_key.'_enabled';
        $testModeMemberId = $this->testModeMemberId();

        return DB::table('member_himamat_preferences')
            ->join('members', 'members.id', '=', 'member_himamat_preferences.member_id')
            ->leftJoin('member_himamat_reminder_deliveries as existing_deliveries', function ($join) use ($slot): void {
                $join->on('existing_deliveries.member_id', '=', 'member_himamat_preferences.member_id')
                    ->where('existing_deliveries.himamat_slot_id', '=', $slot->id)
                    ->where('existing_deliveries.channel', '=', self::CHANNEL);
            })
            ->whereNull('existing_deliveries.id')
            ->where('member_himamat_preferences.lent_season_id', $slot->himamatDay->lent_season_id)
            ->where('member_himamat_preferences.enabled', true)
            ->where("member_himamat_preferences.$preferenceColumn", true)
            ->when($testModeMemberId !== null, fn ($query) => $query->where('member_himamat_preferences.member_id', $testModeMemberId))
            ->where('members.whatsapp_confirmation_status', 'confirmed')
            ->whereNotNull('members.whatsapp_phone')
            ->where('members.whatsapp_phone', '!=', '')
            ->orderBy('member_himamat_preferences.id')
            ->select([
                'member_himamat_preferences.id as preference_id',
                'member_himamat_preferences.member_id',
            ]);
    }
}

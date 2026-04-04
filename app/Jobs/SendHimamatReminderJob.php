<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\HimamatSlot;
use App\Models\Member;
use App\Models\MemberHimamatPreference;
use App\Models\MemberHimamatReminderDelivery;
use App\Services\HimamatWhatsAppTemplateService;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendHimamatReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-himamat';

    public int $tries = 3;

    public int $timeout = 45;

    /** @var array<int> */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly int $himamatSlotId,
        public readonly string $dueAtLondon,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatWhatsAppTemplateService $templateService
    ): void {
        $member = Member::query()->find($this->memberId);
        if (! $member
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            return;
        }

        $slot = HimamatSlot::query()
            ->with('himamatDay')
            ->whereKey($this->himamatSlotId)
            ->where('is_published', true)
            ->first();

        if (! $slot || ! $slot->himamatDay || ! $slot->himamatDay->is_published) {
            return;
        }

        $preferences = MemberHimamatPreference::query()
            ->where('member_id', $member->id)
            ->where('lent_season_id', $slot->himamatDay->lent_season_id)
            ->first();

        if (! $preferences || ! $preferences->slotEnabled((string) $slot->slot_key)) {
            return;
        }

        $delivery = MemberHimamatReminderDelivery::query()->firstOrNew([
            'member_id' => $member->id,
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
        ]);

        if ($delivery->exists && $delivery->status === 'sent') {
            return;
        }

        $delivery->forceFill([
            'due_at_london' => CarbonImmutable::parse($this->dueAtLondon, config('himamat.timezone', 'Europe/London')),
            'status' => 'sending',
            'attempt_count' => (int) $delivery->attempt_count + 1,
            'last_attempt_at' => now(),
            'failure_reason' => null,
        ])->save();

        $message = $templateService->renderReminder(
            $member,
            $slot->himamatDay,
            $slot,
            $slot->himamatDay->accessPath($member, $slot->slot_key)
        )['message'];

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            $delivery->forceFill([
                'status' => 'failed',
                'failure_reason' => 'UltraMsg did not confirm delivery.',
            ])->save();

            throw new RuntimeException(sprintf(
                'UltraMsg did not confirm Himamat reminder delivery for member %d.',
                $member->id
            ));
        }

        $delivery->forceFill([
            'status' => 'sent',
            'delivered_at' => now(),
            'failure_reason' => null,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Queued Himamat reminder failed.', [
            'member_id' => $this->memberId,
            'himamat_slot_id' => $this->himamatSlotId,
            'due_at_london' => $this->dueAtLondon,
            'error' => $exception->getMessage(),
        ]);
    }
}

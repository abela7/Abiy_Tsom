<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MemberHimamatReminderDelivery;
use App\Services\UltraMsgService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendHimamatReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-himamat-reminders';

    public int $tries = 3;

    public int $timeout = 45;

    /** @var array<int> */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->onQueue((string) config('himamat.reminders.queues.reminders', self::QUEUE_NAME));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('himamat-whatsapp-reminders'),
        ];
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        \App\Services\HimamatWhatsAppTemplateService $templateService
    ): void {
        $delivery = MemberHimamatReminderDelivery::query()
            ->with(['member', 'himamatSlot.himamatDay'])
            ->find($this->deliveryId);

        if (! $delivery || $delivery->status === 'sent' || $delivery->status === 'skipped') {
            return;
        }

        $member = $delivery->member;
        if (! $member
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            $this->markSkipped($delivery, 'Member is no longer eligible for confirmed WhatsApp reminders.');

            return;
        }

        $slot = $delivery->himamatSlot;
        if (! $slot || ! $slot->himamatDay || ! $slot->is_published || ! $slot->himamatDay->is_published) {
            $this->markSkipped($delivery, 'The Himamat slot is no longer published.');

            return;
        }

        $preferences = \App\Models\MemberHimamatPreference::query()
            ->where('member_id', $member->id)
            ->where('lent_season_id', $slot->himamatDay->lent_season_id)
            ->first();

        if (! $preferences || ! $preferences->slotEnabled((string) $slot->slot_key)) {
            $this->markSkipped($delivery, 'The member disabled this Himamat slot before delivery.');

            return;
        }

        $delivery->forceFill([
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
            'delivery_id' => $this->deliveryId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function markSkipped(MemberHimamatReminderDelivery $delivery, string $reason): void
    {
        $delivery->forceFill([
            'status' => 'skipped',
            'failure_reason' => $reason,
            'last_attempt_at' => now(),
        ])->save();
    }
}

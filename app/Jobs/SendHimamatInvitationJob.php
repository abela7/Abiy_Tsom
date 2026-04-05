<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Member;
use App\Models\MemberHimamatInvitationDelivery;
use App\Services\HimamatInvitationTemplateService;
use App\Services\UltraMsgService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendHimamatInvitationJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-himamat-invitations';

    public int $tries = 3;

    public int $timeout = 45;

    /** @var array<int> */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly string $campaignKey,
        public readonly ?string $destinationPhone = null,
        public readonly bool $recordDelivery = true,
    ) {
        $this->onQueue((string) config('himamat.reminders.queues.invitations', self::QUEUE_NAME));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new RateLimited('himamat-whatsapp-invitations'),
        ];
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatInvitationTemplateService $templateService
    ): void {
        $member = Member::query()->find($this->memberId);
        if (! $member || ! $member->token || trim((string) $member->token) === '') {
            return;
        }

        $destinationPhone = trim((string) ($this->destinationPhone ?: $member->whatsapp_phone ?: ''));
        if ($destinationPhone === '') {
            return;
        }

        $delivery = null;
        if ($this->recordDelivery) {
            $delivery = MemberHimamatInvitationDelivery::query()->firstOrNew([
                'member_id' => $member->id,
                'campaign_key' => $this->campaignKey,
                'channel' => 'whatsapp',
            ]);

            if ($delivery->exists && $delivery->status === 'sent') {
                return;
            }

            $delivery->forceFill([
                'destination_phone' => $destinationPhone,
                'status' => 'sending',
                'attempt_count' => (int) $delivery->attempt_count + 1,
                'last_attempt_at' => now(),
                'failure_reason' => null,
            ])->save();
        }

        $message = $templateService->render(
            $member,
            route('member.himamat.access', [
                'token' => $member->token,
                'campaign' => $this->campaignKey,
            ])
        )['message'];

        if (! $ultraMsgService->sendTextMessage($destinationPhone, $message)) {
            if ($delivery) {
                $delivery->forceFill([
                    'status' => 'failed',
                    'failure_reason' => 'UltraMsg did not confirm delivery.',
                ])->save();
            }

            throw new RuntimeException(sprintf(
                'UltraMsg did not confirm Himamat invitation delivery for member %d.',
                $member->id
            ));
        }

        if ($delivery) {
            $delivery->forceFill([
                'status' => 'sent',
                'destination_phone' => $destinationPhone,
                'delivered_at' => now(),
                'failure_reason' => null,
            ])->save();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Queued Himamat invitation failed.', [
            'member_id' => $this->memberId,
            'campaign_key' => $this->campaignKey,
            'destination_phone' => $this->destinationPhone,
            'record_delivery' => $this->recordDelivery,
            'error' => $exception->getMessage(),
        ]);
    }
}

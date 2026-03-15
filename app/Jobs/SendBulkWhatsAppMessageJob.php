<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Member;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendBulkWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-bulk';

    public int $tries = 3;

    public int $timeout = 45;

    /**
     * @var array<int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly string $header,
        public readonly string $content,
        public readonly array $links = [],
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        WhatsAppTemplateService $whatsAppTemplateService
    ): void {
        $member = Member::query()
            ->activeConfirmedWhatsApp()
            ->find($this->memberId);

        if (! $member || ! $member->whatsapp_phone || trim((string) $member->whatsapp_phone) === '') {
            return;
        }

        $message = $whatsAppTemplateService
            ->renderBulkMessage($member, $this->header, $this->content, $this->links)['message'];

        if ($message === '') {
            return;
        }

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            throw new RuntimeException(sprintf(
                'UltraMsg did not confirm bulk message delivery for member %d.',
                $member->id
            ));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Queued bulk WhatsApp message failed.', [
            'member_id' => $this->memberId,
            'error' => $exception->getMessage(),
        ]);
    }
}

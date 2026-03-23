<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyContent;
use App\Models\Member;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendWhatsAppReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-reminders';

    public int $tries = 3;

    public int $timeout = 45;

    /**
     * @var array<int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly int $dailyContentId,
        public readonly string $today,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        WhatsAppTemplateService $whatsAppTemplateService
    ): void {
        $member = Member::query()->find($this->memberId);

        if (! $member
            || ! $member->whatsapp_reminder_enabled
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            return;
        }

        if (config('services.ultramsg.reminder_once_only', true)
            && $member->whatsapp_last_sent_date?->toDateString() === $this->today
        ) {
            return;
        }

        $dailyContent = DailyContent::query()
            ->whereKey($this->dailyContentId)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return;
        }

        $dayUrl = $dailyContent->memberDayUrl($member->token);

        $message = $whatsAppTemplateService
            ->renderDailyReminder($member, $dailyContent, $this->ensureHttpsUrl($dayUrl))['message'];

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            throw new RuntimeException(sprintf(
                'UltraMsg did not confirm reminder delivery for member %d.',
                $member->id
            ));
        }

        $member->forceFill([
            'whatsapp_last_sent_date' => $this->today,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Queued WhatsApp reminder failed.', [
            'member_id' => $this->memberId,
            'daily_content_id' => $this->dailyContentId,
            'today' => $this->today,
            'error' => $exception->getMessage(),
        ]);
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

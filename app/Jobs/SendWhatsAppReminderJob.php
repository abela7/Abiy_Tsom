<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyContent;
use App\Models\Member;
use App\Services\TelegramAuthService;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendWhatsAppReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-reminders';

    public int $tries = 1;

    public int $timeout = 45;

    public function __construct(
        public readonly int $memberId,
        public readonly int $dailyContentId,
        public readonly string $today,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        TelegramAuthService $telegramAuthService,
        WhatsAppTemplateService $whatsAppTemplateService
    ): void
    {
        $member = Member::query()->find($this->memberId);

        if (! $member
            || ! $member->whatsapp_reminder_enabled
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            return;
        }

        if ($member->whatsapp_last_sent_date?->toDateString() === $this->today) {
            return;
        }

        $dailyContent = DailyContent::query()
            ->whereKey($this->dailyContentId)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return;
        }

        $code = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $dailyContent], false)
        );

        $dayUrl = route('share.day', [
            'daily' => $dailyContent,
            'code' => $code,
        ]);

        $message = $whatsAppTemplateService
            ->renderDailyReminder($member, $dailyContent, $this->ensureHttpsUrl($dayUrl))['message'];

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            return;
        }

        $member->forceFill([
            'whatsapp_last_sent_date' => $this->today,
        ])->save();
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

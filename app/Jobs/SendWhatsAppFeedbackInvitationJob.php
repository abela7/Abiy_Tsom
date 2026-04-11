<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MemberFeedback;
use App\Services\UltraMsgService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendWhatsAppFeedbackInvitationJob implements ShouldQueue
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
        public readonly int $feedbackId,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(UltraMsgService $ultraMsg): void
    {
        $feedback = MemberFeedback::query()
            ->with('member')
            ->find($this->feedbackId);

        if (! $feedback || ! $feedback->member) {
            return;
        }

        $member = $feedback->member;

        if (
            ! $member->whatsapp_reminder_enabled
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            return;
        }

        $name = $member->baptism_name ?? 'Dear member';
        $url  = $this->ensureHttpsUrl($feedback->surveyUrl());

        $message = implode("\n", [
            "📊 *Post-Fasika Feedback*",
            "",
            "Dear {$name},",
            "",
            "Thank you for joining us this Abiy Tsom season! We would love to hear your thoughts. It takes only 2 minutes.",
            "",
            "👉 {$url}",
            "",
            "_Your feedback helps us serve you better in future seasons._",
        ]);

        if (! $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            throw new \RuntimeException(
                "UltraMsg could not deliver feedback invitation to member {$member->id}."
            );
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('WhatsApp feedback invitation failed.', [
            'feedback_id' => $this->feedbackId,
            'error'       => $exception->getMessage(),
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

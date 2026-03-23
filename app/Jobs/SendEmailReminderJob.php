<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\DailyReminderMail;
use App\Models\DailyContent;
use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

final class SendEmailReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'email-reminders';

    public int $tries = 3;

    public int $timeout = 60;

    /** @var array<int> */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly int $dailyContentId,
        public readonly string $today,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(): void
    {
        $member = Member::query()->find($this->memberId);

        if (! $member
            || ! $member->email_reminder_enabled
            || ! $member->email
            || ! $member->email_verified_at
        ) {
            return;
        }

        if ($member->email_last_sent_date?->toDateString() === $this->today) {
            return;
        }

        $dailyContent = DailyContent::query()
            ->whereKey($this->dailyContentId)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return;
        }

        $dayUrl = $this->ensureHttpsUrl($dailyContent->memberDayUrl($member->token));

        Mail::to($member->email)->send(new DailyReminderMail($member, $dailyContent, $dayUrl));

        $member->forceFill([
            'email_last_sent_date' => $this->today,
        ])->save();
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Queued email reminder failed.', [
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

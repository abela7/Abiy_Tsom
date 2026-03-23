<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendEmailReminderJob;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Send one daily email reminder to opted-in email-verified members.
 *
 * Runs at 04:00 Europe/London (= 07:00 Ethiopia) — a fixed time,
 * unlike WhatsApp reminders where each member picks their own time.
 */
class SendEmailReminders extends Command
{
    protected $signature = 'reminders:send-email
        {--dry-run : Preview due recipients without sending emails}
        {--queue : Dispatch due reminders to the queue instead of sending inline}';

    protected $description = 'Send daily email reminders to email-verified members';

    public function handle(): int
    {
        $lock = Cache::lock('reminders:send-email', 3600);

        if (! $lock->get()) {
            $this->warn('Another email reminder run is already in progress.');

            return self::SUCCESS;
        }

        try {
            $timezone = 'Europe/London';
            $nowLondon = CarbonImmutable::now($timezone);
            $today = $nowLondon->toDateString();
            $dryRun = (bool) $this->option('dry-run');
            $shouldQueue = (bool) $this->option('queue');

            if ($shouldQueue && config('queue.default') === 'sync') {
                $this->warn('Queue mode is enabled, but QUEUE_CONNECTION is sync. Emails will still send inline.');
            }

            $season = LentSeason::active();
            if (! $season) {
                $this->line('No active Lent season. Nothing to send.');

                return self::SUCCESS;
            }

            $dailyContent = DailyContent::query()
                ->where('lent_season_id', $season->id)
                ->whereDate('date', $today)
                ->where('is_published', true)
                ->first();

            if (! $dailyContent) {
                $this->line(sprintf(
                    'No published daily content for %s (%s).',
                    $today,
                    $timezone
                ));

                return self::SUCCESS;
            }

            $dueMembersQuery = Member::query()
                ->where('email_reminder_enabled', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->whereNotNull('email_verified_at')
                ->where(function ($query) use ($today): void {
                    $query->whereNull('email_last_sent_date')
                        ->orWhere('email_last_sent_date', '<', $today);
                });

            $dueCount = (clone $dueMembersQuery)->count();
            if ($dueCount === 0) {
                $this->line('No email reminders due today.');

                return self::SUCCESS;
            }

            $this->line(sprintf(
                '%s %d email reminder(s) for day %d.',
                $shouldQueue ? 'Dispatching' : 'Processing',
                $dueCount,
                $dailyContent->day_number
            ));

            $processedCount = 0;
            $failedCount = 0;

            $dueMembersQuery
                ->orderBy('id')
                ->chunkById(200, function ($members) use (
                    $dailyContent,
                    $today,
                    $dryRun,
                    $shouldQueue,
                    &$processedCount,
                    &$failedCount
                ): void {
                    foreach ($members as $member) {
                        if ($dryRun) {
                            $processedCount++;

                            continue;
                        }

                        if ($shouldQueue) {
                            SendEmailReminderJob::dispatch($member->id, $dailyContent->id, $today)
                                ->onQueue(SendEmailReminderJob::QUEUE_NAME);

                            $processedCount++;

                            continue;
                        }

                        try {
                            $dayUrl = $this->ensureHttpsUrl($dailyContent->memberDayUrl($member->token));

                            Mail::to($member->email)->send(
                                new \App\Mail\DailyReminderMail($member, $dailyContent, $dayUrl)
                            );

                            $member->forceFill([
                                'email_last_sent_date' => $today,
                            ])->save();

                            $processedCount++;
                        } catch (\Throwable $e) {
                            report($e);
                            $failedCount++;
                        }
                    }
                });

            if ($dryRun) {
                $this->line(sprintf('Finished. Due: %d (dry-run)', $processedCount));

                return self::SUCCESS;
            }

            if ($shouldQueue) {
                $this->line(sprintf('Finished. Dispatched: %d', $processedCount));

                return self::SUCCESS;
            }

            $this->line(sprintf('Finished. Sent: %d, Failed: %d', $processedCount, $failedCount));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppReminderJob;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\TelegramAuthService;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Send one WhatsApp reminder per day to opted-in members.
 */
class SendWhatsAppReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reminders:send-whatsapp
        {--dry-run : Preview due recipients without sending WhatsApp messages}
        {--queue : Dispatch due reminders to the queue instead of sending inline}';

    /**
     * @var string
     */
    protected $description = 'Send daily WhatsApp reminders to members';

    public function handle(
        UltraMsgService $ultraMsgService,
        TelegramAuthService $telegramAuthService,
        WhatsAppTemplateService $whatsAppTemplateService
    ): int {
        $lock = Cache::lock('reminders:send-whatsapp', 3600);

        if (! $lock->get()) {
            $this->warn('Another WhatsApp reminder run is already in progress.');

            return self::SUCCESS;
        }

        try {
            if (! $ultraMsgService->isConfigured()) {
                $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

                return self::FAILURE;
            }

            $timezone = 'Europe/London';
            $nowLondon = CarbonImmutable::now($timezone);
            $today = $nowLondon->toDateString();
            $currentTime = $nowLondon->format('H:i:00');
            $dryRun = (bool) $this->option('dry-run');
            $shouldQueue = (bool) $this->option('queue');

            if ($shouldQueue && config('queue.default') === 'sync') {
                $this->warn('Queue mode is enabled, but QUEUE_CONNECTION is sync. Reminders will still run inline until the queue connection and worker are configured.');
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
                ->where('whatsapp_reminder_enabled', true)
                ->where('whatsapp_confirmation_status', 'confirmed')
                ->whereNotNull('whatsapp_phone')
                ->where('whatsapp_phone', '!=', '')
                ->whereNotNull('whatsapp_reminder_time')
                ->where('whatsapp_reminder_time', $currentTime);

            if (config('services.ultramsg.reminder_once_only', true)) {
                $dueMembersQuery->where(function ($query) use ($today): void {
                    $query->whereNull('whatsapp_last_sent_date')
                        ->orWhere('whatsapp_last_sent_date', '<', $today);
                });
            }

            $dueCount = (clone $dueMembersQuery)->count();
            if ($dueCount === 0) {
                $this->line(sprintf(
                    'No reminders due at %s (%s).',
                    $nowLondon->format('H:i'),
                    $timezone
                ));

                return self::SUCCESS;
            }

            $this->line(sprintf(
                '%s %d reminder(s) for day %d at %s (%s).',
                $shouldQueue ? 'Dispatching' : 'Processing',
                $dueCount,
                $dailyContent->day_number,
                $nowLondon->format('H:i'),
                $timezone
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
                    $ultraMsgService,
                    $telegramAuthService,
                    $whatsAppTemplateService,
                    &$processedCount,
                    &$failedCount
                ): void {
                    foreach ($members as $member) {
                        if ($dryRun) {
                            $processedCount++;

                            continue;
                        }

                        if ($shouldQueue) {
                            SendWhatsAppReminderJob::dispatch($member->id, $dailyContent->id, $today)
                                ->onQueue(SendWhatsAppReminderJob::QUEUE_NAME);

                            $processedCount++;

                            continue;
                        }

                        $code = $telegramAuthService->createCode(
                            $member,
                            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
                            $dailyContent->memberDayUrl(false)
                        );
                        $dayUrl = route('share.day', [
                            'daily' => $dailyContent,
                            'code' => $code,
                        ]);
                        $dayUrl = $this->ensureHttpsUrl($dayUrl);
                        $message = $whatsAppTemplateService
                            ->renderDailyReminder($member, $dailyContent, $dayUrl)['message'];

                        $sent = $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message);
                        if (! $sent) {
                            $failedCount++;

                            continue;
                        }

                        $member->forceFill([
                            'whatsapp_last_sent_date' => $today,
                        ])->save();

                        $processedCount++;
                    }
                });

            if ($dryRun) {
                $this->line(sprintf(
                    'Finished reminders. Due: %d (dry-run)%s',
                    $processedCount,
                    $shouldQueue ? ' [queue mode]' : ''
                ));

                return self::SUCCESS;
            }

            if ($shouldQueue) {
                $this->line(sprintf(
                    'Finished reminders. Dispatched: %d',
                    $processedCount
                ));

                return self::SUCCESS;
            }

            $this->line(sprintf(
                'Finished reminders. Sent: %d, Failed: %d',
                $processedCount,
                $failedCount
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }

    /**
     * Ensure reminder links are sent as full HTTPS URLs
     * on non-local environments for best WhatsApp clickability.
     */
    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

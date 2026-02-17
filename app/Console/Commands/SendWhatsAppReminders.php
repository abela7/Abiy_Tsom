<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Lang;

/**
 * Send one WhatsApp reminder per day to opted-in members.
 */
class SendWhatsAppReminders extends Command
{
    /**
     * @var string
     */
    protected $signature = 'reminders:send-whatsapp
        {--dry-run : Preview due recipients without sending WhatsApp messages}';

    /**
     * @var string
     */
    protected $description = 'Send daily WhatsApp reminders to members';

    public function handle(UltraMsgService $ultraMsgService): int
    {
        if (! $ultraMsgService->isConfigured()) {
            $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

            return self::FAILURE;
        }

        $timezone = 'Europe/London';
        $nowLondon = CarbonImmutable::now($timezone);
        $today = $nowLondon->toDateString();
        $currentTime = $nowLondon->format('H:i:00');
        $dryRun = (bool) $this->option('dry-run');

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
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->whereNotNull('whatsapp_reminder_time')
            ->where('whatsapp_reminder_time', $currentTime)
            ->where(function ($query) use ($today): void {
                $query->whereNull('whatsapp_last_sent_date')
                    ->orWhere('whatsapp_last_sent_date', '<', $today);
            });

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
            'Processing %d reminder(s) for day %d at %s (%s).',
            $dueCount,
            $dailyContent->day_number,
            $nowLondon->format('H:i'),
            $timezone
        ));

        $sentCount = 0;
        $failedCount = 0;

        $dueMembersQuery
            ->orderBy('id')
            ->chunkById(200, function ($members) use (
                $dailyContent,
                $today,
                $dryRun,
                $ultraMsgService,
                &$sentCount,
                &$failedCount
            ): void {
                foreach ($members as $member) {
                    $dayUrl = route('member.day', ['daily' => $dailyContent]).'?token='.urlencode((string) $member->token);
                    $message = Lang::get('app.whatsapp_daily_reminder_message', [
                        'day' => $dailyContent->day_number,
                        'url' => $dayUrl,
                    ], 'en');

                    if ($dryRun) {
                        $sentCount++;

                        continue;
                    }

                    $sent = $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message);
                    if (! $sent) {
                        $failedCount++;

                        continue;
                    }

                    $member->forceFill([
                        'whatsapp_last_sent_date' => $today,
                    ])->save();

                    $sentCount++;
                }
            });

        $this->line(sprintf(
            'Finished reminders. Sent: %d, Failed: %d%s',
            $sentCount,
            $failedCount,
            $dryRun ? ' (dry-run)' : ''
        ));

        return self::SUCCESS;
    }
}

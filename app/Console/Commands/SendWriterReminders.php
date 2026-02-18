<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * Send WhatsApp reminders to writers/editors assigned to prepare tomorrow's content.
 */
class SendWriterReminders extends Command
{
    protected $signature = 'reminders:send-writer
        {--dry-run : Preview without sending WhatsApp messages}';

    protected $description = 'Remind assigned writers about tomorrow\'s content preparation';

    public function handle(UltraMsgService $ultraMsgService): int
    {
        if (! $ultraMsgService->isConfigured()) {
            $this->error('UltraMsg is not configured.');

            return self::FAILURE;
        }

        $timezone = 'Europe/London';
        $nowLondon = CarbonImmutable::now($timezone);
        $tomorrow = $nowLondon->addDay()->toDateString();
        $dryRun = (bool) $this->option('dry-run');

        $season = LentSeason::active();
        if (! $season) {
            $this->line('No active Lent season.');

            return self::SUCCESS;
        }

        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $tomorrow)
            ->with('assignedTo')
            ->first();

        if (! $dailyContent || ! $dailyContent->assigned_to_id) {
            $this->line(sprintf('No writer assigned for %s (tomorrow).', $tomorrow));

            return self::SUCCESS;
        }

        $assignee = $dailyContent->assignedTo;

        if (empty($assignee->whatsapp_phone)) {
            $this->line(sprintf('%s is assigned but has no WhatsApp number.', $assignee->name));

            return self::SUCCESS;
        }

        $message = __('app.writer_reminder_message', [
            'name' => $assignee->name,
            'day' => $dailyContent->day_number,
            'date' => $dailyContent->date->format('l, M j'),
        ]);

        if ($dryRun) {
            $this->line(sprintf('Would send to %s: %s', $assignee->whatsapp_phone, $message));

            return self::SUCCESS;
        }

        $sent = $ultraMsgService->sendTextMessage($assignee->whatsapp_phone, $message);

        if ($sent) {
            $this->line(sprintf('Sent reminder to %s for Day %d.', $assignee->name, $dailyContent->day_number));
        } else {
            $this->error('Failed to send WhatsApp message.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

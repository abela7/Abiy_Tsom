<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\WriterReminderService;
use Illuminate\Console\Command;

/**
 * Send WhatsApp reminders to writers/editors assigned to prepare tomorrow's content.
 */
class SendWriterReminders extends Command
{
    protected $signature = 'reminders:send-writer
        {--dry-run : Preview without sending WhatsApp messages}';

    protected $description = 'Remind assigned writers about tomorrow\'s content preparation';

    public function handle(WriterReminderService $writerReminderService): int
    {
        $result = $writerReminderService->sendReminderForTomorrow(
            dryRun: (bool) $this->option('dry-run')
        );

        if ($result['sent']) {
            $this->line($result['message']);

            return self::SUCCESS;
        }

        $this->line($result['message']);

        return $result['message'] === __('app.writer_reminder_send_failed')
            ? self::FAILURE
            : self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LentSeason;
use App\Services\HimamatReminderDispatchService;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendHimamatWhatsAppReminders extends Command
{
    protected $signature = 'himamat:send-whatsapp-reminders
        {--dry-run : Preview due recipients without sending WhatsApp messages}
        {--queue : Dispatch due reminders to the queue instead of sending inline}';

    protected $description = 'Send Holy Week WhatsApp reminders using the separate Himamat pipeline';

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatReminderDispatchService $dispatches
    ): int {
        $lock = Cache::lock('himamat:send-whatsapp-reminders', 3600);

        if (! $lock->get()) {
            $this->warn('Another Himamat reminder run is already in progress.');

            return self::SUCCESS;
        }

        try {
            if (! $ultraMsgService->isConfigured()) {
                $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

                return self::FAILURE;
            }

            $timezone = $dispatches->timezone();
            $nowLondon = CarbonImmutable::now($timezone);
            $dryRun = (bool) $this->option('dry-run');
            $shouldQueue = (bool) $this->option('queue');

            if ($shouldQueue && config('queue.default') === 'sync') {
                $this->warn('Queue mode is enabled, but QUEUE_CONNECTION is sync. Himamat reminders will still run inline until a queue worker is configured.');
            }

            $season = LentSeason::active();
            if (! $season) {
                $this->line('No active Lent season. Nothing to send.');

                return self::SUCCESS;
            }

            $dispatches->refreshOpenDispatches($nowLondon);

            $dueSlots = $dispatches->dueSlots($season, $nowLondon);
            if ($dueSlots->isEmpty()) {
                $this->line(sprintf(
                    'No Himamat slots are due as of %s (%s).',
                    $nowLondon->format('H:i'),
                    $timezone
                ));

                return self::SUCCESS;
            }

            $processedSlots = 0;
            $recipientCount = 0;
            $missedSlots = 0;

            foreach ($dueSlots as $slot) {
                if (! $slot->himamatDay) {
                    continue;
                }

                $dueAtLondon = $dispatches->dueAtLondon($slot);

                if ($dryRun) {
                    $previewCount = $dispatches->previewRecipientCount($slot);
                    if ($previewCount === 0) {
                        continue;
                    }

                    $processedSlots++;
                    $recipientCount += $previewCount;

                    $this->line(sprintf(
                        '[dry-run] %s %s at %s (%s) -> %d recipient(s)',
                        $slot->himamatDay->slug,
                        $slot->slot_key,
                        $dueAtLondon->format('H:i'),
                        $timezone,
                        $previewCount
                    ));

                    continue;
                }

                $result = $dispatches->processDueSlot($slot, $nowLondon, $shouldQueue);
                if (in_array($result['status'], ['already_tracked', 'no_recipients'], true)) {
                    continue;
                }

                if ($result['status'] === 'missed') {
                    $missedSlots++;
                    $processedSlots++;
                    $this->warn(sprintf(
                        'Marked %s %s as missed. The scheduler reached it after the catch-up window.',
                        $slot->himamatDay->slug,
                        $slot->slot_key
                    ));

                    continue;
                }

                $processedSlots++;
                $recipientCount += $result['recipient_count'];

                $this->line(sprintf(
                    '%s %d Himamat reminder(s) for %s %s due at %s (%s).',
                    $shouldQueue ? 'Dispatched' : 'Processed',
                    $result['recipient_count'],
                    $slot->himamatDay->slug,
                    $slot->slot_key,
                    $dueAtLondon->format('H:i'),
                    $timezone
                ));
            }

            if ($dryRun) {
                $this->line(sprintf(
                    'Finished Himamat reminder preview. Slots: %d, Recipients: %d',
                    $processedSlots,
                    $recipientCount
                ));

                return self::SUCCESS;
            }

            $dispatches->refreshOpenDispatches($nowLondon);

            $this->line(sprintf(
                'Finished Himamat reminders. Slots handled: %d, Recipients queued/processed: %d, Missed slots: %d',
                $processedSlots,
                $recipientCount,
                $missedSlots
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}

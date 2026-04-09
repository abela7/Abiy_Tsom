<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\HimamatReminderDispatch;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Services\HimamatReminderDispatchService;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class RetryMissedHimamatSlot extends Command
{
    protected $signature = 'himamat:retry-missed-slot
        {--slot= : Slot key to retry, e.g. intro, third, sixth, ninth, eleventh}
        {--day= : Day slug to target (defaults to today\'s published day)}
        {--dry-run : Preview recipient count without sending}
        {--queue : Dispatch to queue instead of sending inline}';

    protected $description = 'Force-retry a missed Himamat slot reminder, bypassing the grace window';

    private const CHANNEL = 'whatsapp';

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatReminderDispatchService $dispatches
    ): int {
        if (! $ultraMsgService->isConfigured()) {
            $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

            return self::FAILURE;
        }

        $slotKey = (string) $this->option('slot');
        if ($slotKey === '') {
            $this->error('--slot is required. Example: --slot=intro');

            return self::FAILURE;
        }

        $season = LentSeason::active();
        if (! $season) {
            $this->error('No active Lent season found.');

            return self::FAILURE;
        }

        $timezone = $dispatches->timezone();
        $nowLondon = CarbonImmutable::now($timezone);
        $daySlug = (string) $this->option('day');

        $slot = HimamatSlot::query()
            ->where('slot_key', $slotKey)
            ->where('is_published', true)
            ->whereHas('himamatDay', function ($q) use ($season, $nowLondon, $daySlug): void {
                $q->where('lent_season_id', $season->id)
                    ->where('is_published', true);
                if ($daySlug !== '') {
                    $q->where('slug', $daySlug);
                } else {
                    $q->whereDate('date', $nowLondon->toDateString());
                }
            })
            ->with([
                'himamatDay',
                'reminderDispatches' => fn ($q) => $q->where('channel', self::CHANNEL),
            ])
            ->first();

        if (! $slot) {
            $this->error(sprintf(
                'No published "%s" slot found for %s.',
                $slotKey,
                $daySlug !== '' ? 'day "'.$daySlug.'"' : $nowLondon->toDateString()
            ));

            return self::FAILURE;
        }

        $dueAtLondon = $dispatches->dueAtLondon($slot);

        $this->line(sprintf(
            'Slot: %s / %s — originally scheduled at %s (%s)',
            $slot->himamatDay->slug,
            $slotKey,
            $dueAtLondon->format('H:i'),
            $timezone
        ));

        /** @var HimamatReminderDispatch|null $existingDispatch */
        $existingDispatch = $slot->reminderDispatches->firstWhere('channel', self::CHANNEL);

        if ($existingDispatch) {
            if ($existingDispatch->status !== HimamatReminderDispatch::STATUS_MISSED) {
                $this->error(sprintf(
                    'Slot already has a dispatch with status "%s". Cannot retry a non-missed dispatch.',
                    $existingDispatch->status
                ));

                return self::FAILURE;
            }

            $this->warn('Found existing MISSED dispatch — will delete it to allow retry.');

            if (! (bool) $this->option('dry-run')) {
                $existingDispatch->delete();
                $slot->load(['reminderDispatches' => fn ($q) => $q->where('channel', self::CHANNEL)]);
            }
        }

        $recipientCount = $dispatches->previewRecipientCount($slot);
        $this->line(sprintf('Eligible recipients: %d', $recipientCount));

        if ((bool) $this->option('dry-run')) {
            $this->info('[dry-run] No messages sent.');

            return self::SUCCESS;
        }

        if ($recipientCount === 0) {
            $this->warn('No eligible recipients. Everyone already received this slot or no one is opted in.');

            return self::SUCCESS;
        }

        // Pass dueAtLondon as nowLondon so lateByMinutes = 0 — grace window is satisfied
        $result = $dispatches->processDueSlot($slot, $dueAtLondon, (bool) $this->option('queue'));

        $this->info(sprintf(
            'Done — status: %s, recipients: %d',
            $result['status'],
            $result['recipient_count']
        ));

        return self::SUCCESS;
    }
}

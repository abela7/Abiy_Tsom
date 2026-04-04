<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendHimamatReminderJob;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\MemberHimamatPreference;
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

    public function handle(UltraMsgService $ultraMsgService): int
    {
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

            $timezone = (string) config('himamat.timezone', 'Europe/London');
            $nowLondon = CarbonImmutable::now($timezone);
            $today = $nowLondon->toDateString();
            $currentTime = $nowLondon->format('H:i:00');
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

            $slot = HimamatSlot::query()
                ->where('is_published', true)
                ->where('scheduled_time_london', $currentTime)
                ->whereHas('himamatDay', function ($query) use ($season, $today): void {
                    $query->where('lent_season_id', $season->id)
                        ->where('is_published', true)
                        ->whereDate('date', $today);
                })
                ->with('himamatDay')
                ->first();

            if (! $slot || ! $slot->himamatDay) {
                $this->line(sprintf(
                    'No Himamat slot due at %s (%s).',
                    $nowLondon->format('H:i'),
                    $timezone
                ));

                return self::SUCCESS;
            }

            $preferenceColumn = $slot->slot_key.'_enabled';

            $dueQuery = MemberHimamatPreference::query()
                ->with('member')
                ->where('lent_season_id', $season->id)
                ->where('enabled', true)
                ->where($preferenceColumn, true)
                ->whereHas('member', function ($query): void {
                    $query->where('whatsapp_confirmation_status', 'confirmed')
                        ->whereNotNull('whatsapp_phone')
                        ->where('whatsapp_phone', '!=', '');
                });

            $dueCount = (clone $dueQuery)->count();
            if ($dueCount === 0) {
                $this->line(sprintf(
                    'No Himamat reminders due at %s (%s).',
                    $nowLondon->format('H:i'),
                    $timezone
                ));

                return self::SUCCESS;
            }

            $this->line(sprintf(
                '%s %d Himamat reminder(s) for %s at %s (%s).',
                $shouldQueue ? 'Dispatching' : 'Processing',
                $dueCount,
                $slot->himamatDay->slug,
                $nowLondon->format('H:i'),
                $timezone
            ));

            $processed = 0;
            $failed = 0;
            $dueAtLondon = $slot->himamatDay->date->copy()
                ->setTimezone($timezone)
                ->setTimeFromTimeString((string) $slot->scheduled_time_london)
                ->toIso8601String();

            $dueQuery
                ->orderBy('id')
                ->chunkById(200, function ($preferences) use ($slot, $dueAtLondon, $dryRun, $shouldQueue, &$processed, &$failed): void {
                    foreach ($preferences as $preference) {
                        $member = $preference->member;
                        if (! $member) {
                            continue;
                        }

                        $alreadySent = $member->himamatReminderDeliveries()
                            ->where('himamat_slot_id', $slot->id)
                            ->where('channel', 'whatsapp')
                            ->where('status', 'sent')
                            ->exists();

                        if ($alreadySent) {
                            continue;
                        }

                        if ($dryRun) {
                            $processed++;

                            continue;
                        }

                        if ($shouldQueue) {
                            SendHimamatReminderJob::dispatch($member->id, $slot->id, $dueAtLondon)
                                ->onQueue(SendHimamatReminderJob::QUEUE_NAME);
                            $processed++;

                            continue;
                        }

                        try {
                            (new SendHimamatReminderJob($member->id, $slot->id, $dueAtLondon))->handle(
                                app(UltraMsgService::class),
                                app(\App\Services\HimamatWhatsAppTemplateService::class)
                            );
                            $processed++;
                        } catch (\Throwable $exception) {
                            report($exception);
                            $failed++;
                        }
                    }
                });

            if ($dryRun) {
                $this->line(sprintf(
                    'Finished Himamat reminders. Due: %d (dry-run)%s',
                    $processed,
                    $shouldQueue ? ' [queue mode]' : ''
                ));

                return self::SUCCESS;
            }

            if ($shouldQueue) {
                $this->line(sprintf(
                    'Finished Himamat reminders. Dispatched: %d',
                    $processed
                ));

                return self::SUCCESS;
            }

            $this->line(sprintf(
                'Finished Himamat reminders. Sent: %d, Failed: %d',
                $processed,
                $failed
            ));

            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}

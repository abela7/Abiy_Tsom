<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendWhatsAppReminderJob;
use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatReminderDelivery;
use App\Services\HimamatWhatsAppTemplateService;
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
        WhatsAppTemplateService $whatsAppTemplateService,
        HimamatWhatsAppTemplateService $himamatWhatsAppTemplateService
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

            $himamatDay = $this->resolvePublishedHimamatDay($dailyContent);
            $dueHimamatSlot = $himamatDay
                ? $this->resolveDueHimamatSlot($himamatDay, $currentTime)
                : null;
            $isHimamatIntroWindow = $dueHimamatSlot?->slot_key === 'intro';
            $isHimamatSlotWindow = $dueHimamatSlot !== null && ! $isHimamatIntroWindow;

            if ($himamatDay !== null && $dueHimamatSlot === null) {
                $this->line(sprintf(
                    'No reminders due at %s (%s). Himamat reminders only send on their configured slot times.',
                    $nowLondon->format('H:i'),
                    $timezone
                ));

                return self::SUCCESS;
            }

            $dueMembersQuery = $dueHimamatSlot
                ? $this->buildHimamatSlotRecipientsQuery($season->id, $dueHimamatSlot)
                : Member::query()
                    ->where('whatsapp_reminder_enabled', true)
                    ->where('whatsapp_confirmation_status', 'confirmed')
                    ->whereNotNull('whatsapp_phone')
                    ->where('whatsapp_phone', '!=', '')
                    ->whereNotNull('whatsapp_reminder_time')
                    ->where('whatsapp_reminder_time', $currentTime);

            if ($dueHimamatSlot === null && config('services.ultramsg.reminder_once_only', true)) {
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
                '%s %d reminder(s) for day %d at %s (%s)%s.',
                $shouldQueue ? 'Dispatching' : 'Processing',
                $dueCount,
                $dailyContent->day_number,
                $nowLondon->format('H:i'),
                $timezone,
                $dueHimamatSlot ? ' [Himamat '.(string) $dueHimamatSlot->slot_key.']' : ''
            ));

            $processedCount = 0;
            $failedCount = 0;

            $dueMembersQuery
                ->orderBy('id')
                ->chunkById(200, function ($members) use (
                    $dailyContent,
                    $himamatDay,
                    $dueHimamatSlot,
                    $isHimamatIntroWindow,
                    $isHimamatSlotWindow,
                    $nowLondon,
                    $dryRun,
                    $shouldQueue,
                    $ultraMsgService,
                    $whatsAppTemplateService,
                    $himamatWhatsAppTemplateService,
                    &$processedCount,
                    &$failedCount
                ): void {
                    foreach ($members as $member) {
                        if ($dryRun) {
                            $processedCount++;

                            continue;
                        }

                        if ($shouldQueue) {
                            $deliveryId = null;

                            if ($dueHimamatSlot) {
                                $delivery = MemberHimamatReminderDelivery::query()->firstOrCreate(
                                    [
                                        'member_id' => $member->id,
                                        'himamat_slot_id' => $dueHimamatSlot->id,
                                        'channel' => 'whatsapp',
                                    ],
                                    [
                                        'due_at_london' => $nowLondon,
                                        'status' => 'queued',
                                        'attempt_count' => 0,
                                    ]
                                );

                                if ($delivery->wasRecentlyCreated === false) {
                                    $processedCount++;

                                    continue;
                                }

                                $deliveryId = $delivery->id;
                            }

                            SendWhatsAppReminderJob::dispatch(
                                $member->id,
                                $dailyContent->id,
                                $today,
                                $isHimamatIntroWindow
                                    ? SendWhatsAppReminderJob::VARIANT_HIMAMAT_INTRO
                                    : ($isHimamatSlotWindow
                                        ? SendWhatsAppReminderJob::VARIANT_HIMAMAT_SLOT
                                        : SendWhatsAppReminderJob::VARIANT_DAILY),
                                $dueHimamatSlot?->id,
                                $deliveryId
                            )
                                ->onQueue(SendWhatsAppReminderJob::QUEUE_NAME);

                            $processedCount++;

                            continue;
                        }

                        $dayUrl = $this->ensureHttpsUrl($dailyContent->memberDayUrl($member->token));
                        $message = null;
                        $delivery = null;

                        if ($isHimamatIntroWindow && $dueHimamatSlot && $himamatDay) {
                            $delivery = MemberHimamatReminderDelivery::query()->firstOrCreate(
                                [
                                    'member_id' => $member->id,
                                    'himamat_slot_id' => $dueHimamatSlot->id,
                                    'channel' => 'whatsapp',
                                ],
                                [
                                    'due_at_london' => $nowLondon,
                                    'status' => 'sending',
                                    'attempt_count' => 1,
                                    'last_attempt_at' => now(),
                                ]
                            );

                            if ($delivery->wasRecentlyCreated === false) {
                                $processedCount++;

                                continue;
                            }

                            $message = $whatsAppTemplateService
                                ->renderHimamatIntroReminder($member, $dailyContent, $himamatDay, $dayUrl)['message'];
                        } elseif ($isHimamatSlotWindow && $dueHimamatSlot && $himamatDay) {
                            $delivery = MemberHimamatReminderDelivery::query()->firstOrCreate(
                                [
                                    'member_id' => $member->id,
                                    'himamat_slot_id' => $dueHimamatSlot->id,
                                    'channel' => 'whatsapp',
                                ],
                                [
                                    'due_at_london' => $nowLondon,
                                    'status' => 'sending',
                                    'attempt_count' => 1,
                                    'last_attempt_at' => now(),
                                ]
                            );

                            if ($delivery->wasRecentlyCreated === false) {
                                $processedCount++;

                                continue;
                            }

                            $message = $himamatWhatsAppTemplateService->renderReminder(
                                $member,
                                $himamatDay,
                                $dueHimamatSlot,
                                $this->himamatSlotDayUrl($dailyContent, $member, $dueHimamatSlot->slot_key)
                            )['message'];
                        } else {
                            $message = $whatsAppTemplateService->renderDailyReminder($member, $dailyContent, $dayUrl)['message'];
                        }

                        $sent = $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message);
                        if (! $sent) {
                            if ($delivery) {
                                $delivery->forceFill([
                                    'status' => 'failed',
                                    'failure_reason' => 'UltraMsg did not confirm delivery.',
                                    'last_attempt_at' => now(),
                                ])->save();
                            }

                            $failedCount++;

                            continue;
                        }

                        if ($delivery) {
                            $delivery->forceFill([
                                'status' => 'sent',
                                'delivered_at' => now(),
                                'failure_reason' => null,
                            ])->save();
                        }

                        if (! $dueHimamatSlot || $isHimamatIntroWindow) {
                            $member->forceFill([
                                'whatsapp_last_sent_date' => $today,
                            ])->save();
                        }

                        $processedCount++;
                    }
                }, 'members.id', 'id');

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

    private function resolvePublishedHimamatDay(DailyContent $dailyContent): ?HimamatDay
    {
        if ($dailyContent->day_number < 50 || $dailyContent->day_number > 55) {
            return null;
        }

        return HimamatDay::query()
            ->where('lent_season_id', $dailyContent->lent_season_id)
            ->whereDate('date', $dailyContent->date)
            ->where('is_published', true)
            ->with([
                'slots' => fn ($query) => $query
                    ->where('is_published', true)
                    ->orderBy('slot_order'),
            ])
            ->first();
    }

    private function resolveDueHimamatSlot(HimamatDay $himamatDay, string $currentTime): ?HimamatSlot
    {
        return $himamatDay->slots->first(
            fn (HimamatSlot $slot): bool => (string) $slot->scheduled_time_london === $currentTime
        );
    }

    private function buildHimamatSlotRecipientsQuery(int $seasonId, HimamatSlot $slot)
    {
        return Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->where('whatsapp_confirmation_status', 'confirmed')
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->where(function ($query) use ($seasonId): void {
                $query
                    ->whereDoesntHave('himamatPreferences', function ($preferenceQuery) use ($seasonId): void {
                        $preferenceQuery->where('lent_season_id', $seasonId);
                    })
                    ->orWhereHas('himamatPreferences', function ($preferenceQuery) use ($seasonId, $slot): void {
                        $preferenceQuery
                            ->where('lent_season_id', $seasonId)
                            ->where('enabled', true)
                            ->where($slot->slot_key.'_enabled', true);
                    });
            })
            ->whereDoesntHave('himamatReminderDeliveries', function ($deliveryQuery) use ($slot): void {
                $deliveryQuery
                    ->where('himamat_slot_id', $slot->id)
                    ->where('channel', 'whatsapp');
            });
    }

    private function himamatSlotDayUrl(DailyContent $dailyContent, Member $member, string $slotKey): string
    {
        return $this->ensureHttpsUrl(
            $dailyContent->memberDayUrl($member->token).'#himamat-slot-'.$slotKey
        );
    }
}

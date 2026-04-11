<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\Member;
use App\Models\MemberHimamatPreference;
use App\Models\MemberHimamatReminderDelivery;
use App\Services\HimamatWhatsAppTemplateService;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class SendWhatsAppReminderJob implements ShouldQueue
{
    use Queueable;

    public const QUEUE_NAME = 'whatsapp-reminders';

    public const VARIANT_DAILY = 'daily';

    public const VARIANT_HIMAMAT_INTRO = 'himamat_intro';

    public const VARIANT_HIMAMAT_SLOT = 'himamat_slot';

    public int $tries = 3;

    public int $timeout = 45;

    /**
     * @var array<int>
     */
    public array $backoff = [10, 30];

    public function __construct(
        public readonly int $memberId,
        public readonly int $dailyContentId,
        public readonly string $today,
        public readonly string $variant = self::VARIANT_DAILY,
        public readonly ?int $himamatSlotId = null,
        public readonly ?int $himamatDeliveryId = null,
    ) {
        $this->onQueue(self::QUEUE_NAME);
    }

    public function handle(
        UltraMsgService $ultraMsgService,
        WhatsAppTemplateService $whatsAppTemplateService,
        HimamatWhatsAppTemplateService $himamatWhatsAppTemplateService
    ): void {
        $member = Member::query()->find($this->memberId);

        if (! $member
            || ! $member->whatsapp_reminder_enabled
            || $member->whatsapp_confirmation_status !== 'confirmed'
            || ! $member->whatsapp_phone
            || trim((string) $member->whatsapp_phone) === ''
        ) {
            return;
        }

        if ($this->variant !== self::VARIANT_HIMAMAT_SLOT
            && config('services.ultramsg.reminder_once_only', true)
            && $member->whatsapp_last_sent_date?->toDateString() === $this->today
        ) {
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
        $message = match ($this->variant) {
            self::VARIANT_HIMAMAT_INTRO => $this->resolveHimamatIntroMessage(
                $member,
                $dailyContent,
                $dayUrl,
                $whatsAppTemplateService
            ),
            self::VARIANT_HIMAMAT_SLOT => $this->resolveHimamatSlotMessage(
                $member,
                $dailyContent,
                $himamatWhatsAppTemplateService
            ),
            default => $whatsAppTemplateService->renderDailyReminder($member, $dailyContent, $dayUrl)['message'],
        };

        if ($message === null) {
            return;
        }

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            if ($this->isHimamatDeliveryVariant() && $this->himamatDeliveryId) {
                MemberHimamatReminderDelivery::query()
                    ->whereKey($this->himamatDeliveryId)
                    ->update([
                        'status' => 'failed',
                        'failure_reason' => 'UltraMsg did not confirm delivery.',
                        'last_attempt_at' => now(),
                    ]);
            }

            throw new RuntimeException(sprintf(
                'UltraMsg did not confirm reminder delivery for member %d.',
                $member->id
            ));
        }

        if ($this->isHimamatDeliveryVariant() && $this->himamatDeliveryId) {
            MemberHimamatReminderDelivery::query()
                ->whereKey($this->himamatDeliveryId)
                ->update([
                    'status' => 'sent',
                    'delivered_at' => now(),
                    'failure_reason' => null,
                ]);
        }

        if ($this->variant === self::VARIANT_DAILY || $this->variant === self::VARIANT_HIMAMAT_INTRO) {
            $member->forceFill([
                'whatsapp_last_sent_date' => $this->today,
            ])->save();
        }
    }

    public function failed(Throwable $exception): void
    {
        if ($this->isHimamatDeliveryVariant() && $this->himamatDeliveryId) {
            MemberHimamatReminderDelivery::query()
                ->whereKey($this->himamatDeliveryId)
                ->update([
                    'status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                    'last_attempt_at' => now(),
                ]);
        }

        Log::warning('Queued WhatsApp reminder failed.', [
            'member_id' => $this->memberId,
            'daily_content_id' => $this->dailyContentId,
            'today' => $this->today,
            'variant' => $this->variant,
            'error' => $exception->getMessage(),
            'himamat_slot_id' => $this->himamatSlotId,
            'himamat_delivery_id' => $this->himamatDeliveryId,
        ]);
    }

    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }

    private function resolveHimamatIntroMessage(
        Member $member,
        DailyContent $dailyContent,
        string $dayUrl,
        WhatsAppTemplateService $whatsAppTemplateService
    ): ?string {
        $himamatDay = $this->resolvePublishedHimamatDay($dailyContent);
        if (! $himamatDay) {
            return null;
        }

        if (! $whatsAppTemplateService->himamatIntroIsReady($himamatDay)) {
            $this->markHimamatDeliverySkipped('The Himamat intro reminder is missing the reminder title or reminder content.');

            return null;
        }

        return $whatsAppTemplateService
            ->renderHimamatIntroReminder($member, $dailyContent, $himamatDay, $dayUrl)['message'];
    }

    private function resolveHimamatSlotMessage(
        Member $member,
        DailyContent $dailyContent,
        HimamatWhatsAppTemplateService $templateService
    ): ?string {
        $slot = $this->resolvePublishedHimamatSlot($dailyContent);
        if (! $slot || ! $slot->himamatDay) {
            return null;
        }

        $delivery = $this->himamatDeliveryId
            ? MemberHimamatReminderDelivery::query()->find($this->himamatDeliveryId)
            : null;

        if ($delivery && in_array($delivery->status, ['sent', 'skipped'], true)) {
            return null;
        }

        $preferences = MemberHimamatPreference::query()
            ->where('member_id', $member->id)
            ->where('lent_season_id', $slot->himamatDay->lent_season_id)
            ->first();

        if ($preferences && ! $preferences->slotEnabled((string) $slot->slot_key)) {
            $this->markHimamatDeliverySkipped('The member disabled this Himamat slot before delivery.');

            return null;
        }

        if (! $templateService->reminderIsReady($slot)) {
            $this->markHimamatDeliverySkipped('The Himamat slot reminder content is incomplete.');

            return null;
        }

        if ($delivery) {
            $delivery->forceFill([
                'status' => 'sending',
                'attempt_count' => (int) $delivery->attempt_count + 1,
                'last_attempt_at' => now(),
                'failure_reason' => null,
            ])->save();
        }

        $message = $templateService->renderReminder(
            $member,
            $slot->himamatDay,
            $slot,
            $this->himamatSlotDayUrl($dailyContent, $member, $slot->slot_key)
        )['message'];

        return $message;
    }

    private function resolvePublishedHimamatDay(DailyContent $dailyContent): ?HimamatDay
    {
        if ($dailyContent->day_number < 50 || $dailyContent->day_number > 56) {
            return null;
        }

        return HimamatDay::query()
            ->where('lent_season_id', $dailyContent->lent_season_id)
            ->whereDate('date', $dailyContent->date)
            ->where('is_published', true)
            ->with([
                'slots' => fn ($query) => $query
                    ->where('slot_key', 'intro')
                    ->where('is_published', true)
                    ->orderBy('slot_order'),
            ])
            ->first();
    }

    private function resolvePublishedHimamatSlot(DailyContent $dailyContent): ?HimamatSlot
    {
        if ($this->himamatSlotId === null) {
            return null;
        }

        return HimamatSlot::query()
            ->whereKey($this->himamatSlotId)
            ->where('is_published', true)
            ->with(['himamatDay' => fn ($query) => $query->where('is_published', true)])
            ->first();
    }

    private function himamatSlotDayUrl(DailyContent $dailyContent, Member $member, string $slotKey): string
    {
        return $this->ensureHttpsUrl(
            $dailyContent->memberDayUrl($member->token).'#himamat-slot-'.$slotKey
        );
    }

    private function isHimamatDeliveryVariant(): bool
    {
        return in_array($this->variant, [
            self::VARIANT_HIMAMAT_INTRO,
            self::VARIANT_HIMAMAT_SLOT,
        ], true);
    }

    private function markHimamatDeliverySkipped(string $reason): void
    {
        if (! $this->isHimamatDeliveryVariant() || ! $this->himamatDeliveryId) {
            return;
        }

        MemberHimamatReminderDelivery::query()
            ->whereKey($this->himamatDeliveryId)
            ->update([
                'status' => 'skipped',
                'failure_reason' => $reason,
                'last_attempt_at' => now(),
            ]);
    }
}

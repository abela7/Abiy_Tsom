<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\HimamatWhatsAppTemplateService;
use App\Services\UltraMsgService;
use Illuminate\Console\Command;

class SendHimamatSampleReminder extends Command
{
    protected $signature = 'himamat:send-sample-reminder
        {--member-id= : Use this member for language, tokenized link, and personalization}
        {--sample-phone= : Send only to this phone number}
        {--day= : Himamat day slug such as holy-monday}
        {--slot= : Slot key: intro, third, sixth, ninth, eleventh}
        {--dry-run : Preview the message only without sending WhatsApp}';

    protected $description = 'Send a single Himamat reminder sample to one phone without touching live deliveries';

    public function handle(
        UltraMsgService $ultraMsgService,
        HimamatWhatsAppTemplateService $templateService
    ): int {
        $memberId = (int) ($this->option('member-id') ?: 0);
        $samplePhone = trim((string) ($this->option('sample-phone') ?: ''));
        $daySlug = trim((string) ($this->option('day') ?: ''));
        $slotKey = trim((string) ($this->option('slot') ?: ''));
        $dryRun = (bool) $this->option('dry-run');

        if ($memberId <= 0 || $samplePhone === '' || $daySlug === '' || $slotKey === '') {
            $this->error('You must pass --member-id, --sample-phone, --day, and --slot.');

            return self::FAILURE;
        }

        if (! $dryRun && ! $ultraMsgService->isConfigured()) {
            $this->error('UltraMsg is not configured. Set ULTRAMSG_INSTANCE_ID and ULTRAMSG_TOKEN.');

            return self::FAILURE;
        }

        $season = LentSeason::active();
        if (! $season) {
            $this->error('No active Lent season. Nothing to preview.');

            return self::FAILURE;
        }

        $member = Member::query()->find($memberId);
        if (! $member || ! $member->token || trim((string) $member->token) === '') {
            $this->error('Sample member was not found or does not have a valid token.');

            return self::FAILURE;
        }

        $day = HimamatDay::query()
            ->where('lent_season_id', $season->id)
            ->where('slug', $daySlug)
            ->with('slots')
            ->first();

        if (! $day) {
            $this->error('The requested Himamat day was not found in the active season.');

            return self::FAILURE;
        }

        $slot = $day->slots->firstWhere('slot_key', $slotKey);
        if (! $slot) {
            $this->error('The requested Himamat slot was not found on that day.');

            return self::FAILURE;
        }

        if (! $day->is_published) {
            $this->error(sprintf(
                'The requested Himamat day [%s] is not published yet, so the access link would not open for members.',
                $day->slug
            ));
            $this->line('Publish the Himamat day first, then send the sample again.');

            return self::FAILURE;
        }

        if (! $slot->is_published) {
            $this->error(sprintf(
                'The requested Himamat slot [%s] on [%s] is not published yet, so the access link would not open for members.',
                $slot->slot_key,
                $day->slug
            ));
            $this->line('Publish that slot in the Hourly Timeline section, then send the sample again.');

            return self::FAILURE;
        }

        $rendered = $templateService->renderReminder(
            $member,
            $day,
            $slot,
            $this->resolveMemberDaySlotUrl($member, $day, $slot->slot_key)
        );

        if ($dryRun) {
            $this->line(sprintf(
                "Sample reminder preview for member %d to %s:\n\n%s",
                $member->id,
                $samplePhone,
                $rendered['message']
            ));

            $contact = $ultraMsgService->checkContact($samplePhone);
            if ($contact !== null) {
                $this->newLine();
                $this->line(sprintf(
                    'UltraMsg contact check: %s (%s)',
                    strtoupper($contact['status']),
                    $contact['chat_id']
                ));

                if ($contact['status'] === 'invalid') {
                    $this->warn('UltraMsg says this recipient is invalid, so a real send will not arrive.');
                    $this->line('If this is the same WhatsApp number connected to your UltraMsg instance, test with a different recipient number.');
                }
            }

            return self::SUCCESS;
        }

        $contact = $ultraMsgService->checkContact($samplePhone);
        if (($contact['status'] ?? 'unknown') === 'invalid') {
            $this->error(sprintf(
                'UltraMsg reports %s as INVALID (%s). The message will not be delivered.',
                $samplePhone,
                $contact['chat_id']
            ));
            $this->line('If this is the same WhatsApp number connected to your UltraMsg instance, test with a different recipient number.');

            return self::FAILURE;
        }

        if (! $ultraMsgService->sendTextMessage($samplePhone, $rendered['message'])) {
            $this->error('UltraMsg did not confirm the sample reminder delivery.');

            return self::FAILURE;
        }

        $this->line(sprintf(
            'Sample Himamat reminder sent to %s using member %d, day [%s], slot [%s].',
            $samplePhone,
            $member->id,
            $day->slug,
            $slot->slot_key
        ));

        return self::SUCCESS;
    }

    private function resolveMemberDaySlotUrl(Member $member, HimamatDay $day, string $slotKey): string
    {
        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $day->lent_season_id)
            ->whereDate('date', $day->date)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return $day->accessUrl($member, $slotKey);
        }

        return url($dailyContent->memberDayUrl($member->token, false).'#himamat-slot-'.$slotKey);
    }
}

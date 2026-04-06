<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AuditHimamatReminders extends Command
{
    protected $signature = 'himamat:audit-reminders
        {--date= : Audit a specific London date in YYYY-MM-DD format}
        {--member-id= : Also show one member\'s per-slot eligibility preview}
        {--locale= : Preview localized titles/content in this locale (default: app locale)}';

    protected $description = 'Audit Himamat reminder readiness without sending any WhatsApp messages';

    public function handle(): int
    {
        $timezone = (string) config('himamat.timezone', 'Europe/London');
        $locale = (string) ($this->option('locale') ?: app()->getLocale());
        $auditDate = $this->resolveAuditDate($timezone);
        $memberId = $this->option('member-id');

        $season = LentSeason::active();
        if (! $season) {
            $this->error('No active Lent season found.');

            return self::FAILURE;
        }

        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $auditDate->toDateString())
            ->first();

        if (! $dailyContent) {
            $this->error(sprintf(
                'No DailyContent row exists for %s in season %d.',
                $auditDate->toDateString(),
                $season->id
            ));

            return self::FAILURE;
        }

        $himamatDay = HimamatDay::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $auditDate->toDateString())
            ->with(['slots' => fn ($query) => $query->orderBy('slot_order')])
            ->first();

        $this->line('');
        $this->info('Himamat Reminder Audit');
        $this->line('----------------------');
        $this->line('London date: '.$auditDate->toDateString());
        $this->line('Active season: #'.$season->id.' ('.$season->year.')');
        $this->line(sprintf(
            'Daily content: #%d | Day %d of 55 | Published: %s',
            $dailyContent->id,
            (int) $dailyContent->day_number,
            $dailyContent->is_published ? 'yes' : 'no'
        ));
        $this->line('Daily page URL pattern: '.$dailyContent->memberDayUrl('TOKEN', false));

        if ((int) $dailyContent->day_number < 50 || (int) $dailyContent->day_number > 55) {
            $this->warn('This day is outside Passion Week (days 50-55). No Himamat reminders should send.');

            return self::SUCCESS;
        }

        if (! $himamatDay) {
            $this->error('No Himamat day is linked to this date.');

            return self::FAILURE;
        }

        $localizedDayTitle = localized($himamatDay, 'title', $locale) ?? $himamatDay->title_en ?? $himamatDay->slug;
        $dayMeaning = trim((string) (localized($himamatDay, 'spiritual_meaning', $locale) ?? ''));

        $this->line(sprintf(
            'Himamat day: #%d | %s | Published: %s',
            $himamatDay->id,
            $localizedDayTitle,
            $himamatDay->is_published ? 'yes' : 'no'
        ));
        $this->line('Day meaning present: '.($dayMeaning !== '' ? 'yes' : 'no'));

        $slotDefinitions = collect((array) config('himamat.slots', []));
        $slotsByKey = $himamatDay->slots->keyBy('slot_key');
        $rows = [];
        $warnings = [];

        foreach ($slotDefinitions as $definition) {
            $slotKey = (string) ($definition['key'] ?? '');
            $expectedTime = (string) ($definition['time'] ?? '');
            /** @var HimamatSlot|null $slot */
            $slot = $slotsByKey->get($slotKey);

            if (! $slot) {
                $rows[] = [
                    $slotKey,
                    $expectedTime,
                    'missing',
                    'no',
                    '0',
                    '0',
                    '-',
                    '-',
                ];
                $warnings[] = sprintf('Missing Himamat slot: %s', $slotKey);

                continue;
            }

            $localizedSlotTitle = trim((string) (localized($slot, 'slot_header', $locale) ?? $slot->slot_header_en ?? ''));
            $localizedReminderHeader = trim((string) (localized($slot, 'reminder_header', $locale) ?? ''));
            $localizedReminderContent = trim((string) (localized($slot, 'reminder_content', $locale) ?? ''));
            $eligibleCount = $this->eligibleRecipientsQuery($season->id, $slot)->count();
            $existingDeliveryCount = MemberHimamatReminderDelivery::query()
                ->where('himamat_slot_id', $slot->id)
                ->where('channel', 'whatsapp')
                ->count();

            $ready = $himamatDay->is_published
                && $slot->is_published
                && $this->slotHasRequiredContent($slotKey, $localizedSlotTitle, $localizedReminderHeader, $localizedReminderContent, $dayMeaning);

            if ((string) $slot->scheduled_time_london !== $expectedTime) {
                $warnings[] = sprintf(
                    'Time mismatch for %s: expected %s, actual %s',
                    $slotKey,
                    $expectedTime,
                    (string) $slot->scheduled_time_london
                );
            }

            if (! $slot->is_published) {
                $warnings[] = sprintf('Slot %s is still draft.', $slotKey);
            }

            if ($slotKey === 'intro') {
                if ($localizedReminderHeader === '') {
                    $warnings[] = 'Intro reminder title is blank.';
                }
                if ($dayMeaning === '') {
                    $warnings[] = 'Day Theme & Meaning is blank.';
                }
            } else {
                if ($localizedSlotTitle === '') {
                    $warnings[] = sprintf('Hour title is blank for %s.', $slotKey);
                }
                if ($localizedReminderContent === '') {
                    $warnings[] = sprintf('Reminder content is blank for %s.', $slotKey);
                }
            }

            $rows[] = [
                $slotKey,
                (string) $slot->scheduled_time_london,
                $slot->is_published ? 'published' : 'draft',
                $ready ? 'yes' : 'no',
                (string) $eligibleCount,
                (string) $existingDeliveryCount,
                $this->summarize($slotKey === 'intro' ? $localizedReminderHeader : $localizedSlotTitle),
                $this->summarize($slotKey === 'intro' ? $dayMeaning : $localizedReminderContent),
            ];
        }

        $this->line('');
        $this->table(
            ['Slot', 'Time', 'Status', 'Ready', 'Eligible now', 'Existing sends', 'Heading source', 'Body source'],
            $rows
        );

        if ($memberId !== null && $memberId !== '') {
            $this->renderMemberPreview((int) $memberId, $dailyContent, $himamatDay, $season->id, $slotsByKey);
        }

        $this->line('');
        if ($warnings === []) {
            $this->info('Audit result: ready. No blocking issues found for today\'s Himamat reminder setup.');
        } else {
            $this->warn('Audit result: review the warnings below before relying on live sends.');
            foreach (array_unique($warnings) as $warning) {
                $this->line(' - '.$warning);
            }
        }

        $this->line('');
        $this->line('This command does not send any WhatsApp messages.');

        return self::SUCCESS;
    }

    private function resolveAuditDate(string $timezone): CarbonImmutable
    {
        $date = $this->option('date');

        if (! $date) {
            return CarbonImmutable::now($timezone);
        }

        return CarbonImmutable::parse((string) $date, $timezone);
    }

    private function eligibleRecipientsQuery(int $seasonId, HimamatSlot $slot): Builder
    {
        return Member::query()
            ->where('whatsapp_reminder_enabled', true)
            ->where('whatsapp_confirmation_status', 'confirmed')
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->where(function ($query) use ($seasonId, $slot): void {
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

    private function slotHasRequiredContent(
        string $slotKey,
        string $slotTitle,
        string $reminderHeader,
        string $reminderContent,
        string $dayMeaning
    ): bool {
        if ($slotKey === 'intro') {
            return $reminderHeader !== '' && $dayMeaning !== '';
        }

        return $slotTitle !== '' && $reminderContent !== '';
    }

    private function summarize(string $value): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($value)) ?? '';

        if ($normalized === '') {
            return '-';
        }

        return mb_strimwidth($normalized, 0, 54, '...', 'UTF-8');
    }

    /**
     * @param Collection<string, HimamatSlot> $slotsByKey
     */
    private function renderMemberPreview(
        int $memberId,
        DailyContent $dailyContent,
        HimamatDay $himamatDay,
        int $seasonId,
        Collection $slotsByKey
    ): void {
        $member = Member::query()->find($memberId);

        if (! $member) {
            $this->warn(sprintf('Member #%d not found. Skipping member preview.', $memberId));

            return;
        }

        $this->line('');
        $this->info(sprintf(
            'Member preview: #%d | %s | WhatsApp enabled: %s | Confirmed: %s',
            $member->id,
            $member->baptism_name ?? '-',
            $member->whatsapp_reminder_enabled ? 'yes' : 'no',
            $member->whatsapp_confirmation_status === 'confirmed' ? 'yes' : 'no'
        ));

        $memberPreference = $member->himamatPreferences()
            ->where('lent_season_id', $seasonId)
            ->first();

        $rows = [];

        foreach (collect((array) config('himamat.slots', [])) as $definition) {
            $slotKey = (string) ($definition['key'] ?? '');
            /** @var HimamatSlot|null $slot */
            $slot = $slotsByKey->get($slotKey);

            if (! $slot) {
                $rows[] = [$slotKey, 'missing', 'no', '-'];

                continue;
            }

            $enabled = $memberPreference
                ? $memberPreference->slotEnabled($slotKey)
                : true;

            $alreadySent = MemberHimamatReminderDelivery::query()
                ->where('member_id', $member->id)
                ->where('himamat_slot_id', $slot->id)
                ->where('channel', 'whatsapp')
                ->exists();

            $eligible = $member->whatsapp_reminder_enabled
                && $member->whatsapp_confirmation_status === 'confirmed'
                && filled($member->whatsapp_phone)
                && $enabled
                && ! $alreadySent
                && $himamatDay->is_published
                && $slot->is_published;

            $rows[] = [
                $slotKey,
                $enabled ? 'yes' : 'no',
                $eligible ? 'yes' : 'no',
                $this->summarize(
                    $dailyContent->memberDayUrl($member->token, false).'#himamat-slot-'.$slotKey
                ),
            ];
        }

        $this->table(['Slot', 'Preference on', 'Would send', 'Member URL'], $rows);
    }
}

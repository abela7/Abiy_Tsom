<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\MemberHimamatInvitationDelivery;
use App\Models\MemberHimamatPreference;
use App\Models\MemberHimamatReminderDelivery;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeWhatsAppPhone extends Command
{
    protected $signature = 'members:merge-whatsapp-phone
        {phone : The WhatsApp phone number to consolidate}
        {--keep-id= : Member ID that should keep ownership of the WhatsApp number}
        {--normalized : Match members by normalized UK mobile format instead of exact stored value}
        {--apply : Actually apply the merge. Without this flag the command is a dry-run only}';

    protected $description = 'Consolidate one WhatsApp number onto a single keeper member account';

    public function handle(): int
    {
        $phone = trim((string) $this->argument('phone'));
        $normalized = (bool) $this->option('normalized');
        $apply = (bool) $this->option('apply');

        if ($phone === '') {
            $this->error('Provide a WhatsApp number to merge.');

            return self::FAILURE;
        }

        $matchedMembers = $this->matchedMembers($phone, $normalized);
        if ($matchedMembers->count() < 2) {
            $this->warn('Fewer than two members match that WhatsApp number. Nothing to merge.');

            return self::SUCCESS;
        }

        $keepId = $this->option('keep-id');
        $keeper = $keepId !== null && $keepId !== ''
            ? $matchedMembers->firstWhere('id', (int) $keepId)
            : $this->recommendedKeeper($matchedMembers);

        if (! $keeper instanceof Member) {
            $this->error('The chosen --keep-id does not belong to the matched duplicate set.');

            return self::FAILURE;
        }

        $duplicates = $matchedMembers
            ->reject(fn (Member $member): bool => $member->id === $keeper->id)
            ->values();

        $this->line('');
        $this->info($normalized ? 'Normalized WhatsApp Merge Preview' : 'Exact WhatsApp Merge Preview');
        $this->line('--------------------------------');
        $this->line('Target phone: '.$phone);
        if ($normalized) {
            $this->line('Normalized key: '.(normalizeUkWhatsAppPhone($phone) ?? '[invalid UK mobile format]'));
        }

        $this->line('');
        $this->info('Matched member accounts');
        $this->table(
            ['ID', 'Name', 'Stored phone', 'Confirmed', 'Reminders', 'WA lang', 'Locale', 'Last sent'],
            $matchedMembers->map(function (Member $member): array {
                return [
                    (string) $member->id,
                    (string) ($member->baptism_name ?: '-'),
                    (string) ($member->whatsapp_phone ?: '-'),
                    (string) ($member->whatsapp_confirmation_status ?: '-'),
                    $member->whatsapp_reminder_enabled ? 'yes' : 'no',
                    (string) ($member->whatsapp_language ?: '-'),
                    (string) ($member->locale ?: '-'),
                    $member->whatsapp_last_sent_date?->toDateString() ?: '-',
                ];
            })->all()
        );

        $mergedState = $this->mergedWhatsAppState($keeper, $duplicates, $phone, $normalized);

        $this->line('');
        $this->info('Keeper account');
        $this->table(
            ['Field', 'Value'],
            [
                ['Member ID', (string) $keeper->id],
                ['Name', (string) ($keeper->baptism_name ?: '-')],
                ['Final WhatsApp phone', (string) ($mergedState['whatsapp_phone'] ?: '-')],
                ['Final confirmed status', (string) ($mergedState['whatsapp_confirmation_status'] ?: '-')],
                ['Final reminders enabled', $mergedState['whatsapp_reminder_enabled'] ? 'yes' : 'no'],
                ['Final reminder time', (string) ($mergedState['whatsapp_reminder_time'] ?: '-')],
                ['Final WhatsApp language', (string) ($mergedState['whatsapp_language'] ?: '-')],
                ['Final last sent date', $mergedState['whatsapp_last_sent_date']?->toDateString() ?: '-'],
            ]
        );

        $this->line('');
        $this->info('Rows affected if applied');
        $this->table(
            ['Type', 'Count'],
            [
                ['Duplicate member accounts to clear', (string) $duplicates->count()],
                ['Duplicate Himamat preferences found', (string) $this->countRelatedRows('member_himamat_preferences', $duplicates)],
                ['Duplicate Himamat reminder deliveries found', (string) $this->countRelatedRows('member_himamat_reminder_deliveries', $duplicates)],
                ['Duplicate Himamat invitation deliveries found', (string) $this->countRelatedRows('member_himamat_invitation_deliveries', $duplicates)],
            ]
        );

        if (! $apply) {
            $this->line('');
            $this->warn('Dry-run only. No database changes were made.');
            $this->line('To apply this merge, rerun with:');
            $this->line(sprintf(
                'php artisan members:merge-whatsapp-phone "%s" --keep-id=%d%s --apply',
                $phone,
                $keeper->id,
                $normalized ? ' --normalized' : ''
            ));

            return self::SUCCESS;
        }

        if ($keepId === null || $keepId === '') {
            $this->error('For safety, --keep-id is required when using --apply.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($keeper, $duplicates, $mergedState): void {
            $this->applyMergedWhatsAppState($keeper, $mergedState);
            $this->mergeHimamatPreferences($keeper, $duplicates);
            $this->mergeHimamatReminderDeliveries($keeper, $duplicates);
            $this->mergeHimamatInvitationDeliveries($keeper, $duplicates);
            $this->clearDuplicateWhatsAppState($duplicates);
        });

        $this->line('');
        $this->info(sprintf(
            'Merged WhatsApp number onto member #%d and cleared %d duplicate account(s).',
            $keeper->id,
            $duplicates->count()
        ));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Member>
     */
    private function matchedMembers(string $phone, bool $normalized): Collection
    {
        $members = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->orderBy('id')
            ->get();

        if (! $normalized) {
            return $members
                ->filter(fn (Member $member): bool => trim((string) $member->whatsapp_phone) === $phone)
                ->values();
        }

        $normalizedPhone = normalizeUkWhatsAppPhone($phone);
        if ($normalizedPhone === null) {
            return collect();
        }

        return $members
            ->filter(function (Member $member) use ($normalizedPhone): bool {
                return normalizeUkWhatsAppPhone((string) $member->whatsapp_phone) === $normalizedPhone;
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function mergedWhatsAppState(Member $keeper, Collection $duplicates, string $phone, bool $normalized): array
    {
        $allMembers = collect([$keeper])->concat($duplicates)->values();
        $finalPhone = $normalized
            ? (normalizeUkWhatsAppPhone($phone) ?? trim((string) $keeper->whatsapp_phone))
            : trim((string) ($keeper->whatsapp_phone ?: $phone));

        return [
            'whatsapp_phone' => $finalPhone !== '' ? $finalPhone : $phone,
            'whatsapp_reminder_enabled' => $allMembers->contains(
                fn (Member $member): bool => (bool) $member->whatsapp_reminder_enabled
            ),
            'whatsapp_reminder_time' => $this->firstNonEmptyValue($allMembers, 'whatsapp_reminder_time'),
            'whatsapp_last_sent_date' => $this->latestDate($allMembers, 'whatsapp_last_sent_date'),
            'whatsapp_language' => $this->preferredLocaleValue($allMembers, 'whatsapp_language'),
            'whatsapp_confirmation_status' => $this->strongestConfirmationStatus($allMembers),
            'whatsapp_confirmation_requested_at' => $this->earliestDateTime($allMembers, 'whatsapp_confirmation_requested_at'),
            'whatsapp_confirmation_responded_at' => $this->latestDateTime($allMembers, 'whatsapp_confirmation_responded_at'),
            'whatsapp_non_uk_requested' => $allMembers->contains(
                fn (Member $member): bool => (bool) $member->whatsapp_non_uk_requested
            ),
            'phone_verified_at' => $this->earliestDateTime($allMembers, 'phone_verified_at'),
        ];
    }

    private function applyMergedWhatsAppState(Member $keeper, array $mergedState): void
    {
        $keeper->forceFill($mergedState)->save();
    }

    private function mergeHimamatPreferences(Member $keeper, Collection $duplicates): void
    {
        foreach ($duplicates as $duplicate) {
            $preferences = MemberHimamatPreference::query()
                ->where('member_id', $duplicate->id)
                ->get();

            foreach ($preferences as $preference) {
                $keeperRow = MemberHimamatPreference::query()
                    ->where('member_id', $keeper->id)
                    ->where('lent_season_id', $preference->lent_season_id)
                    ->first();

                if (! $keeperRow) {
                    $preference->forceFill(['member_id' => $keeper->id])->save();

                    continue;
                }

                $preference->delete();
            }
        }
    }

    private function mergeHimamatReminderDeliveries(Member $keeper, Collection $duplicates): void
    {
        foreach ($duplicates as $duplicate) {
            $deliveries = MemberHimamatReminderDelivery::query()
                ->where('member_id', $duplicate->id)
                ->orderBy('id')
                ->get();

            foreach ($deliveries as $delivery) {
                $keeperDelivery = MemberHimamatReminderDelivery::query()
                    ->where('member_id', $keeper->id)
                    ->where('himamat_slot_id', $delivery->himamat_slot_id)
                    ->where('channel', $delivery->channel)
                    ->first();

                if (! $keeperDelivery) {
                    $delivery->forceFill(['member_id' => $keeper->id])->save();

                    continue;
                }

                $keeperDelivery->forceFill([
                    'status' => $this->strongerReminderStatus(
                        (string) $keeperDelivery->status,
                        (string) $delivery->status
                    ),
                    'attempt_count' => max((int) $keeperDelivery->attempt_count, (int) $delivery->attempt_count),
                    'due_at_london' => $keeperDelivery->due_at_london ?? $delivery->due_at_london,
                    'last_attempt_at' => $this->laterDateTime($keeperDelivery->last_attempt_at, $delivery->last_attempt_at),
                    'delivered_at' => $this->laterDateTime($keeperDelivery->delivered_at, $delivery->delivered_at),
                    'failure_reason' => $keeperDelivery->failure_reason ?: $delivery->failure_reason,
                    'himamat_reminder_dispatch_id' => $keeperDelivery->himamat_reminder_dispatch_id ?: $delivery->himamat_reminder_dispatch_id,
                ])->save();

                $delivery->delete();
            }
        }
    }

    private function mergeHimamatInvitationDeliveries(Member $keeper, Collection $duplicates): void
    {
        foreach ($duplicates as $duplicate) {
            $deliveries = MemberHimamatInvitationDelivery::query()
                ->where('member_id', $duplicate->id)
                ->orderBy('id')
                ->get();

            foreach ($deliveries as $delivery) {
                $keeperDelivery = MemberHimamatInvitationDelivery::query()
                    ->where('member_id', $keeper->id)
                    ->where('campaign_key', $delivery->campaign_key)
                    ->where('channel', $delivery->channel)
                    ->first();

                if (! $keeperDelivery) {
                    $delivery->forceFill([
                        'member_id' => $keeper->id,
                        'destination_phone' => $keeper->whatsapp_phone ?: $delivery->destination_phone,
                    ])->save();

                    continue;
                }

                $keeperDelivery->forceFill([
                    'destination_phone' => $keeperDelivery->destination_phone ?: $delivery->destination_phone,
                    'status' => $this->strongerInvitationStatus(
                        (string) $keeperDelivery->status,
                        (string) $delivery->status
                    ),
                    'attempt_count' => max((int) $keeperDelivery->attempt_count, (int) $delivery->attempt_count),
                    'open_count' => max((int) $keeperDelivery->open_count, (int) $delivery->open_count),
                    'last_attempt_at' => $this->laterDateTime($keeperDelivery->last_attempt_at, $delivery->last_attempt_at),
                    'delivered_at' => $this->laterDateTime($keeperDelivery->delivered_at, $delivery->delivered_at),
                    'first_opened_at' => $this->earlierDateTime($keeperDelivery->first_opened_at, $delivery->first_opened_at),
                    'last_opened_at' => $this->laterDateTime($keeperDelivery->last_opened_at, $delivery->last_opened_at),
                    'failure_reason' => $keeperDelivery->failure_reason ?: $delivery->failure_reason,
                ])->save();

                $delivery->delete();
            }
        }
    }

    private function clearDuplicateWhatsAppState(Collection $duplicates): void
    {
        foreach ($duplicates as $duplicate) {
            $duplicate->forceFill([
                'whatsapp_phone' => null,
                'whatsapp_reminder_enabled' => false,
                'whatsapp_reminder_time' => null,
                'whatsapp_last_sent_date' => null,
                'whatsapp_language' => null,
                'whatsapp_confirmation_status' => 'none',
                'whatsapp_confirmation_requested_at' => null,
                'whatsapp_confirmation_responded_at' => null,
                'whatsapp_non_uk_requested' => false,
                'phone_verified_at' => null,
            ])->save();
        }
    }

    private function recommendedKeeper(Collection $members): Member
    {
        /** @var Member $keeper */
        $keeper = $members
            ->sort(function (Member $left, Member $right): int {
                $scoreComparison = $this->keeperScore($right) <=> $this->keeperScore($left);

                if ($scoreComparison !== 0) {
                    return $scoreComparison;
                }

                return $left->id <=> $right->id;
            })
            ->first();

        return $keeper;
    }

    private function keeperScore(Member $member): int
    {
        $score = 0;

        if ($member->whatsapp_confirmation_status === 'confirmed') {
            $score += 100;
        } elseif ($member->whatsapp_confirmation_status === 'pending') {
            $score += 40;
        } elseif ($member->whatsapp_confirmation_status === 'rejected') {
            $score += 10;
        }

        if ($member->phone_verified_at !== null) {
            $score += 50;
        }

        if ($member->whatsapp_reminder_enabled) {
            $score += 20;
        }

        if ($member->whatsapp_last_sent_date !== null) {
            $score += 10;
        }

        if ($this->isSupportedLocale($member->whatsapp_language)) {
            $score += 5;
        }

        if ($member->whatsapp_reminder_time !== null) {
            $score += 3;
        }

        return $score;
    }

    private function strongestConfirmationStatus(Collection $members): string
    {
        $priority = [
            'confirmed' => 4,
            'pending' => 3,
            'rejected' => 2,
            'none' => 1,
            '' => 0,
        ];

        return $members
            ->map(fn (Member $member): string => (string) ($member->whatsapp_confirmation_status ?? 'none'))
            ->sortByDesc(fn (string $status): int => $priority[$status] ?? 0)
            ->first() ?? 'none';
    }

    private function strongerReminderStatus(string $left, string $right): string
    {
        $priority = [
            'sent' => 5,
            'sending' => 4,
            'queued' => 3,
            'skipped' => 2,
            'failed' => 1,
        ];

        return ($priority[$right] ?? 0) > ($priority[$left] ?? 0) ? $right : $left;
    }

    private function strongerInvitationStatus(string $left, string $right): string
    {
        $priority = [
            'sent' => 5,
            'sending' => 4,
            'pending' => 3,
            'skipped' => 2,
            'failed' => 1,
        ];

        return ($priority[$right] ?? 0) > ($priority[$left] ?? 0) ? $right : $left;
    }

    private function firstNonEmptyValue(Collection $members, string $field): ?string
    {
        foreach ($members as $member) {
            $value = trim((string) ($member->{$field} ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function preferredLocaleValue(Collection $members, string $field): ?string
    {
        foreach ($members as $member) {
            $value = trim((string) ($member->{$field} ?? ''));
            if ($this->isSupportedLocale($value)) {
                return $value;
            }
        }

        return null;
    }

    private function isSupportedLocale(?string $value): bool
    {
        return in_array($value, ['en', 'am'], true);
    }

    private function latestDate(Collection $members, string $field): ?CarbonInterface
    {
        return $members
            ->map(fn (Member $member) => $member->{$field})
            ->filter(fn ($value) => $value instanceof CarbonInterface)
            ->sortByDesc(fn (CarbonInterface $value): int => $value->getTimestamp())
            ->first();
    }

    private function earliestDateTime(Collection $members, string $field): ?CarbonInterface
    {
        return $members
            ->map(fn (Member $member) => $member->{$field})
            ->filter(fn ($value) => $value instanceof CarbonInterface)
            ->sortBy(fn (CarbonInterface $value): int => $value->getTimestamp())
            ->first();
    }

    private function latestDateTime(Collection $members, string $field): ?CarbonInterface
    {
        return $members
            ->map(fn (Member $member) => $member->{$field})
            ->filter(fn ($value) => $value instanceof CarbonInterface)
            ->sortByDesc(fn (CarbonInterface $value): int => $value->getTimestamp())
            ->first();
    }

    private function earlierDateTime($left, $right): ?CarbonInterface
    {
        if (! $left instanceof CarbonInterface) {
            return $right instanceof CarbonInterface ? $right : null;
        }

        if (! $right instanceof CarbonInterface) {
            return $left;
        }

        return $left->lte($right) ? $left : $right;
    }

    private function laterDateTime($left, $right): ?CarbonInterface
    {
        if (! $left instanceof CarbonInterface) {
            return $right instanceof CarbonInterface ? $right : null;
        }

        if (! $right instanceof CarbonInterface) {
            return $left;
        }

        return $left->gte($right) ? $left : $right;
    }

    private function countRelatedRows(string $table, Collection $duplicates): int
    {
        return DB::table($table)
            ->whereIn('member_id', $duplicates->pluck('id'))
            ->count();
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class AuditDuplicateWhatsAppPhones extends Command
{
    protected $signature = 'members:audit-whatsapp-duplicates
        {--normalized : Group by normalized UK mobile format instead of exact stored value}';

    protected $description = 'List duplicate WhatsApp phone numbers across member accounts';

    public function handle(): int
    {
        $normalized = (bool) $this->option('normalized');

        $members = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->orderBy('whatsapp_phone')
            ->orderBy('id')
            ->get([
                'id',
                'baptism_name',
                'whatsapp_phone',
                'whatsapp_confirmation_status',
                'whatsapp_reminder_enabled',
                'whatsapp_language',
                'locale',
                'phone_verified_at',
            ]);

        $groups = $members
            ->groupBy(fn (Member $member): string => $this->groupKey($member, $normalized))
            ->filter(fn (Collection $group, string $key): bool => $key !== '' && $group->count() > 1)
            ->sortByDesc(fn (Collection $group): int => $group->count());

        $this->line('');
        $this->info($normalized ? 'Normalized WhatsApp Duplicate Audit' : 'Exact WhatsApp Duplicate Audit');
        $this->line('--------------------------------');

        if ($groups->isEmpty()) {
            $this->info('No duplicate WhatsApp numbers found.');

            return self::SUCCESS;
        }

        $summaryRows = $groups->map(function (Collection $group, string $phone): array {
            return [
                $phone,
                (string) $group->count(),
                $group->pluck('id')->implode(', '),
            ];
        })->values()->all();

        $this->table(
            ['Phone', 'Accounts', 'Member IDs'],
            $summaryRows
        );

        foreach ($groups as $phone => $group) {
            $this->line('');
            $this->warn(sprintf('Duplicate phone: %s (%d account(s))', $phone, $group->count()));
            $this->table(
                ['ID', 'Name', 'Stored phone', 'Confirmed', 'Reminders', 'WA lang', 'Locale', 'Phone verified'],
                $group->map(function (Member $member): array {
                    return [
                        (string) $member->id,
                        (string) ($member->baptism_name ?: '-'),
                        (string) $member->whatsapp_phone,
                        (string) $member->whatsapp_confirmation_status,
                        $member->whatsapp_reminder_enabled ? 'yes' : 'no',
                        (string) ($member->whatsapp_language ?: '-'),
                        (string) ($member->locale ?: '-'),
                        $member->phone_verified_at ? 'yes' : 'no',
                    ];
                })->all()
            );
        }

        $this->line('');
        $this->line('Use the merge command to consolidate a number onto one keeper account:');
        $this->line('php artisan members:merge-whatsapp-phone "+447..." --keep-id=123 --apply');

        return self::SUCCESS;
    }

    private function groupKey(Member $member, bool $normalized): string
    {
        $phone = trim((string) ($member->whatsapp_phone ?? ''));

        if ($phone === '') {
            return '';
        }

        if (! $normalized) {
            return $phone;
        }

        return normalizeUkWhatsAppPhone($phone) ?? $phone;
    }
}

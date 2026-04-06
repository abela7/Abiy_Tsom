<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class ClearMemberWhatsApp extends Command
{
    protected $signature = 'members:clear-whatsapp
        {member_id : Member ID to remove WhatsApp ownership from}
        {--apply : Actually clear the WhatsApp fields. Without this flag the command is a dry-run only}';

    protected $description = 'Safely clear WhatsApp state from one member account without merging accounts';

    public function handle(): int
    {
        $member = Member::query()->find((int) $this->argument('member_id'));

        if (! $member) {
            $this->error('Member not found.');

            return self::FAILURE;
        }

        $this->line('');
        $this->info('Clear Member WhatsApp Preview');
        $this->line('-----------------------------');
        $this->table(
            ['Field', 'Current value'],
            [
                ['Member ID', (string) $member->id],
                ['Name', (string) ($member->baptism_name ?: '-')],
                ['WhatsApp phone', (string) ($member->whatsapp_phone ?: '-')],
                ['Confirmed status', (string) ($member->whatsapp_confirmation_status ?: '-')],
                ['Reminders enabled', $member->whatsapp_reminder_enabled ? 'yes' : 'no'],
                ['Reminder time', (string) ($member->whatsapp_reminder_time ?: '-')],
                ['WhatsApp language', (string) ($member->whatsapp_language ?: '-')],
                ['Last sent date', $member->whatsapp_last_sent_date?->toDateString() ?: '-'],
                ['Phone verified', $member->phone_verified_at ? 'yes' : 'no'],
            ]
        );

        $this->line('');
        $this->line('Fields that will be cleared:');
        $this->line('- whatsapp_phone');
        $this->line('- whatsapp_reminder_enabled');
        $this->line('- whatsapp_reminder_time');
        $this->line('- whatsapp_last_sent_date');
        $this->line('- whatsapp_language');
        $this->line('- whatsapp_confirmation_status');
        $this->line('- whatsapp_confirmation_requested_at');
        $this->line('- whatsapp_confirmation_responded_at');
        $this->line('- whatsapp_non_uk_requested');
        $this->line('- phone_verified_at');
        $this->line('');
        $this->line('This command does not delete the member account or Himamat history.');

        if (! $this->option('apply')) {
            $this->warn('Dry-run only. No database changes were made.');
            $this->line(sprintf(
                'To apply: php artisan members:clear-whatsapp %d --apply',
                $member->id
            ));

            return self::SUCCESS;
        }

        $member->forceFill([
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

        $this->line('');
        $this->info(sprintf(
            'Cleared WhatsApp state from member #%d.',
            $member->id
        ));

        return self::SUCCESS;
    }
}

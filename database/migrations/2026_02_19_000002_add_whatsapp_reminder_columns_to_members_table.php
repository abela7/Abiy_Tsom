<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store WhatsApp reminder preference per member.
     * Reminder time is stored in London time (Europe/London).
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->boolean('whatsapp_reminder_enabled')
                ->default(false)
                ->after('theme');
            $table->string('whatsapp_phone', 32)
                ->nullable()
                ->after('whatsapp_reminder_enabled');
            $table->time('whatsapp_reminder_time')
                ->nullable()
                ->after('whatsapp_phone')
                ->comment('Reminder time in Europe/London');
            $table->date('whatsapp_last_sent_date')
                ->nullable()
                ->after('whatsapp_reminder_time');

            $table->index(
                ['whatsapp_reminder_enabled', 'whatsapp_reminder_time'],
                'members_whatsapp_reminder_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex('members_whatsapp_reminder_idx');
            $table->dropColumn([
                'whatsapp_reminder_enabled',
                'whatsapp_phone',
                'whatsapp_reminder_time',
                'whatsapp_last_sent_date',
            ]);
        });
    }
};

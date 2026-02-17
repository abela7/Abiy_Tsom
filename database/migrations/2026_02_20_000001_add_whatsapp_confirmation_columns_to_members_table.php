<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Track WhatsApp reminder confirmation by reply (YES/NO).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('whatsapp_confirmation_status', 16)
                ->default('none')
                ->comment('none|pending|confirmed|rejected')
                ->after('whatsapp_language');
            $table->timestamp('whatsapp_confirmation_requested_at')
                ->nullable()
                ->after('whatsapp_confirmation_status');
            $table->timestamp('whatsapp_confirmation_responded_at')
                ->nullable()
                ->after('whatsapp_confirmation_requested_at');

            $table->index(
                ['whatsapp_phone', 'whatsapp_confirmation_status'],
                'members_whatsapp_confirmation_idx'
            );
        });

        // Existing active reminder users are treated as already confirmed.
        DB::table('members')
            ->where('whatsapp_reminder_enabled', true)
            ->whereNotNull('whatsapp_phone')
            ->whereNotNull('whatsapp_reminder_time')
            ->update([
                'whatsapp_confirmation_status' => 'confirmed',
                'whatsapp_confirmation_requested_at' => now(),
                'whatsapp_confirmation_responded_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropIndex('members_whatsapp_confirmation_idx');
            $table->dropColumn([
                'whatsapp_confirmation_status',
                'whatsapp_confirmation_requested_at',
                'whatsapp_confirmation_responded_at',
            ]);
        });
    }
};

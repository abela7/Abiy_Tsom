<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the whatsapp_language column so we know which language
 * to use when sending WhatsApp confirmations and daily reminders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->string('whatsapp_language', 2)
                ->nullable()
                ->default('en')
                ->comment('Language for WhatsApp messages: en or am')
                ->after('whatsapp_last_sent_date');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_language');
        });
    }
};

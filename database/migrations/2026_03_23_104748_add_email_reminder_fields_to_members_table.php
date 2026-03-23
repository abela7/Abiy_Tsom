<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('email_reminder_enabled')->default(false)->after('email_verified_at');
            $table->date('email_last_sent_date')->nullable()->after('email_reminder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['email_reminder_enabled', 'email_last_sent_date']);
        });
    }
};

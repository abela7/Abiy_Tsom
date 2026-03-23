<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('email')->nullable()->unique()->after('baptism_name');
            $table->timestamp('phone_verified_at')->nullable()->after('whatsapp_non_uk_requested');
            $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
            $table->string('verification_code')->nullable()->after('email_verified_at');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
        });

        // Auto-verify existing confirmed WhatsApp members.
        DB::table('members')
            ->where('whatsapp_confirmation_status', 'confirmed')
            ->whereNull('phone_verified_at')
            ->update(['phone_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn([
                'email',
                'phone_verified_at',
                'email_verified_at',
                'verification_code',
                'verification_code_expires_at',
            ]);
        });
    }
};

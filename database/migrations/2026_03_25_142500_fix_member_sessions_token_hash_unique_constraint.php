<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_sessions', function (Blueprint $table) {
            // Drop the single-column unique on token_hash — a member can have
            // multiple sessions (one per device/IP).
            $table->dropUnique(['token_hash']);

            // Add a composite unique so the same device doesn't get duplicate rows.
            $table->unique(['token_hash', 'device_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('member_sessions', function (Blueprint $table) {
            $table->dropUnique(['token_hash', 'device_hash']);
            $table->unique('token_hash');
        });
    }
};

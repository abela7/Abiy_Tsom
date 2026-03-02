<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referral codes belong to admin users (writers, editors, admins)
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 8)
                ->nullable()
                ->unique()
                ->after('role');
        });

        // Track which admin user referred each member
        Schema::table('members', function (Blueprint $table): void {
            $table->foreignId('referred_by')
                ->nullable()
                ->after('token')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['referred_by']);
            $table->dropColumn('referred_by');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('referral_code');
        });
    }
};

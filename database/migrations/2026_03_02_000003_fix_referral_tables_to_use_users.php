<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix referral system: move referral_code from members to users,
 * change referred_by FK from members to users,
 * rename referral_clicks.member_id to user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Add referral_code to users table
        Schema::table('users', function (Blueprint $table): void {
            $table->string('referral_code', 8)
                ->nullable()
                ->unique()
                ->after('role');
        });

        // 2. Remove referral_code from members table
        Schema::table('members', function (Blueprint $table): void {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });

        // 3. Fix referred_by FK: drop old FK (→ members), add new FK (→ users)
        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['referred_by']);
        });
        Schema::table('members', function (Blueprint $table): void {
            $table->foreign('referred_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // 4. Fix referral_clicks: rename member_id to user_id, update FK
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->dropForeign(['member_id']);
        });
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->renameColumn('member_id', 'user_id');
        });
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Reverse: rename user_id back, move referral_code back, etc.
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
        });
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->renameColumn('user_id', 'member_id');
        });
        Schema::table('referral_clicks', function (Blueprint $table): void {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->dropForeign(['referred_by']);
        });
        Schema::table('members', function (Blueprint $table): void {
            $table->foreign('referred_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->string('referral_code', 8)->nullable()->unique()->after('token');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['referral_code']);
            $table->dropColumn('referral_code');
        });
    }
};

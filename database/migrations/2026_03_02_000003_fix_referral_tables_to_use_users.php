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
        // 1. Add referral_code to users table (skip if already added by 000001)
        if (! Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('referral_code', 8)
                    ->nullable()
                    ->unique()
                    ->after('role');
            });
        }

        // 2. Remove referral_code from members table (skip if it was never added there)
        if (Schema::hasColumn('members', 'referral_code')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->dropUnique(['referral_code']);
                $table->dropColumn('referral_code');
            });
        }

        // 3. Fix referred_by FK: only needed if it still points to members
        // (000001 already created it pointing to users, so skip if correct)
        if (Schema::hasColumn('members', 'referred_by')) {
            // The FK already points to users from 000001, nothing to change
        }

        // 4. Fix referral_clicks: rename member_id to user_id (skip if already user_id)
        if (Schema::hasColumn('referral_clicks', 'member_id')) {
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

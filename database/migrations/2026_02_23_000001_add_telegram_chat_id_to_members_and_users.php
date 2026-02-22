<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('members') && ! Schema::hasColumn('members', 'telegram_chat_id')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->string('telegram_chat_id', 64)
                    ->nullable()
                    ->unique()
                    ->after('token');
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('telegram_chat_id', 64)
                    ->nullable()
                    ->unique()
                    ->after('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('members') && Schema::hasColumn('members', 'telegram_chat_id')) {
            Schema::table('members', function (Blueprint $table): void {
                $table->dropColumn('telegram_chat_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'telegram_chat_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('telegram_chat_id');
            });
        }
    }
};


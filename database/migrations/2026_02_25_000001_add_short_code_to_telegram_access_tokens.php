<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_access_tokens') && ! Schema::hasColumn('telegram_access_tokens', 'short_code')) {
            Schema::table('telegram_access_tokens', function (Blueprint $table): void {
                $table->string('short_code', 8)->nullable()->unique()->after('token_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telegram_access_tokens') && Schema::hasColumn('telegram_access_tokens', 'short_code')) {
            Schema::table('telegram_access_tokens', function (Blueprint $table): void {
                $table->dropColumn('short_code');
            });
        }
    }
};

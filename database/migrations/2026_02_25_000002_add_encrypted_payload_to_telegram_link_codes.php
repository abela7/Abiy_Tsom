<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_access_tokens') && ! Schema::hasColumn('telegram_access_tokens', 'encrypted_payload')) {
            Schema::table('telegram_access_tokens', function (Blueprint $table): void {
                $table->text('encrypted_payload')->nullable()->after('short_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('telegram_access_tokens') && Schema::hasColumn('telegram_access_tokens', 'encrypted_payload')) {
            Schema::table('telegram_access_tokens', function (Blueprint $table): void {
                $table->dropColumn('encrypted_payload');
            });
        }
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_himamat_invitation_deliveries', function (Blueprint $table): void {
            $table->unsignedSmallInteger('open_count')->default(0)->after('attempt_count');
            $table->timestamp('first_opened_at')->nullable()->after('delivered_at');
            $table->timestamp('last_opened_at')->nullable()->after('first_opened_at');
        });
    }

    public function down(): void
    {
        Schema::table('member_himamat_invitation_deliveries', function (Blueprint $table): void {
            $table->dropColumn([
                'open_count',
                'first_opened_at',
                'last_opened_at',
            ]);
        });
    }
};

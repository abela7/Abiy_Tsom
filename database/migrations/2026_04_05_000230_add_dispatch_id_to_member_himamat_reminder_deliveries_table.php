<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
            $table->foreignId('himamat_reminder_dispatch_id')
                ->nullable()
                ->after('channel')
                ->constrained('himamat_reminder_dispatches')
                ->nullOnDelete();

            $table->index(
                ['himamat_reminder_dispatch_id', 'status'],
                'member_himamat_dispatch_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
            $table->dropIndex('member_himamat_dispatch_status_idx');
            $table->dropConstrainedForeignId('himamat_reminder_dispatch_id');
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

return new class extends Migration
{
    private const FOREIGN_KEY_NAME = 'member_himamat_dispatch_fk';

    private const INDEX_NAME = 'member_himamat_dispatch_status_idx';

    public function up(): void
    {
        if (! Schema::hasColumn('member_himamat_reminder_deliveries', 'himamat_reminder_dispatch_id')) {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->unsignedBigInteger('himamat_reminder_dispatch_id')
                    ->nullable()
                    ->after('channel');
            });
        }

        try {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->foreign('himamat_reminder_dispatch_id', self::FOREIGN_KEY_NAME)
                    ->references('id')
                    ->on('himamat_reminder_dispatches')
                    ->nullOnDelete();
            });
        } catch (Throwable $exception) {
            if (! $this->isDuplicateDefinitionError($exception)) {
                throw $exception;
            }
        }

        try {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->index(
                    ['himamat_reminder_dispatch_id', 'status'],
                    self::INDEX_NAME
                );
            });
        } catch (Throwable $exception) {
            if (! $this->isDuplicateDefinitionError($exception)) {
                throw $exception;
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->dropIndex(self::INDEX_NAME);
            });
        } catch (Throwable) {
        }

        try {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->dropForeign(self::FOREIGN_KEY_NAME);
            });
        } catch (Throwable) {
        }

        if (Schema::hasColumn('member_himamat_reminder_deliveries', 'himamat_reminder_dispatch_id')) {
            Schema::table('member_himamat_reminder_deliveries', function (Blueprint $table): void {
                $table->dropColumn('himamat_reminder_dispatch_id');
            });
        }
    }

    private function isDuplicateDefinitionError(Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return str_contains($message, 'duplicate')
            || str_contains($message, 'already exists');
    }
};

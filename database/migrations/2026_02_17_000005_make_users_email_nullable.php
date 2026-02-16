<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Align users.email with admin form (optional email).
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            'ALTER TABLE `users` MODIFY `email` VARCHAR(255) NULL'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "UPDATE `users` SET `email` = CONCAT('user', `id`, '@placeholder.local') WHERE `email` IS NULL"
        );

        DB::statement(
            'ALTER TABLE `users` MODIFY `email` VARCHAR(255) NOT NULL'
        );
    }
};

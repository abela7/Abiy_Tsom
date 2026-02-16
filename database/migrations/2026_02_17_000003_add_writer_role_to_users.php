<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM('admin','editor','writer') NOT NULL DEFAULT 'admin'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('users')
            ->where('role', 'writer')
            ->update(['role' => 'editor']);

        DB::statement(
            "ALTER TABLE `users` MODIFY `role` ENUM('admin','editor') NOT NULL DEFAULT 'admin'"
        );
    }
};

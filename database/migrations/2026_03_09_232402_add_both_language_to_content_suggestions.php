<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            // SQLite: 'both' is already included in the base enum definition
            return;
        }

        DB::statement(
            "ALTER TABLE `content_suggestions` MODIFY `language` ENUM('en','am','both') NOT NULL DEFAULT 'en'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('content_suggestions')
            ->where('language', 'both')
            ->update(['language' => 'en']);

        DB::statement(
            "ALTER TABLE `content_suggestions` MODIFY `language` ENUM('en','am') NOT NULL DEFAULT 'en'"
        );
    }
};

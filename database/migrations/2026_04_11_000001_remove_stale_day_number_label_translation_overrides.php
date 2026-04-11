<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Admin DB rows for app.day_number_label previously pinned "(1-55)".
 * Removing them restores lang file values "(1-56)".
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('translations')->where('key', 'day_number_label')->delete();
        Translation::clearCache();
    }

    public function down(): void
    {
        // Overrides were stale; do not re-insert old "(1-55)" text.
    }
};

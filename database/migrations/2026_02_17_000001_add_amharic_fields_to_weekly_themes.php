<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Amharic translations for weekly themes.
     */
    public function up(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            if (! Schema::hasColumn('weekly_themes', 'meaning_am')) {
                $table->string('meaning_am')->nullable()->after('meaning')->comment('Amharic: meaning');
            }
            if (! Schema::hasColumn('weekly_themes', 'description_am')) {
                $table->text('description_am')->nullable()->after('description')->comment('Amharic: description');
            }
            if (! Schema::hasColumn('weekly_themes', 'summary_am')) {
                $table->text('summary_am')->nullable()->after('theme_summary')->comment('Amharic: theme summary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('weekly_themes', 'meaning_am')) {
                $columns[] = 'meaning_am';
            }
            if (Schema::hasColumn('weekly_themes', 'description_am')) {
                $columns[] = 'description_am';
            }
            if (Schema::hasColumn('weekly_themes', 'summary_am')) {
                $columns[] = 'summary_am';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

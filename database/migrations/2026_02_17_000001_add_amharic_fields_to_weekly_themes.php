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
            $table->string('meaning_am')->nullable()->after('meaning')->comment('Amharic: meaning');
            $table->text('description_am')->nullable()->after('description')->comment('Amharic: description');
            $table->text('summary_am')->nullable()->after('theme_summary')->comment('Amharic: theme summary');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->dropColumn(['meaning_am', 'description_am', 'summary_am']);
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Bible reading text in English and Amharic (shown based on user locale).
     */
    public function up(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->text('bible_text_en')->nullable()->after('bible_summary');
            $table->text('bible_text_am')->nullable()->after('bible_text_en');
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->dropColumn(['bible_text_en', 'bible_text_am']);
        });
    }
};

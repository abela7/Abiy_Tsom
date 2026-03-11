<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Cloudflare R2 audio URLs for Bible readings in English and Amharic.
     */
    public function up(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->string('bible_audio_url_en')->nullable()->after('bible_text_am');
            $table->string('bible_audio_url_am')->nullable()->after('bible_audio_url_en');
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->dropColumn(['bible_audio_url_en', 'bible_audio_url_am']);
        });
    }
};

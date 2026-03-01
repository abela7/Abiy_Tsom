<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add structured weekly reading content to weekly_themes.
     *
     * Each week supports:
     *  - 1 feature picture
     *  - 3 Bible readings (reference + full text EN/AM)
     *  - Psalm full text (EN/AM) — reference already in psalm_reference
     *  - Gospel full text (EN/AM) — reference already in gospel_reference
     *  - Liturgy full text (EN/AM) — anaphora name already in liturgy
     */
    public function up(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            // Feature picture
            $table->string('feature_picture')->nullable()->after('liturgy');

            // Bible Reading 1
            $table->string('reading_1_reference')->nullable()->after('feature_picture');
            $table->text('reading_1_text_en')->nullable()->after('reading_1_reference');
            $table->text('reading_1_text_am')->nullable()->after('reading_1_text_en');

            // Bible Reading 2
            $table->string('reading_2_reference')->nullable()->after('reading_1_text_am');
            $table->text('reading_2_text_en')->nullable()->after('reading_2_reference');
            $table->text('reading_2_text_am')->nullable()->after('reading_2_text_en');

            // Bible Reading 3
            $table->string('reading_3_reference')->nullable()->after('reading_2_text_am');
            $table->text('reading_3_text_en')->nullable()->after('reading_3_reference');
            $table->text('reading_3_text_am')->nullable()->after('reading_3_text_en');

            // Psalm full text (reference already stored in psalm_reference)
            $table->text('psalm_text_en')->nullable()->after('reading_3_text_am');
            $table->text('psalm_text_am')->nullable()->after('psalm_text_en');

            // Gospel full text (reference already stored in gospel_reference)
            $table->text('gospel_text_en')->nullable()->after('psalm_text_am');
            $table->text('gospel_text_am')->nullable()->after('gospel_text_en');

            // Liturgy full text (anaphora name already stored in liturgy)
            $table->text('liturgy_text_en')->nullable()->after('gospel_text_am');
            $table->text('liturgy_text_am')->nullable()->after('liturgy_text_en');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->dropColumn([
                'feature_picture',
                'reading_1_reference', 'reading_1_text_en', 'reading_1_text_am',
                'reading_2_reference', 'reading_2_text_en', 'reading_2_text_am',
                'reading_3_reference', 'reading_3_text_en', 'reading_3_text_am',
                'psalm_text_en', 'psalm_text_am',
                'gospel_text_en', 'gospel_text_am',
                'liturgy_text_en', 'liturgy_text_am',
            ]);
        });
    }
};

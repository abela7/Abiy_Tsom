<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Amharic variants for all reference/title fields in weekly_themes.
     *
     * The existing single-language fields (gospel_reference, psalm_reference,
     * liturgy, reading_N_reference) are treated as English; this migration
     * adds their Amharic counterparts.
     */
    public function up(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            // Amharic reference for the 3 bible readings
            $table->string('reading_1_reference_am')->nullable()->after('reading_1_reference');
            $table->string('reading_2_reference_am')->nullable()->after('reading_2_reference');
            $table->string('reading_3_reference_am')->nullable()->after('reading_3_reference');

            // Amharic psalm reference (existing psalm_reference treated as EN)
            $table->string('psalm_reference_am')->nullable()->after('psalm_reference');

            // Amharic gospel reference (existing gospel_reference treated as EN)
            $table->string('gospel_reference_am')->nullable()->after('gospel_reference');

            // Amharic anaphora / liturgy name (existing liturgy treated as EN)
            $table->string('liturgy_am')->nullable()->after('liturgy')
                ->comment('Amharic name of the anaphora');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->dropColumn([
                'reading_1_reference_am',
                'reading_2_reference_am',
                'reading_3_reference_am',
                'psalm_reference_am',
                'gospel_reference_am',
                'liturgy_am',
            ]);
        });
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->string('epistles_reference')->nullable()->after('gospel_reference')
                ->comment('Epistle readings e.g. Hebrews 9:11-end, 1 Peter 4:1-12');
            $table->string('psalm_reference')->nullable()->after('epistles_reference')
                ->comment('Misbak/Responsorial Psalm e.g. 8:2');
            $table->string('liturgy')->nullable()->after('psalm_reference')
                ->comment('Anaphora used e.g. St. Gregory, Athanasius');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->dropColumn(['epistles_reference', 'psalm_reference', 'liturgy']);
        });
    }
};

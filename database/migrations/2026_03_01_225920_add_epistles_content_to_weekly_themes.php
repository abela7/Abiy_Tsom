<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->string('epistles_reference_am', 500)->nullable()->after('epistles_reference');
            $table->text('epistles_text_en')->nullable()->after('epistles_reference_am');
            $table->text('epistles_text_am')->nullable()->after('epistles_text_en');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_themes', function (Blueprint $table) {
            $table->dropColumn(['epistles_reference_am', 'epistles_text_en', 'epistles_text_am']);
        });
    }
};

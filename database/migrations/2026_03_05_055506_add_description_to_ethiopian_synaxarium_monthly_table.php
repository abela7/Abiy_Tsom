<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('celebration_am');
            $table->text('description_am')->nullable()->after('description_en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table) {
            $table->dropColumn(['description_en', 'description_am']);
        });
    }
};

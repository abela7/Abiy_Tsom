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
        Schema::table('daily_contents', function (Blueprint $table) {
            $table->string('reflection_title_am')->nullable()->after('reflection_am');
            $table->string('reflection_title_en')->nullable()->after('reflection_title_am');
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table) {
            $table->dropColumn(['reflection_title_am', 'reflection_title_en']);
        });
    }
};

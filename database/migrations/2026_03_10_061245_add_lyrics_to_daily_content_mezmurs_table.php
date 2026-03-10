<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_content_mezmurs', function (Blueprint $table) {
            $table->text('lyrics_en')->nullable()->after('description_en');
            $table->text('lyrics_am')->nullable()->after('description_am');
        });
    }

    public function down(): void
    {
        Schema::table('daily_content_mezmurs', function (Blueprint $table) {
            $table->dropColumn(['lyrics_en', 'lyrics_am']);
        });
    }
};

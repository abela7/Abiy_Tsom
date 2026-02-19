<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('photo_en')->nullable()->after('photo');
            $table->string('youtube_url_en')->nullable()->after('youtube_url');
            $table->string('youtube_position_en', 10)->nullable()->after('youtube_position');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn(['photo_en', 'youtube_url_en', 'youtube_position_en']);
        });
    }
};

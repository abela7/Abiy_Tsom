<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add type to references: video (YouTube), website, or file.
     * Writer chooses type; system shows appropriate button (View video, Read more, View file).
     */
    public function up(): void
    {
        Schema::table('daily_content_references', function (Blueprint $table): void {
            $table->string('type', 20)->default('website')->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('daily_content_references', function (Blueprint $table): void {
            $table->dropColumn('type');
        });
    }
};

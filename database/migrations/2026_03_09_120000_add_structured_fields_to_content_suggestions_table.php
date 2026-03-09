<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table): void {
            $table->string('source', 20)->default('web')->after('user_id');
            $table->string('content_area', 40)->nullable()->after('type');
            $table->unsignedTinyInteger('ethiopian_month')->nullable()->after('language');
            $table->unsignedTinyInteger('ethiopian_day')->nullable()->after('ethiopian_month');
            $table->string('entry_scope', 20)->nullable()->after('ethiopian_day');
            $table->string('image_path', 500)->nullable()->after('url');
            $table->json('structured_payload')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table): void {
            $table->dropColumn([
                'source',
                'content_area',
                'ethiopian_month',
                'ethiopian_day',
                'entry_scope',
                'image_path',
                'structured_payload',
            ]);
        });
    }
};

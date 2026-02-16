<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add sinksar_url (YouTube link) and rename sinksar_content to sinksar_description
     * to match the mezmur structure (title, url, description).
     */
    public function up(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->string('sinksar_url', 500)->nullable()->after('sinksar_title')
                ->comment('YouTube or video link for Synaxarium');
        });

        if (Schema::hasColumn('daily_contents', 'sinksar_content')) {
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->text('sinksar_description')->nullable()->after('sinksar_url');
            });
            DB::table('daily_contents')->update(['sinksar_description' => DB::raw('sinksar_content')]);
            Schema::table('daily_contents', function (Blueprint $table): void {
                $table->dropColumn('sinksar_content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->dropColumn(['sinksar_url', 'sinksar_description']);
        });
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->text('sinksar_content')->nullable()->after('sinksar_title');
        });
    }
};

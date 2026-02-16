<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Multiple Mezmur entries per day — migrate from single mezmur columns.
     */
    public function up(): void
    {
        Schema::create('daily_content_mezmurs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url', 500)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrate existing mezmur data
        $rows = DB::table('daily_contents')
            ->whereNotNull('mezmur_title')
            ->where('mezmur_title', '!=', '')
            ->get(['id', 'mezmur_title', 'mezmur_url', 'mezmur_description']);

        foreach ($rows as $row) {
            DB::table('daily_content_mezmurs')->insert([
                'daily_content_id' => $row->id,
                'title' => $row->mezmur_title,
                'url' => $row->mezmur_url,
                'description' => $row->mezmur_description,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->dropColumn(['mezmur_title', 'mezmur_url', 'mezmur_description']);
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->string('mezmur_title')->nullable()->after('bible_text_am');
            $table->string('mezmur_url', 500)->nullable()->after('mezmur_title');
            $table->text('mezmur_description')->nullable()->after('mezmur_url');
        });

        Schema::dropIfExists('daily_content_mezmurs');
    }
};

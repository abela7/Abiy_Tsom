<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create SEO settings key-value store.
     */
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['key' => 'site_title_en', 'value' => 'Abiy Tsom'],
            ['key' => 'site_title_am', 'value' => 'Abiy Tsom'],
            ['key' => 'meta_description_en', 'value' => 'Get your daily dose of faith during Great Lent: Bible readings, mezmur, Sinksar, and spiritual books every day until Easter. Stay engaged and keep Lent meaningfully.'],
            ['key' => 'meta_description_am', 'value' => 'Get your daily dose of faith during Great Lent: Bible readings, mezmur, Sinksar, and spiritual books every day until Easter. Stay engaged and keep Lent meaningfully.'],
            ['key' => 'og_title_en', 'value' => 'Abiy Tsom - Daily Faith Content for Great Lent'],
            ['key' => 'og_title_am', 'value' => 'Abiy Tsom - Daily Faith Content for Great Lent'],
            ['key' => 'og_description_en', 'value' => 'Bible readings, mezmur, Sinksar and spiritual books delivered daily. Stay engaged and do Lent properly, one day at a time until Easter.'],
            ['key' => 'og_description_am', 'value' => 'Bible readings, mezmur, Sinksar and spiritual books delivered daily. Stay engaged and do Lent properly, one day at a time until Easter.'],
            ['key' => 'twitter_card', 'value' => 'summary_large_image'],
            ['key' => 'robots', 'value' => 'index,follow,max-image-preview:large'],
            ['key' => 'og_image', 'value' => null],
        ];

        DB::table('seo_settings')->insert(
            array_map(
                static fn (array $item): array => [
                    'key' => $item['key'],
                    'value' => $item['value'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $defaults
            )
        );
    }

    /**
     * Drop SEO settings table.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};

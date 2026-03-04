<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_content_sinksar_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->string('caption_en', 255)->nullable();
            $table->string('caption_am', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_content_sinksar_images');
    }
};

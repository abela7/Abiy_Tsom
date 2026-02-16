<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-curated content for each of the 55 days.
     * This is the core table the admin feeds daily.
     */
    public function up(): void
    {
        Schema::create('daily_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->foreignId('weekly_theme_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number')->comment('1-55');
            $table->date('date');
            $table->string('day_title')->nullable()->comment('Optional title for the day');

            // Bible reading
            $table->string('bible_reference')->nullable()->comment('e.g. John 3:1-16');
            $table->text('bible_summary')->nullable()->comment('Brief summary of the reading');

            // Mezmur / spiritual music
            $table->string('mezmur_title')->nullable();
            $table->string('mezmur_url')->nullable()->comment('YouTube or audio link');
            $table->text('mezmur_description')->nullable();

            // Sinksar (Synaxarium)
            $table->string('sinksar_title')->nullable();
            $table->text('sinksar_content')->nullable();

            // Spiritual book / resource
            $table->string('book_title')->nullable();
            $table->string('book_url')->nullable();
            $table->text('book_description')->nullable();

            // Daily reflection / message from admin
            $table->text('reflection')->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['lent_season_id', 'day_number']);
            $table->unique(['lent_season_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_contents');
    }
};

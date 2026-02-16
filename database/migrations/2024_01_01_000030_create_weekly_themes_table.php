<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 8 weekly themes of the Great Lent.
     */
    public function up(): void
    {
        Schema::create('weekly_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number')->comment('1-8');
            $table->string('name_geez')->nullable()->comment('e.g. ዘወረደ');
            $table->string('name_en')->comment('e.g. Zewerede');
            $table->string('name_am')->nullable()->comment('Amharic name');
            $table->string('meaning')->comment('e.g. He who descended from above');
            $table->text('description')->nullable();
            $table->string('gospel_reference')->nullable()->comment('e.g. John 3:16');
            $table->text('theme_summary')->nullable();
            $table->date('week_start_date');
            $table->date('week_end_date');
            $table->timestamps();

            $table->unique(['lent_season_id', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_themes');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->string('slug');
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->date('date');
            $table->string('title_en');
            $table->string('title_am')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lent_season_id', 'slug']);
            $table->unique(['lent_season_id', 'date']);
            $table->index(['lent_season_id', 'is_published', 'date'], 'himamat_days_season_publish_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_days');
    }
};

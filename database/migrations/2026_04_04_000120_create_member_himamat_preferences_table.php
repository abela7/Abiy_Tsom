<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_himamat_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('intro_enabled')->default(true);
            $table->boolean('third_enabled')->default(true);
            $table->boolean('sixth_enabled')->default(true);
            $table->boolean('ninth_enabled')->default(true);
            $table->boolean('eleventh_enabled')->default(true);
            $table->timestamps();

            $table->unique(['member_id', 'lent_season_id']);
            $table->index(['lent_season_id', 'enabled'], 'member_himamat_preferences_season_enabled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_himamat_preferences');
    }
};

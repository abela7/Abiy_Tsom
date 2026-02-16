<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-defined spiritual activities for daily checklists.
     * e.g. "Did you pray today?", "Did you fast properly?",
     *      "Did you give to the needy?"
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('icon')->nullable()->comment('Emoji or icon class');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};

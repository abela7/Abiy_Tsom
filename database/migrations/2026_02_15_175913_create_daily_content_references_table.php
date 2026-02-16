<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * References (links) for each day — "know more" about the week or day.
     */
    public function up(): void
    {
        Schema::create('daily_content_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('url', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_content_references');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ethiopian_synaxarium_monthly', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('day')->unique();
            $table->string('celebration_en', 500);
            $table->string('celebration_am', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ethiopian_synaxarium_monthly');
    }
};

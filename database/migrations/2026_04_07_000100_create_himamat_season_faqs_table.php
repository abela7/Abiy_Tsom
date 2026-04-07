<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_season_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lent_season_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('question_en')->nullable();
            $table->string('question_am')->nullable();
            $table->text('answer_en')->nullable();
            $table->text('answer_am')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_season_faqs');
    }
};

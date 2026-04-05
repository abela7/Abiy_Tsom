<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_day_faqs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('himamat_day_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->string('question_en');
            $table->string('question_am')->nullable();
            $table->text('answer_en');
            $table->text('answer_am')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['himamat_day_id', 'sort_order'], 'himamat_day_faqs_day_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_day_faqs');
    }
};

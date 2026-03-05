<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lectionary', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('month'); // 1–13 Ethiopian month
            $table->unsignedTinyInteger('day');   // 1–30

            // Pauline Epistle (ጳውሎስ)
            $table->string('pauline_book_am', 100)->nullable();
            $table->string('pauline_book_en', 100)->nullable();
            $table->unsignedTinyInteger('pauline_chapter')->nullable();
            $table->string('pauline_verses', 30)->nullable();
            $table->text('pauline_text_am')->nullable();
            $table->text('pauline_text_en')->nullable();

            // Catholic Epistle (ካቶሊካዊ መልእክት)
            $table->string('catholic_book_am', 100)->nullable();
            $table->string('catholic_book_en', 100)->nullable();
            $table->unsignedTinyInteger('catholic_chapter')->nullable();
            $table->string('catholic_verses', 30)->nullable();
            $table->text('catholic_text_am')->nullable();
            $table->text('catholic_text_en')->nullable();

            // Acts of the Apostles (ሐዋርያት)
            $table->unsignedTinyInteger('acts_chapter')->nullable();
            $table->string('acts_verses', 30)->nullable();
            $table->text('acts_text_am')->nullable();
            $table->text('acts_text_en')->nullable();

            // Mesbak / Psalm (ምስባክ)
            $table->unsignedSmallInteger('mesbak_psalm')->nullable();
            $table->string('mesbak_verses', 30)->nullable();
            $table->text('mesbak_text_geez')->nullable();
            $table->text('mesbak_text_am')->nullable();
            $table->text('mesbak_text_en')->nullable();

            // Gospel (ወንጌል)
            $table->string('gospel_book_am', 100)->nullable();
            $table->string('gospel_book_en', 100)->nullable();
            $table->unsignedTinyInteger('gospel_chapter')->nullable();
            $table->string('gospel_verses', 30)->nullable();
            $table->text('gospel_text_am')->nullable();
            $table->text('gospel_text_en')->nullable();

            // Qiddase / Anaphora (ቅዳሴ)
            $table->string('qiddase_am', 300)->nullable();
            $table->string('qiddase_en', 300)->nullable();

            $table->timestamps();

            $table->unique(['month', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lectionary');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('himamat_day_id')->constrained()->cascadeOnDelete();
            $table->string('slot_key', 32);
            $table->unsignedTinyInteger('slot_order')->default(1);
            $table->time('scheduled_time_london');
            $table->string('slot_header_en');
            $table->string('slot_header_am')->nullable();
            $table->string('reminder_header_en');
            $table->string('reminder_header_am')->nullable();
            $table->text('spiritual_significance_en')->nullable();
            $table->text('spiritual_significance_am')->nullable();
            $table->string('reading_reference_en')->nullable();
            $table->string('reading_reference_am')->nullable();
            $table->longText('reading_text_en')->nullable();
            $table->longText('reading_text_am')->nullable();
            $table->unsignedSmallInteger('prostration_count')->default(0);
            $table->text('prostration_guidance_en')->nullable();
            $table->text('prostration_guidance_am')->nullable();
            $table->text('short_prayer_en')->nullable();
            $table->text('short_prayer_am')->nullable();
            $table->boolean('is_published')->default(false);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['himamat_day_id', 'slot_key']);
            $table->index(['scheduled_time_london', 'is_published'], 'himamat_slots_time_publish_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_slots');
    }
};

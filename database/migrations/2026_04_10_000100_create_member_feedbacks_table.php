<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_feedbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->enum('status', ['pending', 'draft', 'submitted'])->default('pending');

            // Survey questions
            $table->unsignedTinyInteger('q1_overall_rating')->nullable();
            $table->string('q2_most_used_feature', 32)->nullable();
            $table->unsignedTinyInteger('q3_himamat_rating')->nullable();
            $table->boolean('q4_whatsapp_reminder_useful')->nullable();
            $table->text('q5_suggestion')->nullable();
            $table->boolean('q6_opt_in_future_fasts')->nullable();

            // Submission tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_saved_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamps();

            // One survey per member
            $table->unique('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_feedbacks');
    }
};

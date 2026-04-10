<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_feedbacks', function (Blueprint $table): void {
            // Drop old static question columns
            $table->dropColumn([
                'q1_overall_rating',
                'q2_most_used_feature',
                'q3_himamat_rating',
                'q4_whatsapp_reminder_useful',
                'q5_suggestion',
                'q6_opt_in_future_fasts',
            ]);

            // Add new conditional-flow columns
            $table->string('q1_usefulness', 32)->nullable()->after('status');
            $table->text('q2_improvement_feedback')->nullable()->after('q1_usefulness');
            $table->string('q3_continuity_preference', 32)->nullable()->after('q2_improvement_feedback');
            $table->unsignedTinyInteger('q4_overall_rating')->nullable()->after('q3_continuity_preference');
        });
    }

    public function down(): void
    {
        Schema::table('member_feedbacks', function (Blueprint $table): void {
            $table->dropColumn([
                'q1_usefulness',
                'q2_improvement_feedback',
                'q3_continuity_preference',
                'q4_overall_rating',
            ]);

            $table->unsignedTinyInteger('q1_overall_rating')->nullable();
            $table->string('q2_most_used_feature', 32)->nullable();
            $table->unsignedTinyInteger('q3_himamat_rating')->nullable();
            $table->boolean('q4_whatsapp_reminder_useful')->nullable();
            $table->text('q5_suggestion')->nullable();
            $table->boolean('q6_opt_in_future_fasts')->nullable();
        });
    }
};

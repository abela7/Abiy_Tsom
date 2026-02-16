<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completion tracking for member custom activities per day.
     */
    public function up(): void
    {
        Schema::create('member_custom_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_custom_activity_id')->constrained('member_custom_activities')->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['member_id', 'daily_content_id', 'member_custom_activity_id'], 'member_day_custom_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_custom_checklists');
    }
};

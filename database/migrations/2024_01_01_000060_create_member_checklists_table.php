<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily check-in for each member.
     * Each row = one member + one day + one activity.
     */
    public function up(): void
    {
        Schema::create('member_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['member_id', 'daily_content_id', 'activity_id'], 'member_day_activity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_checklists');
    }
};

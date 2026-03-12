<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Track unique member views per daily content page.
     */
    public function up(): void
    {
        Schema::create('member_daily_views', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->unique(['member_id', 'daily_content_id']);
            $table->index('daily_content_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_daily_views');
    }
};

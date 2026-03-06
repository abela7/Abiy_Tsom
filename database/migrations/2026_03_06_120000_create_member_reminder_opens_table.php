<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_reminder_opens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_content_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable()->index();
            $table->timestamp('last_authenticated_open_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('authenticated_open_count')->default(0);
            $table->unsignedInteger('public_open_count')->default(0);
            $table->string('last_open_state', 32)->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->string('last_user_agent', 512)->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'daily_content_id']);
            $table->index(['member_id', 'last_opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_reminder_opens');
    }
};

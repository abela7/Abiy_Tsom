<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasika_greeting_shares', function (Blueprint $table): void {
            $table->id();
            $table->string('share_token', 48)->unique();
            $table->string('sender_name', 120);
            $table->string('sender_name_normalized', 120)->index();
            $table->unsignedInteger('open_count')->default(0);
            $table->string('creator_ip', 45)->nullable();
            $table->text('creator_user_agent')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->string('last_opened_ip', 45)->nullable();
            $table->text('last_opened_user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasika_greeting_shares');
    }
};

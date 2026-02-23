<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_states', function (Blueprint $table): void {
            $table->id();
            $table->string('chat_id', 64)->index();
            $table->string('action', 50);
            $table->string('step', 50);
            $table->json('data')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            // One active wizard per action per chat
            $table->unique(['chat_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_states');
    }
};

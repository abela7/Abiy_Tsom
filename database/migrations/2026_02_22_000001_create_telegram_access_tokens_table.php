<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_access_tokens')) {
            return;
        }

        Schema::create('telegram_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('purpose', 64)->index();
            $table->string('actor_type')->nullable()->index();
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->string('redirect_to')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamps();

            $table->index(
                ['purpose', 'actor_type', 'actor_id', 'expires_at'],
                'toks_purpose_actor_expires_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_access_tokens');
    }
};

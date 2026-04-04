<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_himamat_reminder_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('himamat_slot_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->timestamp('due_at_london');
            $table->string('status', 32)->default('queued');
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'himamat_slot_id', 'channel'], 'member_himamat_delivery_unique');
            $table->index(['channel', 'status', 'due_at_london'], 'member_himamat_delivery_channel_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_himamat_reminder_deliveries');
    }
};

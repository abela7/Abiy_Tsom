<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_reminder_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('himamat_slot_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('whatsapp');
            $table->timestamp('due_at_london');
            $table->string('status', 32)->default('queued');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('dispatch_started_at')->nullable();
            $table->timestamp('dispatch_finished_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['himamat_slot_id', 'channel'], 'himamat_dispatch_slot_channel_unique');
            $table->index(['channel', 'status', 'due_at_london'], 'himamat_dispatch_channel_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_reminder_dispatches');
    }
};

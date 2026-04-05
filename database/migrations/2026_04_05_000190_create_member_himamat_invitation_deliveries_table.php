<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_himamat_invitation_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('campaign_key', 120);
            $table->string('channel', 32)->default('whatsapp');
            $table->string('destination_phone', 32)->nullable();
            $table->string('status', 32)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'campaign_key', 'channel'], 'himamat_invitation_unique_campaign');
            $table->index(['campaign_key', 'channel', 'status'], 'himamat_invitation_campaign_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_himamat_invitation_deliveries');
    }
};

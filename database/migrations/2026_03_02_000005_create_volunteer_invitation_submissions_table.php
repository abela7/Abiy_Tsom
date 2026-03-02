<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_invitation_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('volunteer_invitation_campaign_id');
            $table->string('visitor_token', 64);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referer', 1024)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('video_started_at')->nullable();
            $table->timestamp('video_completed_at')->nullable();
            $table->string('decision', 32)->nullable();
            $table->timestamp('decision_at')->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('preferred_contact_method', 20)->nullable();
            $table->timestamp('contact_submitted_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();

            $table->foreign(
                'volunteer_invitation_campaign_id',
                'v_inv_sub_campaign_fk'
            )->references('id')
                ->on('volunteer_invitation_campaigns')
                ->cascadeOnDelete();

            $table->unique(
                ['volunteer_invitation_campaign_id', 'visitor_token'],
                'v_inv_sub_campaign_token_uq'
            );
            $table->index('decision', 'v_inv_sub_decision_idx');
            $table->index('opened_at', 'v_inv_sub_opened_at_idx');
            $table->index('video_started_at', 'v_inv_sub_video_start_idx');
            $table->index('video_completed_at', 'v_inv_sub_video_done_idx');
            $table->index('contact_submitted_at', 'v_inv_sub_contact_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_invitation_submissions');
    }
};

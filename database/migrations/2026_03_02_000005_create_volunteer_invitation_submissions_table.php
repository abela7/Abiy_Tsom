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
            $table->foreignId('volunteer_invitation_campaign_id')
                ->constrained('volunteer_invitation_campaigns')
                ->cascadeOnDelete();
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

            $table->unique(['volunteer_invitation_campaign_id', 'visitor_token']);
            $table->index('decision');
            $table->index('opened_at');
            $table->index('video_started_at');
            $table->index('video_completed_at');
            $table->index('contact_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_invitation_submissions');
    }
};

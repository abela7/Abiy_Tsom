<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_invitation_submissions', function (Blueprint $table): void {
            $table->timestamp('video_skipped_at')->nullable()->after('video_completed_at');
            $table->timestamp('shared_at')->nullable()->after('contact_submitted_at');
            $table->timestamp('last_activity_at')->nullable()->after('shared_at');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_invitation_submissions', function (Blueprint $table): void {
            $table->dropColumn(['video_skipped_at', 'shared_at', 'last_activity_at']);
        });
    }
};

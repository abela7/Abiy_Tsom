<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_fundraising_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('fundraising_campaigns')->cascadeOnDelete();
            // 'snoozed' = clicked "Not Today", 'interested' = submitted contact details
            $table->enum('status', ['snoozed', 'interested'])->nullable();
            // Date of last snooze — used to decide whether to show popup again next day
            $table->date('last_snoozed_date')->nullable();
            // Contact details collected when member expresses interest
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamp('interested_at')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'campaign_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_fundraising_responses');
    }
};

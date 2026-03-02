<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('volunteer_invitation_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 120)->unique();
            $table->string('youtube_url', 500)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('volunteer_invitation_campaigns');
    }
};

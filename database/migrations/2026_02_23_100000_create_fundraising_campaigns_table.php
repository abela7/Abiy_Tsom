<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fundraising_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title')->default('Help Us Buy Our Church Building');
            $table->text('description')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('donate_url')->default('https://donate.abuneteklehaymanot.org/');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fundraising_campaigns');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('volunteer_invitation_campaigns', function (Blueprint $table): void {
            $table->string('seo_title', 160)->nullable()->after('name');
            $table->string('seo_description', 500)->nullable()->after('seo_title');
        });
    }

    public function down(): void
    {
        Schema::table('volunteer_invitation_campaigns', function (Blueprint $table): void {
            $table->dropColumn(['seo_title', 'seo_description']);
        });
    }
};


<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow anonymous view tracking with IP address.
     * - Make member_id nullable (anonymous visitors won't have one)
     * - Add ip_address to identify unique anonymous visitors
     */
    public function up(): void
    {
        Schema::table('member_daily_views', function (Blueprint $table): void {
            // Make member_id nullable for anonymous views
            $table->unsignedBigInteger('member_id')->nullable()->change();

            // Store IP address for all views (member + anonymous)
            $table->string('ip_address', 45)->nullable()->after('daily_content_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_daily_views', function (Blueprint $table): void {
            $table->dropColumn('ip_address');
            $table->unsignedBigInteger('member_id')->nullable(false)->change();
        });
    }
};

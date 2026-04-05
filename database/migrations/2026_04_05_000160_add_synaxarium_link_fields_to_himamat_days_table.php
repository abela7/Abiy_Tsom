<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('himamat_days', function (Blueprint $table): void {
            $table->string('synaxarium_source', 16)->default('automatic')->after('ritual_guide_intro_am');
            $table->unsignedTinyInteger('synaxarium_month')->nullable()->after('synaxarium_source');
            $table->unsignedTinyInteger('synaxarium_day')->nullable()->after('synaxarium_month');
        });
    }

    public function down(): void
    {
        Schema::table('himamat_days', function (Blueprint $table): void {
            $table->dropColumn([
                'synaxarium_source',
                'synaxarium_month',
                'synaxarium_day',
            ]);
        });
    }
};

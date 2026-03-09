<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table): void {
            $table->longText('description_en')->nullable()->change();
            $table->longText('description_am')->nullable()->change();
        });

        Schema::table('ethiopian_synaxarium_annual', function (Blueprint $table): void {
            $table->longText('description_en')->nullable()->change();
            $table->longText('description_am')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ethiopian_synaxarium_monthly', function (Blueprint $table): void {
            $table->text('description_en')->nullable()->change();
            $table->text('description_am')->nullable()->change();
        });

        Schema::table('ethiopian_synaxarium_annual', function (Blueprint $table): void {
            $table->text('description_en')->nullable()->change();
            $table->text('description_am')->nullable()->change();
        });
    }
};

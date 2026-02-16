<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each year's lent season configuration.
     */
    public function up(): void
    {
        Schema::create('lent_seasons', function (Blueprint $table) {
            $table->id();
            $table->year('year')->unique();
            $table->date('start_date');
            $table->date('end_date')->comment('Easter Sunday');
            $table->integer('total_days')->default(55);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lent_seasons');
    }
};

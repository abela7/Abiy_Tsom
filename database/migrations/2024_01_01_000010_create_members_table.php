<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Church members — lightweight, no email required.
     * Identified by a unique token stored in browser localStorage.
     * Optional passcode for app-level lock.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('baptism_name');
            $table->string('token', 64)->unique()->comment('Stored in localStorage to identify member');
            $table->string('passcode')->nullable()->comment('Hashed 4-6 digit PIN for app lock');
            $table->boolean('passcode_enabled')->default(false);
            $table->string('locale', 5)->default('en');
            $table->string('theme', 10)->default('light');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

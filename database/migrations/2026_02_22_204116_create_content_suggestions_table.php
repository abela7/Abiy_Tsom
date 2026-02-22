<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_suggestions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['bible', 'mezmur', 'sinksar', 'book', 'reference']);
            $table->enum('language', ['en', 'am'])->default('en');
            $table->enum('status', ['pending', 'reviewed', 'approved', 'rejected'])->default('pending');
            $table->string('submitter_name', 100)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('reference', 500)->nullable();
            $table->string('author', 255)->nullable();
            $table->text('content_detail')->nullable();
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_suggestions');
    }
};

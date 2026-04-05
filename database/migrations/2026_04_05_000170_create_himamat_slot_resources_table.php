<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('himamat_slot_resources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('himamat_slot_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->string('title_en')->nullable();
            $table->string('title_am')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['himamat_slot_id', 'sort_order'], 'himamat_slot_resources_slot_sort_idx');
            $table->index(['type'], 'himamat_slot_resources_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('himamat_slot_resources');
    }
};

<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name')->nullable()->index();
            $table->string('action')->nullable();
            $table->string('method', 10);
            $table->string('url', 2048);
            $table->string('target_type')->nullable()->index();
            $table->string('target_id')->nullable()->index();
            $table->string('target_label')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

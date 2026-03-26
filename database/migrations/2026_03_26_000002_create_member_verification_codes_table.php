<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_verification_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 32)->index();
            $table->string('code_hash', 64);
            $table->string('device_hash', 64)->nullable()->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['member_id', 'used_at', 'expires_at'], 'member_verification_codes_member_valid_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_verification_codes');
    }
};

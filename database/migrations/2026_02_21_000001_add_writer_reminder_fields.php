<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('whatsapp_phone', 20)->nullable()->after('email');
        });

        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->foreignId('assigned_to_id')
                ->nullable()
                ->after('updated_by_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_contents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_to_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('whatsapp_phone');
        });
    }
};

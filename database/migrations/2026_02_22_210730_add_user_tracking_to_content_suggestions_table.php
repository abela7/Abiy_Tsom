<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('used_by_id')->nullable()->after('ip_address')->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->after('used_by_id');
            $table->text('admin_notes')->nullable()->after('used_at');
        });
    }

    public function down(): void
    {
        Schema::table('content_suggestions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('used_by_id');
            $table->dropColumn(['used_at', 'admin_notes']);
        });
    }
};

<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
            $table->boolean('is_super_admin')->default(false)->after('role');
        });

        $first = User::orderBy('id')->first();
        if ($first) {
            $first->update([
                'username' => $first->username ?? 'superadmin',
                'is_super_admin' => true,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'is_super_admin']);
        });
    }
};

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default super admin (only one exists)
        $admin = User::firstOrCreate(
            ['email' => 'admin@abiy-tsom.com'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_super_admin' => true,
            ]
        );
        if (! $admin->is_super_admin && User::where('is_super_admin', true)->count() === 0) {
            $admin->update(['username' => 'superadmin', 'is_super_admin' => true]);
        }

        $this->command->info('✓ Super admin created (username: superadmin / password: password)');

        // Run ProgressSampleDataSeeder when you need demo progress charts:
        //   php artisan db:seed --class=ProgressSampleDataSeeder
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminUserPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('whatsapp_phone')->nullable();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_super_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_super_admin_can_change_an_admin_password(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $admin = User::create([
            'name' => 'Editor User',
            'username' => 'editor1',
            'email' => 'editor@example.com',
            'password' => bcrypt('old-password'),
            'role' => 'editor',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($superAdmin)->put(
            route('admin.admins.update', $admin),
            [
                'name' => $admin->name,
                'username' => $admin->username,
                'email' => $admin->email,
                'whatsapp_phone' => '',
                'role' => $admin->role,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]
        );

        $response->assertRedirect(route('admin.admins.index'));
        $this->assertTrue(Hash::check('new-password', (string) $admin->fresh()?->password));
        $this->assertFalse(Hash::check('old-password', (string) $admin->fresh()?->password));
    }

    public function test_super_admin_sees_change_password_action_on_admin_list(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        User::create([
            'name' => 'Writer User',
            'username' => 'writer1',
            'email' => 'writer@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'writer',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.admins.index'));

        $response->assertOk()
            ->assertSeeText('Change Password');
    }

    public function test_super_admin_can_update_own_password_without_role_field(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('old-password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->put(
            route('admin.admins.update', $superAdmin),
            [
                'name' => $superAdmin->name,
                'username' => $superAdmin->username,
                'email' => $superAdmin->email,
                'whatsapp_phone' => '',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]
        );

        $response->assertRedirect(route('admin.admins.index'));
        $this->assertTrue(Hash::check('new-password', (string) $superAdmin->fresh()?->password));
        $this->assertSame('admin', $superAdmin->fresh()?->role);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminProfileTest extends TestCase
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

    public function test_writer_can_view_own_profile_page(): void
    {
        $writer = User::create([
            'name' => 'Writer User',
            'username' => 'writer1',
            'email' => 'writer@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'writer',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($writer)->get(route('admin.profile.edit'));

        $response->assertOk()
            ->assertSeeText('My Profile')
            ->assertSeeText('Writer User');
    }

    public function test_writer_can_update_own_name_without_changing_password(): void
    {
        $writer = User::create([
            'name' => 'Writer User',
            'username' => 'writer1',
            'email' => 'writer@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'writer',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($writer)->put(route('admin.profile.update'), [
            'name' => 'Writer Updated',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $this->assertSame('Writer Updated', $writer->fresh()?->name);
        $this->assertTrue(Hash::check('secret123', (string) $writer->fresh()?->password));
    }

    public function test_writer_must_supply_current_password_to_change_password(): void
    {
        $writer = User::create([
            'name' => 'Writer User',
            'username' => 'writer1',
            'email' => 'writer@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'writer',
            'is_super_admin' => false,
        ]);

        $response = $this->from(route('admin.profile.edit'))
            ->actingAs($writer)
            ->put(route('admin.profile.update'), [
                'name' => 'Writer User',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('secret123', (string) $writer->fresh()?->password));
    }

    public function test_writer_can_change_own_password_with_current_password(): void
    {
        $writer = User::create([
            'name' => 'Writer User',
            'username' => 'writer1',
            'email' => 'writer@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'writer',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($writer)->put(route('admin.profile.update'), [
            'name' => 'Writer User',
            'current_password' => 'secret123',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect(route('admin.profile.edit'));
        $this->assertTrue(Hash::check('new-password', (string) $writer->fresh()?->password));
    }
}

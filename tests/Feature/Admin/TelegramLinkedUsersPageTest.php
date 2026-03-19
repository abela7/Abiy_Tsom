<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramLinkedUsersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_telegram_linked_users_page(): void
    {
        $super = User::create([
            'name' => 'Super',
            'username' => 'super',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        Member::create([
            'baptism_name' => 'Linked Member',
            'token' => 'tokentelegramlinked123456789012345678',
            'telegram_chat_id' => '999888777',
            'locale' => 'am',
        ]);

        $response = $this->actingAs($super)->get(route('admin.telegram.users'));

        $response->assertOk()
            ->assertViewIs('admin.telegram.linked-users')
            ->assertViewHas(['memberCount', 'staffCount', 'members'])
            ->assertSee('999888777')
            ->assertSee('Linked Member');
    }

    public function test_non_super_admin_cannot_view_telegram_linked_users_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.telegram.users'));

        $response->assertForbidden();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Member;
use App\Models\MemberSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembersAdminBaptismNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_update_member_baptism_name(): void
    {
        $super = User::create([
            'name' => 'Super',
            'username' => 'super',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Old Name',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
        ]);

        $response = $this->actingAs($super)->patch(route('admin.members.update', $member), [
            'baptism_name' => 'New Display Name',
        ]);

        $response->assertRedirect(route('admin.members.show', $member));
        $response->assertSessionHas('success');

        $member->refresh();
        $this->assertSame('New Display Name', $member->baptism_name);
    }

    public function test_member_home_shows_updated_baptism_name_after_admin_change(): void
    {
        $super = User::create([
            'name' => 'Super',
            'username' => 'super',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Before Admin Edit',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
        ]);

        $sessionToken = str_repeat('x', 40);
        $deviceId = str_repeat('y', 20);
        MemberSession::create([
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $sessionToken),
            'device_hash' => hash('sha256', $deviceId),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test',
            'last_used_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($super)->patch(route('admin.members.update', $member), [
            'baptism_name' => 'After Admin Edit',
        ])->assertRedirect();

        $home = $this->get($member->personalUrl('/home'));

        $home->assertOk();
        $home->assertSee('After Admin Edit', false);
        $home->assertDontSee('Before Admin Edit', false);
    }

    public function test_baptism_name_update_requires_non_empty_string(): void
    {
        $super = User::create([
            'name' => 'Super',
            'username' => 'super',
            'email' => 'super@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Valid Name',
            'token' => str_repeat('c', 64),
            'locale' => 'en',
        ]);

        $response = $this->actingAs($super)->from(route('admin.members.show', $member))
            ->patch(route('admin.members.update', $member), [
                'baptism_name' => '   ',
            ]);

        $response->assertRedirect(route('admin.members.show', $member));
        $response->assertSessionHasErrors('baptism_name');

        $this->assertSame('Valid Name', $member->fresh()->baptism_name);
    }

    public function test_non_super_admin_cannot_update_member_baptism_name(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $member = Member::create([
            'baptism_name' => 'Member',
            'token' => str_repeat('d', 64),
            'locale' => 'en',
        ]);

        $this->actingAs($admin)->patch(route('admin.members.update', $member), [
            'baptism_name' => 'Hacked',
        ])->assertForbidden();
    }
}

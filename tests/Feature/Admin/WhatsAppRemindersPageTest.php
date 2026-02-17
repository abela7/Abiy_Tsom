<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppRemindersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reminders_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.reminders'));

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.reminders')
            ->assertViewHas(['totalOptedIn', 'byTime', 'members']);
    }

    public function test_reminders_page_shows_opted_in_members(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        Member::create([
            'baptism_name' => 'Test Member',
            'token' => 'abc123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.reminders'));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('totalOptedIn'));
    }

    public function test_admin_can_update_member_reminder(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.whatsapp.reminders.update', $member), [
            'baptism_name' => 'Updated Name',
            'whatsapp_phone' => '+251912345678',
            'whatsapp_reminder_time' => '14:30',
        ]);

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $member->refresh();
        $this->assertEquals('Updated Name', $member->baptism_name);
        $this->assertEquals('+251912345678', $member->whatsapp_phone);
        $this->assertEquals('14:30:00', $member->whatsapp_reminder_time);
    }

    public function test_admin_can_disable_member_reminder(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.reminders.disable', $member));

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $member->refresh();
        $this->assertFalse($member->whatsapp_reminder_enabled);
        $this->assertNull($member->whatsapp_phone);
        $this->assertNull($member->whatsapp_reminder_time);
    }

    public function test_admin_can_delete_member(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $id = $member->id;

        $response = $this->actingAs($admin)->delete(route('admin.whatsapp.reminders.destroy', $member));

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $this->assertNull(Member::find($id));
    }
}

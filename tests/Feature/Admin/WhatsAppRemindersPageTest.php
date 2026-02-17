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
}

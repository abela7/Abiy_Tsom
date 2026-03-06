<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberReminderOpen;
use App\Models\User;
use App\Models\WeeklyTheme;
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
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.reminders'));

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.reminders')
            ->assertViewHas([
                'totalOptedIn',
                'totalPending',
                'totalOpenedMembers',
                'activeReminderMembers7d',
                'byTime',
                'members',
            ]);
    }

    public function test_reminders_page_shows_opted_in_members(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        Member::create([
            'baptism_name' => 'Test Member',
            'token' => 'abc123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
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
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.whatsapp.reminders.update', $member), [
            'baptism_name' => 'Updated Name',
            'whatsapp_phone' => '07123456789',
            'whatsapp_reminder_time' => '14:30',
        ]);

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $member->refresh();
        $this->assertEquals('Updated Name', $member->baptism_name);
        $this->assertEquals('+447123456789', $member->whatsapp_phone);
        $this->assertEquals('14:30:00', $member->whatsapp_reminder_time);
    }

    public function test_admin_can_disable_member_reminder(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.reminders.disable', $member));

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $member->refresh();
        $this->assertFalse($member->whatsapp_reminder_enabled);
        $this->assertNull($member->whatsapp_phone);
        $this->assertNull($member->whatsapp_reminder_time);
        $this->assertSame('none', $member->whatsapp_confirmation_status);
    }

    public function test_admin_can_delete_member(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Test',
            'token' => 'token123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $id = $member->id;

        $response = $this->actingAs($admin)->delete(route('admin.whatsapp.reminders.destroy', $member));

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $this->assertNull(Member::find($id));
    }

    public function test_pending_members_appear_in_reminders_list(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        Member::create([
            'baptism_name' => 'Pending Member',
            'token' => 'pending123',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
            'whatsapp_phone' => '+447700900999',
            'whatsapp_reminder_time' => '08:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.reminders'));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('totalPending'));
        $members = $response->viewData('members');
        $this->assertCount(1, $members);
        $this->assertSame('pending', $members->first()->whatsapp_confirmation_status);
    }

    public function test_admin_can_manually_confirm_pending_member(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Pending',
            'token' => 'pending456',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
            'whatsapp_phone' => '+447700900888',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.reminders.confirm', $member));

        $response->assertRedirect(route('admin.whatsapp.reminders'));
        $member->refresh();
        $this->assertTrue($member->whatsapp_reminder_enabled);
        $this->assertSame('confirmed', $member->whatsapp_confirmation_status);
    }

    public function test_reminders_page_shows_tracked_link_activity(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Active Member',
            'token' => 'active123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_time' => '07:30:00',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 1,
            'name_en' => 'Week 1',
            'meaning' => 'Meaning',
            'week_start_date' => '2026-02-15',
            'week_end_date' => '2026-02-21',
        ]);

        $day = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 1,
            'date' => now()->toDateString(),
            'day_title_en' => 'Tracked Day',
            'is_published' => true,
        ]);

        MemberReminderOpen::create([
            'member_id' => $member->id,
            'daily_content_id' => $day->id,
            'first_opened_at' => now()->subDay(),
            'last_opened_at' => now()->subHours(2),
            'last_authenticated_open_at' => now()->subHours(2),
            'open_count' => 2,
            'authenticated_open_count' => 1,
            'public_open_count' => 1,
            'last_open_state' => 'authenticated_session',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.reminders'));

        $response->assertOk();
        $this->assertEquals(1, $response->viewData('totalOpenedMembers'));
        $this->assertEquals(1, $response->viewData('activeReminderMembers7d'));
    }

    public function test_admin_can_view_member_engagement_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Tracked Member',
            'token' => 'tracked123',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900222',
            'whatsapp_reminder_time' => '18:00:00',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 1,
            'name_en' => 'Week 1',
            'meaning' => 'Meaning',
            'week_start_date' => '2026-02-15',
            'week_end_date' => '2026-02-21',
        ]);

        $day = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 2,
            'date' => '2026-02-16',
            'day_title_en' => 'Tracked Day',
            'is_published' => true,
        ]);

        MemberReminderOpen::create([
            'member_id' => $member->id,
            'daily_content_id' => $day->id,
            'first_opened_at' => now()->subDay(),
            'last_opened_at' => now()->subHours(1),
            'last_authenticated_open_at' => now()->subHours(1),
            'open_count' => 1,
            'authenticated_open_count' => 1,
            'public_open_count' => 0,
            'last_open_state' => 'authenticated_session',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.whatsapp.reminders.engagement', $member));

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.reminder-engagement')
            ->assertSee('Tracked Member')
            ->assertSee('Reminder Engagement');
    }
}

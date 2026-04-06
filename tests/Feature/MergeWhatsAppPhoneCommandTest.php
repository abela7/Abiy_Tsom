<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatInvitationDelivery;
use App\Models\MemberHimamatPreference;
use App\Models\MemberHimamatReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeWhatsAppPhoneCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_command_lists_exact_duplicate_whatsapp_numbers(): void
    {
        Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'am',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
        ]);

        Member::create([
            'baptism_name' => 'Abel Duplicate',
            'token' => str_repeat('b', 64),
            'locale' => 'am',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
        ]);

        $this->artisan('members:audit-whatsapp-duplicates')
            ->assertExitCode(0);
    }

    public function test_merge_command_requires_keep_id_for_apply(): void
    {
        Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('c', 64),
            'locale' => 'am',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900222',
        ]);

        Member::create([
            'baptism_name' => 'Abel Duplicate',
            'token' => str_repeat('d', 64),
            'locale' => 'am',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900222',
        ]);

        $this->artisan('members:merge-whatsapp-phone', [
            'phone' => '+447700900222',
            '--apply' => true,
        ])
            ->assertExitCode(1);
    }

    public function test_merge_command_consolidates_whatsapp_identity_and_related_himamat_rows(): void
    {
        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'ሰኞ',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'third',
            'slot_order' => 2,
            'scheduled_time_london' => '09:00:00',
            'slot_header_en' => 'Third Hour',
            'slot_header_am' => '3 ሰዓት',
            'reminder_header_en' => 'Third Hour Reminder',
            'reminder_header_am' => 'የ3 ሰዓት ማሳሰቢያ',
            'is_published' => true,
        ]);

        $keeper = Member::create([
            'baptism_name' => 'Keeper',
            'token' => str_repeat('e', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_phone' => '+447700900333',
            'whatsapp_confirmation_status' => 'pending',
        ]);

        $duplicate = Member::create([
            'baptism_name' => 'Duplicate',
            'token' => str_repeat('f', 64),
            'locale' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_phone' => '+447700900333',
            'whatsapp_reminder_time' => '09:00:00',
            'whatsapp_last_sent_date' => '2026-04-05',
            'whatsapp_language' => 'am',
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_confirmation_requested_at' => '2026-04-01 08:00:00',
            'whatsapp_confirmation_responded_at' => '2026-04-01 08:03:00',
            'phone_verified_at' => '2026-04-01 08:03:00',
        ]);

        MemberHimamatPreference::create([
            'member_id' => $duplicate->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => false,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        MemberHimamatReminderDelivery::create([
            'member_id' => $duplicate->id,
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'due_at_london' => CarbonImmutable::parse('2026-04-06 09:00:00', 'Europe/London'),
            'status' => 'sent',
            'attempt_count' => 1,
            'delivered_at' => CarbonImmutable::parse('2026-04-06 09:00:30', 'Europe/London'),
        ]);

        MemberHimamatInvitationDelivery::create([
            'member_id' => $duplicate->id,
            'campaign_key' => 'holy-monday-launch-2026',
            'channel' => 'whatsapp',
            'destination_phone' => '+447700900333',
            'status' => 'sent',
            'attempt_count' => 1,
            'open_count' => 1,
            'delivered_at' => CarbonImmutable::parse('2026-04-05 20:00:00', 'Europe/London'),
            'first_opened_at' => CarbonImmutable::parse('2026-04-05 20:05:00', 'Europe/London'),
            'last_opened_at' => CarbonImmutable::parse('2026-04-05 20:05:00', 'Europe/London'),
        ]);

        $this->artisan('members:merge-whatsapp-phone', [
            'phone' => '+447700900333',
            '--keep-id' => (string) $keeper->id,
            '--apply' => true,
        ])
            ->assertExitCode(0);

        $keeper->refresh();
        $duplicate->refresh();

        $this->assertTrue($keeper->whatsapp_reminder_enabled);
        $this->assertSame('+447700900333', $keeper->whatsapp_phone);
        $this->assertSame('confirmed', $keeper->whatsapp_confirmation_status);
        $this->assertSame('am', $keeper->whatsapp_language);
        $this->assertSame('09:00:00', $keeper->whatsapp_reminder_time);
        $this->assertSame('2026-04-05', $keeper->whatsapp_last_sent_date?->toDateString());
        $this->assertNotNull($keeper->phone_verified_at);

        $this->assertNull($duplicate->whatsapp_phone);
        $this->assertFalse($duplicate->whatsapp_reminder_enabled);
        $this->assertNull($duplicate->whatsapp_reminder_time);
        $this->assertNull($duplicate->whatsapp_last_sent_date);
        $this->assertNull($duplicate->whatsapp_language);
        $this->assertSame('none', $duplicate->whatsapp_confirmation_status);
        $this->assertNull($duplicate->phone_verified_at);

        $this->assertDatabaseHas('member_himamat_preferences', [
            'member_id' => $keeper->id,
            'lent_season_id' => $season->id,
            'third_enabled' => false,
        ]);

        $this->assertDatabaseHas('member_himamat_reminder_deliveries', [
            'member_id' => $keeper->id,
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('member_himamat_invitation_deliveries', [
            'member_id' => $keeper->id,
            'campaign_key' => 'holy-monday-launch-2026',
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);
    }
}

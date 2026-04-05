<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HimamatDay;
use App\Models\HimamatReminderDispatch;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatPreference;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendHimamatRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_command_catches_up_due_himamat_reminder_once_and_records_delivery(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');
        config()->set('himamat.reminders.dispatch_grace_minutes', 15);

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-10 15:04:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'good-friday',
            'sort_order' => 6,
            'date' => '2026-04-10',
            'title_en' => 'Good Friday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'ninth',
            'slot_order' => 4,
            'scheduled_time_london' => '15:00:00',
            'slot_header_en' => 'Ninth Hour',
            'reminder_header_en' => 'The hour our Lord gave Himself for the life of the world.',
            'spiritual_significance_en' => 'Stand in silence before the Cross.',
            'reading_reference_en' => 'John 19:28-30',
            'short_prayer_en' => 'Lord Jesus Christ, have mercy on us.',
            'prostration_count' => 12,
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        MemberHimamatPreference::create([
            'member_id' => $member->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/contacts/check*' => Http::response([
                'status' => 'valid',
            ]),
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('himamat:send-whatsapp-reminders')
            ->assertExitCode(0);

        $this->artisan('himamat:send-whatsapp-reminders')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($member, $day): bool {
            $body = (string) $request['body'];
            $expectedPath = '/himamat/access/'.$member->token.'/'.$day->slug.'/ninth';

            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && str_contains($body, 'The hour our Lord gave Himself for the life of the world.')
                && str_contains($body, 'John 19:28-30')
                && str_contains($body, $expectedPath);
        });

        $this->assertDatabaseHas('member_himamat_reminder_deliveries', [
            'member_id' => $member->id,
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('himamat_reminder_dispatches', [
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'status' => HimamatReminderDispatch::STATUS_COMPLETED,
            'recipient_count' => 1,
            'sent_count' => 1,
            'failed_count' => 0,
        ]);
    }

    public function test_command_marks_slot_as_missed_once_it_is_past_the_catch_up_window(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('himamat.reminders.dispatch_grace_minutes', 10);

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-10 15:45:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'good-friday',
            'sort_order' => 6,
            'date' => '2026-04-10',
            'title_en' => 'Good Friday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'ninth',
            'slot_order' => 4,
            'scheduled_time_london' => '15:00:00',
            'slot_header_en' => 'Ninth Hour',
            'reminder_header_en' => 'The hour our Lord gave Himself for the life of the world.',
            'reading_reference_en' => 'John 19:28-30',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        MemberHimamatPreference::create([
            'member_id' => $member->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        Http::fake();

        $this->artisan('himamat:send-whatsapp-reminders')
            ->assertExitCode(0);

        Http::assertNothingSent();

        $this->assertDatabaseHas('himamat_reminder_dispatches', [
            'himamat_slot_id' => $slot->id,
            'channel' => 'whatsapp',
            'status' => HimamatReminderDispatch::STATUS_MISSED,
        ]);

        $this->assertDatabaseCount('member_himamat_reminder_deliveries', 0);
    }

    public function test_sample_reminder_command_refuses_invalid_recipient_reported_by_ultramsg(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => true,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'reminder_header_en' => 'Holy Monday has begun.',
            'reading_reference_en' => 'Mark 11:12-26',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel Teklu',
            'token' => str_repeat('z', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/contacts/check*' => Http::response([
                'status' => 'invalid',
            ]),
        ]);

        $this->artisan(sprintf(
            'himamat:send-sample-reminder --member-id=%d --sample-phone=+447700900999 --day=holy-monday --slot=intro',
            $member->id
        ))->assertExitCode(1);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return str_starts_with($request->url(), 'https://api.ultramsg.com/instance999/contacts/check');
        });
    }

    public function test_sample_reminder_command_refuses_unpublished_day_or_slot_before_sending(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'reminder_header_en' => 'Holy Monday has begun.',
            'reading_reference_en' => 'Mark 11:12-26',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel Teklu',
            'token' => str_repeat('z', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        Http::fake();

        $this->artisan(sprintf(
            'himamat:send-sample-reminder --member-id=%d --sample-phone=+447700900999 --day=holy-monday --slot=intro',
            $member->id
        ))->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_sample_reminder_command_sends_only_to_the_requested_sample_phone(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => true,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'reminder_header_en' => 'Holy Monday has begun.',
            'reading_reference_en' => 'Mark 11:12-26',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel Teklu',
            'token' => str_repeat('z', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/contacts/check*' => Http::response([
                'status' => 'valid',
            ]),
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan(sprintf(
            'himamat:send-sample-reminder --member-id=%d --sample-phone=+447700900999 --day=holy-monday --slot=intro',
            $member->id
        ))->assertExitCode(0);

        Http::assertSentCount(2);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($member, $day): bool {
            if ($request->url() !== 'https://api.ultramsg.com/instance999/messages/chat') {
                return false;
            }

            $body = (string) $request['body'];

            return $request['to'] === '+447700900999'
                && str_contains($body, 'Holy Monday has begun.')
                && str_contains($body, 'Mark 11:12-26')
                && str_contains($body, '/himamat/access/'.$member->token.'/'.$day->slug.'/intro');
        });

        $this->assertDatabaseCount('member_himamat_reminder_deliveries', 0);
    }

    public function test_command_can_be_safely_limited_to_one_member_in_test_mode(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');
        config()->set('himamat.reminders.dispatch_grace_minutes', 15);

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-10 15:04:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'good-friday',
            'sort_order' => 6,
            'date' => '2026-04-10',
            'title_en' => 'Good Friday',
            'is_published' => true,
        ]);

        $slot = HimamatSlot::create([
            'himamat_day_id' => $day->id,
            'slot_key' => 'ninth',
            'slot_order' => 4,
            'scheduled_time_london' => '15:00:00',
            'slot_header_en' => 'Ninth Hour',
            'reminder_header_en' => 'The hour our Lord gave Himself for the life of the world.',
            'reading_reference_en' => 'John 19:28-30',
            'is_published' => true,
        ]);

        $targetMember = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        $otherMember = Member::create([
            'baptism_name' => 'Bethlehem',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900222',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);

        MemberHimamatPreference::create([
            'member_id' => $targetMember->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        MemberHimamatPreference::create([
            'member_id' => $otherMember->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        config()->set('himamat.reminders.test_mode_member_id', $targetMember->id);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('himamat:send-whatsapp-reminders')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($targetMember): bool {
            return $request['to'] === $targetMember->whatsapp_phone;
        });

        $this->assertDatabaseHas('member_himamat_reminder_deliveries', [
            'member_id' => $targetMember->id,
            'himamat_slot_id' => $slot->id,
            'status' => 'sent',
        ]);

        $this->assertDatabaseMissing('member_himamat_reminder_deliveries', [
            'member_id' => $otherMember->id,
            'himamat_slot_id' => $slot->id,
        ]);

        $this->assertDatabaseHas('himamat_reminder_dispatches', [
            'himamat_slot_id' => $slot->id,
            'recipient_count' => 1,
            'sent_count' => 1,
        ]);
    }
}

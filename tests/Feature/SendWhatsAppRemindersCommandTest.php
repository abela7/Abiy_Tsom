<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatPreference;
use App\Models\WeeklyTheme;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendWhatsAppRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_command_sends_due_reminders_and_marks_member_as_sent(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-02-17 08:30:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 1,
            'name_en' => 'Zewerede',
            'meaning' => 'He who descended from above',
            'week_start_date' => '2026-02-16',
            'week_end_date' => '2026-02-22',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 2,
            'date' => '2026-02-17',
            'is_published' => true,
        ]);

        $dueMember = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_time' => '08:30:00',
        ]);

        Member::create([
            'baptism_name' => 'Already Sent',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900112',
            'whatsapp_reminder_time' => '08:30:00',
            'whatsapp_last_sent_date' => '2026-02-17',
        ]);

        Member::create([
            'baptism_name' => 'Different Time',
            'token' => str_repeat('c', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900113',
            'whatsapp_reminder_time' => '09:00:00',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('reminders:send-whatsapp')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($daily, $dueMember): bool {
            $body = (string) $request['body'];
            $expectedUrl = '/m/'.$dueMember->token.'/day/'.$daily->day_number.'-'.$daily->id;

            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && $request['token'] === 'token-123'
                && $body !== ''
                && str_contains(strtolower($body), 'day '.$daily->day_number)
                && str_contains($body, $expectedUrl);
        });

        $this->assertDatabaseHas('members', [
            'id' => $dueMember->id,
            'whatsapp_last_sent_date' => '2026-02-17 00:00:00',
        ]);
    }

    public function test_command_sends_himamat_intro_reminder_at_seven_am_to_intro_enabled_members(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-06 07:00:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'meaning' => 'Passion Week',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 50,
            'date' => '2026-04-06',
            'is_published' => true,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday - Cleansing of the Temple & The Cursing of the Fig Tree',
            'title_am' => 'ሰኞ - አንጽሖተ ቤተመቅደስ እና መርገመ በለስ',
            'spiritual_meaning_en' => 'Holy Monday meaning.',
            'spiritual_meaning_am' => 'ይህ ዕለት አንጽሆተ ቤተመቅደስና መርገመ በለስ የተፈጸመበት ዕለት ነው፡፡',
            'is_published' => true,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'slot_header_am' => 'የዕለቱ መክፈቻ',
            'reminder_header_en' => 'Holy Monday - Cleansing of the Temple & The Cursing of the Fig Tree',
            'reminder_header_am' => 'ሰኞ - አንጽሖተ ቤተመቅደስ እና መርገመ በለስ',
            'reminder_content_en' => 'Holy Monday intro reminder content.',
            'reminder_content_am' => 'á‹­áˆ… á‹•áˆˆá‰µ áŠ áŠ•áŒ½áˆ†á‰° á‰¤á‰°áˆ˜á‰…á‹°áˆµáŠ“ áˆ˜áˆ­áŒˆáˆ˜ á‰ áˆˆáˆµ á‹¨á‰°áˆáŒ¸áˆ˜á‰ á‰µ á‹•áˆˆá‰µ áŠá‹á¡á¡',
            'reading_reference_en' => null,
            'reading_reference_am' => null,
            'reading_text_en' => null,
            'reading_text_am' => null,
            'is_published' => true,
        ]);

        $defaultIncludedMember = Member::create([
            'baptism_name' => 'David',
            'token' => str_repeat('d', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_time' => null,
        ]);

        $disabledMember = Member::create([
            'baptism_name' => 'Disabled Intro',
            'token' => str_repeat('e', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900112',
            'whatsapp_reminder_time' => null,
        ]);

        MemberHimamatPreference::create([
            'member_id' => $disabledMember->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => false,
            'third_enabled' => true,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('reminders:send-whatsapp')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $recorded = Http::recorded();
        $request = $recorded->first()[0];
        $body = (string) $request['body'];
        $expectedUrl = '/m/'.$defaultIncludedMember->token.'/day/'.$daily->day_number.'-'.$daily->id;

        $this->assertSame('+447700900111', $request['to']);
        $this->assertStringContainsString('ሰኞ - አንጽሖተ ቤተመቅደስ እና መርገመ በለስ', $body);
        $this->assertStringContainsString('ይህ ዕለት አንጽሆተ ቤተመቅደስና መርገመ በለስ የተፈጸመበት ዕለት ነው፡፡', $body);
        $this->assertStringContainsString($expectedUrl, $body);

        $this->assertDatabaseHas('members', [
            'id' => $defaultIncludedMember->id,
            'whatsapp_last_sent_date' => '2026-04-06 00:00:00',
        ]);

        $this->assertDatabaseMissing('members', [
            'id' => $disabledMember->id,
            'whatsapp_last_sent_date' => '2026-04-06 00:00:00',
        ]);
    }

    public function test_command_skips_old_time_based_daily_reminders_on_himamat_days_outside_seven_am(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-06 08:30:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'meaning' => 'Passion Week',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 50,
            'date' => '2026-04-06',
            'is_published' => true,
        ]);

        HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'ሰኞ',
            'spiritual_meaning_en' => 'Meaning',
            'spiritual_meaning_am' => 'ማብራሪያ',
            'is_published' => true,
        ]);

        Member::create([
            'baptism_name' => 'Old Daily Time',
            'token' => str_repeat('f', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900113',
            'whatsapp_reminder_time' => '08:30:00',
        ]);

        Http::fake();

        $this->artisan('reminders:send-whatsapp')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    public function test_command_sends_himamat_slot_reminder_at_nine_am_to_third_hour_members(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-06 09:00:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'meaning' => 'Passion Week',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 50,
            'date' => '2026-04-06',
            'is_published' => true,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'ሰኞ',
            'spiritual_meaning_en' => 'Holy Monday meaning.',
            'spiritual_meaning_am' => 'ሰኞ ማብራሪያ',
            'is_published' => true,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'slot_header_am' => 'የዕለቱ መክፈቻ',
            'reminder_header_en' => 'Holy Monday Intro',
            'reminder_header_am' => 'ሰኞ መክፈቻ',
            'is_published' => true,
        ]);

        $thirdSlot = HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'third',
            'slot_order' => 2,
            'scheduled_time_london' => '09:00:00',
            'slot_header_en' => 'Monday morning 3 oclock Gospel reading',
            'slot_header_am' => 'ሰኞ ጠዋት 3 የሚነበበው የዕለቱ ወንጌል',
            'reminder_header_en' => 'Third Hour Reminder Header',
            'reminder_header_am' => 'የ3 ሰዓት ማሳሰቢያ ርዕስ',
            'reminder_content_en' => 'Third Hour Reminder Content',
            'reminder_content_am' => "በዚህ ሰዓት ጌታችን ወደ በለስ ሄደ።\n\nየበረታ ስግደት፣ እንዲሁም ጸሎት ያድርግ።",
            'is_published' => true,
        ]);

        $includedMember = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('g', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900114',
            'whatsapp_reminder_time' => null,
        ]);

        $disabledMember = Member::create([
            'baptism_name' => 'Third Off',
            'token' => str_repeat('h', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900115',
            'whatsapp_reminder_time' => null,
        ]);

        MemberHimamatPreference::create([
            'member_id' => $disabledMember->id,
            'lent_season_id' => $season->id,
            'enabled' => true,
            'intro_enabled' => true,
            'third_enabled' => false,
            'sixth_enabled' => true,
            'ninth_enabled' => true,
            'eleventh_enabled' => true,
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('reminders:send-whatsapp')
            ->assertExitCode(0);

        Http::assertSentCount(1);
        $recorded = Http::recorded();
        $request = $recorded->first()[0];
        $body = (string) $request['body'];
        $expectedUrl = '/m/'.$includedMember->token.'/day/'.$daily->day_number.'-'.$daily->id.'#himamat-slot-third';

        $this->assertSame('+447700900114', $request['to']);
        $this->assertStringContainsString('ሰኞ ጠዋት 3 የሚነበበው የዕለቱ ወንጌል', $body);
        $this->assertStringContainsString('በዚህ ሰዓት ጌታችን ወደ በለስ ሄደ።', $body);
        $this->assertStringContainsString('በዚህ ሰዓት የሚነበበውን የወንጌል ክፍል ለማግኘት', $body);
        $this->assertStringContainsString($expectedUrl, $body);

        $this->assertDatabaseHas('member_himamat_reminder_deliveries', [
            'member_id' => $includedMember->id,
            'himamat_slot_id' => $thirdSlot->id,
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);

        $this->assertDatabaseMissing('member_himamat_reminder_deliveries', [
            'member_id' => $disabledMember->id,
            'himamat_slot_id' => $thirdSlot->id,
            'channel' => 'whatsapp',
        ]);
    }

    public function test_command_skips_himamat_slot_when_required_reminder_content_is_blank(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-06 12:00:00', 'Europe/London')
        );

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Hosanna',
            'meaning' => 'Passion Week',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 50,
            'date' => '2026-04-06',
            'is_published' => true,
        ]);

        $himamatDay = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 2,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'title_am' => 'ሰኞ',
            'spiritual_meaning_en' => 'Holy Monday meaning.',
            'spiritual_meaning_am' => 'ሰኞ ማብራሪያ',
            'is_published' => true,
        ]);

        HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'intro',
            'slot_order' => 1,
            'scheduled_time_london' => '07:00:00',
            'slot_header_en' => 'Daily Introduction',
            'slot_header_am' => 'የዕለቱ መክፈቻ',
            'reminder_header_en' => 'Holy Monday Intro',
            'reminder_header_am' => 'ሰኞ መክፈቻ',
            'is_published' => true,
        ]);

        $sixthSlot = HimamatSlot::create([
            'himamat_day_id' => $himamatDay->id,
            'slot_key' => 'sixth',
            'slot_order' => 3,
            'scheduled_time_london' => '12:00:00',
            'slot_header_en' => 'Monday 6 oclock Gospel reading',
            'slot_header_am' => 'ሰኞ ቀትር 6 ሰዓት የሚነበበው የዕለቱ ወንጌል',
            'reminder_header_en' => 'Sixth Hour Reminder Header',
            'reminder_header_am' => 'የ6 ሰዓት ማሳሰቢያ ርዕስ',
            'reminder_content_en' => '',
            'reminder_content_am' => '',
            'is_published' => true,
        ]);

        Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('i', 64),
            'locale' => 'am',
            'whatsapp_language' => 'am',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_phone' => '+447700900116',
            'whatsapp_reminder_time' => null,
        ]);

        Http::fake();

        $this->artisan('reminders:send-whatsapp')
            ->assertExitCode(0);

        Http::assertNothingSent();

        $this->assertDatabaseMissing('member_himamat_reminder_deliveries', [
            'himamat_slot_id' => $sixthSlot->id,
            'channel' => 'whatsapp',
        ]);
    }
}

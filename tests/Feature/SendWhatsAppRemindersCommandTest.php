<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
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
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_time' => '08:30:00',
        ]);

        Member::create([
            'baptism_name' => 'Already Sent',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_reminder_enabled' => true,
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
            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && $request['token'] === 'token-123'
                && is_string($request['body'])
                && str_contains($request['body'], 'Day '.$daily->day_number)
                && str_contains($request['body'], '/member/day/'.$daily->id)
                && str_contains($request['body'], 'token='.$dueMember->token);
        });

        $this->assertDatabaseHas('members', [
            'id' => $dueMember->id,
            'whatsapp_last_sent_date' => '2026-02-17 00:00:00',
        ]);
    }
}

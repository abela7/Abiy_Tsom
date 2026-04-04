<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HimamatDay;
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

    public function test_command_sends_due_himamat_reminder_once_and_records_delivery(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-04-10 15:00:00', 'Europe/London')
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
    }
}

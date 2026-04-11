<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendBulkWhatsAppMessageJob;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\WeeklyTheme;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendBulkWhatsAppMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_message_job_renders_and_sends_without_touching_last_sent_date(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'whatsapp_phone' => '+447700900111',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'en',
            'whatsapp_last_sent_date' => '2026-02-17',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $job = new SendBulkWhatsAppMessageJob(
            $member->id,
            'Hello :name, English bulk message.',
            'ሰላም :name, ይህ የአማርኛ መልእክት ነው።'
        );

        $job->handle(app(UltraMsgService::class), app(WhatsAppTemplateService::class));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $body = (string) $request['body'];

            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && $request['token'] === 'token-123'
                && str_contains($body, 'Hello Abel, English bulk message.');
        });

        $member->refresh();

        $this->assertSame('2026-02-17', $member->whatsapp_last_sent_date?->toDateString());
    }

    public function test_bulk_message_job_skips_ineligible_member(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        $member = Member::create([
            'baptism_name' => 'Pending',
            'token' => str_repeat('b', 64),
            'whatsapp_phone' => '+447700900112',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
        ]);

        Http::fake();

        $job = new SendBulkWhatsAppMessageJob(
            $member->id,
            'Hello :name',
            'ሰላም :name'
        );

        $job->handle(app(UltraMsgService::class), app(WhatsAppTemplateService::class));

        Http::assertNothingSent();
    }

    public function test_bulk_message_job_can_send_member_specific_fasika_link(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');
        config()->set('app.easter_date', '2026-04-12 03:00');
        config()->set('app.easter_timezone', 'Europe/London');

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-16',
            'end_date' => '2026-04-12',
            'total_days' => 56,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 8,
            'name_en' => 'Fasika',
            'meaning' => 'Resurrection',
            'week_start_date' => '2026-04-06',
            'week_end_date' => '2026-04-12',
        ]);

        $daily = DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 56,
            'date' => '2026-04-12',
            'is_published' => true,
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('f', 64),
            'whatsapp_phone' => '+447700900114',
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'am',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $job = new SendBulkWhatsAppMessageJob(
            $member->id,
            'Hello :name :fasika_url',
            "Selam :name\n\n:fasika_url"
        );

        $job->handle(app(UltraMsgService::class), app(WhatsAppTemplateService::class));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($daily): bool {
            $body = (string) $request['body'];

            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900114'
                && str_contains($body, '/m/'.str_repeat('f', 64).'/day/56-'.$daily->id);
        });
    }
}

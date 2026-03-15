<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendBulkWhatsAppMessageJob;
use App\Models\Member;
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
            'Important update',
            'Please read this today.',
            'https://example.com/bulk'
        );

        $job->handle(app(UltraMsgService::class), app(WhatsAppTemplateService::class));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $body = (string) $request['body'];

            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && $request['token'] === 'token-123'
                && str_contains($body, 'Hello Abel')
                && str_contains($body, 'Important update')
                && str_contains($body, 'Please read this today.')
                && str_contains($body, 'https://example.com/bulk');
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
            'Important update',
            'Please read this today.',
            'https://example.com/bulk'
        );

        $job->handle(app(UltraMsgService::class), app(WhatsAppTemplateService::class));

        Http::assertNothingSent();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejected_when_secret_is_wrong(): void
    {
        config()->set('services.ultramsg.webhook_secret', 'correct-secret');

        $response = $this->postJson(route('webhooks.ultramsg'), [
            'from' => '447700900123@c.us',
            'body' => 'YES',
            'fromMe' => false,
        ], ['X-Webhook-Secret' => 'wrong-secret']);

        $response->assertStatus(401)
            ->assertJson(['error' => 'unauthorized']);
    }

    public function test_webhook_accepted_when_secret_matches(): void
    {
        config()->set('services.ultramsg.webhook_secret', 'correct-secret');

        $response = $this->postJson(route('webhooks.ultramsg'), [
            'from' => '447700900123@c.us',
            'body' => 'YES',
            'fromMe' => false,
        ], ['X-Webhook-Secret' => 'correct-secret']);

        $response->assertOk();
    }

    public function test_webhook_allowed_when_no_secret_configured(): void
    {
        config()->set('services.ultramsg.webhook_secret', null);

        $response = $this->postJson(route('webhooks.ultramsg'), [
            'from' => '447700900123@c.us',
            'body' => 'HELLO',
            'fromMe' => false,
        ]);

        $response->assertOk();
    }

    public function test_yes_reply_confirms_pending_whatsapp_reminder(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
            ]),
        ]);

        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('a', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_reminder_time' => '08:30:00',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
            'whatsapp_confirmation_requested_at' => now(),
        ]);

        $response = $this->postJson(route('webhooks.ultramsg'), [
            'from' => '447700900123@c.us',
            'body' => 'YES',
            'fromMe' => false,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'confirmed',
            ]);

        $member->refresh();
        $this->assertTrue((bool) $member->whatsapp_reminder_enabled);
        $this->assertSame('confirmed', $member->whatsapp_confirmation_status);
        $this->assertNotNull($member->whatsapp_confirmation_responded_at);

        Http::assertSentCount(2);
        $personalUrl = str_replace('http://', 'https://', $member->personalUrl('/home'));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($personalUrl): bool {
            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900123'
                && $request['token'] === 'token-123'
                && is_string($request['body'])
                && ! str_contains($request['body'], '/member/access/')
                && str_contains($request['body'], $personalUrl)
                && str_contains($request['body'], 'do not share this link');
        });
    }

    public function test_no_reply_rejects_pending_whatsapp_reminder(): void
    {
        $member = Member::create([
            'baptism_name' => 'Abel',
            'token' => str_repeat('b', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900124',
            'whatsapp_reminder_time' => '08:30:00',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
            'whatsapp_confirmation_requested_at' => now(),
        ]);

        $response = $this->postJson(route('webhooks.ultramsg'), [
            'from' => '447700900124@c.us',
            'body' => 'NO',
            'fromMe' => false,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'action' => 'rejected',
            ]);

        $member->refresh();
        $this->assertFalse((bool) $member->whatsapp_reminder_enabled);
        $this->assertSame('rejected', $member->whatsapp_confirmation_status);
        $this->assertNotNull($member->whatsapp_confirmation_responded_at);
    }
}

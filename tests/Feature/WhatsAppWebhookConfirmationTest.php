<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppWebhookConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_yes_reply_confirms_pending_whatsapp_reminder(): void
    {
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

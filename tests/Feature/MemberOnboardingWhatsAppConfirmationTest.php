<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberOnboardingWhatsAppConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_starts_whatsapp_confirmation_as_pending(): void
    {
        config([
            'services.ultramsg.instance_id' => 'test-instance',
            'services.ultramsg.token' => 'test-token',
        ]);

        // Fake a successful UltraMsg API response.
        Http::fake([
            'api.ultramsg.com/*' => Http::response(['sent' => 'true'], 200),
        ]);

        $response = $this->postJson('/register', [
            'baptism_name' => 'Martha',
            'phone' => '+447123456789',
            'locale' => 'en',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'verification_pending' => true,
                'channel' => 'whatsapp',
                'code_sent' => true,
            ]);

        $this->assertDatabaseHas('members', [
            'baptism_name' => 'Martha',
            'whatsapp_phone' => '+447123456789',
            'whatsapp_reminder_enabled' => 0,
            'whatsapp_confirmation_status' => 'pending',
        ]);
    }

    public function test_registration_fails_loudly_when_prompt_not_sent(): void
    {
        config([
            'services.ultramsg.instance_id' => 'test-instance',
            'services.ultramsg.token' => 'test-token',
        ]);

        // Fake a failed UltraMsg API response.
        Http::fake([
            'api.ultramsg.com/*' => Http::response(['sent' => 'false'], 200),
        ]);

        $response = $this->postJson('/register', [
            'baptism_name' => 'Martha',
            'phone' => '+447123456789',
            'locale' => 'en',
        ]);

        $response->assertStatus(502)
            ->assertJson([
                'success' => false,
            ]);

        // Member should be cleaned up — no orphaned pending records.
        $this->assertDatabaseMissing('members', [
            'whatsapp_phone' => '+447123456789',
        ]);
    }
}

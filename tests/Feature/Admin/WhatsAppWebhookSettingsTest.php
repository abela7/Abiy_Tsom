<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_current_webhook_settings(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        config()->set('services.ultramsg.instance_id', 'test123');
        config()->set('services.ultramsg.token', 'test_token');

        Http::fake([
            '*/instance/settings*' => Http::response([
                'webhook_url' => 'https://example.com/webhook',
                'webhook_message_received' => 'on',
                'webhook_message_create' => 'off',
                'webhook_message_ack' => 'on',
                'webhook_message_download_media' => 'off',
                'sendDelay' => 2,
                'sendDelayMax' => 20,
            ], 200),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.settings'));

        $response->assertOk();
        $response->assertViewHas('currentSettings');
    }

    public function test_admin_can_update_webhook_settings(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        config()->set('services.ultramsg.instance_id', 'test123');
        config()->set('services.ultramsg.token', 'test_token');

        Http::fake([
            '*/instance/settings' => Http::response(['success' => true], 200),
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.whatsapp.webhook'), [
            'webhook_url' => 'https://example.com/webhook',
            'webhook_message_received' => true,
            'webhook_message_create' => false,
            'webhook_message_ack' => true,
            'webhook_message_download_media' => false,
            'sendDelay' => 2,
            'sendDelayMax' => 20,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ultramsg.com/test123/instance/settings'
                && $request['webhook_url'] === 'https://example.com/webhook'
                && $request['webhook_message_received'] === 'true'
                && $request['webhook_message_create'] === 'false'
                && $request['webhook_message_ack'] === 'true'
                && $request['webhook_message_download_media'] === 'false'
                && $request['sendDelay'] == 2
                && $request['sendDelayMax'] == 20;
        });
    }

    public function test_webhook_update_requires_valid_url(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        config()->set('services.ultramsg.instance_id', 'test123');
        config()->set('services.ultramsg.token', 'test_token');

        $response = $this->actingAs($admin)->postJson(route('admin.whatsapp.webhook'), [
            'webhook_url' => 'not-a-valid-url',
            'webhook_message_received' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['webhook_url']);
    }

    public function test_webhook_update_fails_when_credentials_not_configured(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        config()->set('services.ultramsg.instance_id', '');
        config()->set('services.ultramsg.token', '');

        $response = $this->actingAs($admin)->postJson(route('admin.whatsapp.webhook'), [
            'webhook_url' => 'https://example.com/webhook',
            'webhook_message_received' => true,
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
    }
}

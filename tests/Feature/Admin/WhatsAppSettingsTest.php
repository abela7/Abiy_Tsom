<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_whatsapp_settings_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => null,
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/whatsapp');

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.index');
    }

    public function test_admin_can_test_ultramsg_connection(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => null,
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 123,
            ]),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/whatsapp/test', [
            'instance_id' => 'instance999',
            'token' => 'test-token-123',
            'test_phone' => '+447700900456',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900456'
                && $request['token'] === 'test-token-123';
        });
    }

    public function test_test_connection_requires_valid_phone_format(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => null,
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/whatsapp/test', [
            'instance_id' => 'instance999',
            'token' => 'test-token-123',
            'test_phone' => 'invalid-phone',
        ]);

        $response->assertStatus(422);
    }
}

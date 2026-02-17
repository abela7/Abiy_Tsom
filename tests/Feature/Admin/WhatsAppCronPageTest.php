<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppCronPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_cron_page(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.cron'));

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.cron')
            ->assertViewHas(['phpPath', 'artisanPath', 'appUrl']);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendBulkWhatsAppMessageJob;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppBulkMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_bulk_message_section_on_template_page(): void
    {
        $admin = $this->createSuperAdmin();
        $this->createActiveMember('Abel', '+447700900111');

        $response = $this->actingAs($admin)->get(route('admin.whatsapp.template'));

        $response->assertOk()
            ->assertViewIs('admin.whatsapp.template')
            ->assertSee(__('app.whatsapp_bulk_send_title'));
    }

    public function test_super_admin_can_queue_bulk_message_for_all_active_members(): void
    {
        Queue::fake();
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        $admin = $this->createSuperAdmin();
        $first = $this->createActiveMember('Abel', '+447700900111');
        $second = $this->createActiveMember('Sara', '+447700900112');

        Member::create([
            'baptism_name' => 'Pending',
            'token' => str_repeat('p', 64),
            'whatsapp_phone' => '+447700900113',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.template.bulk-send'), [
            'recipient_mode' => 'all_active',
            'bulk_header' => 'Important update',
            'bulk_content' => 'Please read this today.',
            'bulk_link' => 'https://example.com/bulk',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, 2);
        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, function (SendBulkWhatsAppMessageJob $job) use ($first): bool {
            return $job->memberId === $first->id
                && $job->header === 'Important update'
                && $job->content === 'Please read this today.'
                && $job->url === 'https://example.com/bulk';
        });
        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, function (SendBulkWhatsAppMessageJob $job) use ($second): bool {
            return $job->memberId === $second->id;
        });
    }

    public function test_selected_active_mode_requires_member_selection(): void
    {
        Queue::fake();
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        $admin = $this->createSuperAdmin();

        $response = $this->from(route('admin.whatsapp.template'))
            ->actingAs($admin)
            ->post(route('admin.whatsapp.template.bulk-send'), [
                'recipient_mode' => 'selected_active',
                'bulk_header' => 'Important update',
                'bulk_content' => 'Please read this today.',
            ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHasErrors('selected_member_ids');

        Queue::assertNothingPushed();
    }

    public function test_selected_active_mode_rejects_ineligible_members(): void
    {
        Queue::fake();
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        $admin = $this->createSuperAdmin();
        $pending = Member::create([
            'baptism_name' => 'Pending',
            'token' => str_repeat('q', 64),
            'whatsapp_phone' => '+447700900114',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
        ]);

        $response = $this->from(route('admin.whatsapp.template'))
            ->actingAs($admin)
            ->post(route('admin.whatsapp.template.bulk-send'), [
                'recipient_mode' => 'selected_active',
                'selected_member_ids' => [$pending->id],
                'bulk_header' => 'Important update',
                'bulk_content' => 'Please read this today.',
            ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHasErrors('selected_member_ids');

        Queue::assertNothingPushed();
    }

    private function createSuperAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'username' => 'superadmin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);
    }

    private function createActiveMember(string $name, string $phone): Member
    {
        return Member::create([
            'baptism_name' => $name,
            'token' => str_repeat(strtolower($name[0]), 64),
            'whatsapp_phone' => $phone,
            'whatsapp_reminder_enabled' => true,
            'whatsapp_confirmation_status' => 'confirmed',
            'whatsapp_language' => 'en',
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Jobs\SendBulkWhatsAppMessageJob;
use App\Models\Member;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
            'bulk_message_en' => 'Hello :name, English bulk message.',
            'bulk_message_am' => 'ሰላም :name, ይህ የአማርኛ መልእክት ነው።',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, 2);
        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, function (SendBulkWhatsAppMessageJob $job) use ($first): bool {
            return $job->memberId === $first->id
                && $job->englishMessage === 'Hello :name, English bulk message.'
                && $job->amharicMessage === 'ሰላም :name, ይህ የአማርኛ መልእክት ነው።';
        });
        Queue::assertPushed(SendBulkWhatsAppMessageJob::class, function (SendBulkWhatsAppMessageJob $job) use ($second): bool {
            return $job->memberId === $second->id;
        });
    }

    public function test_super_admin_can_save_bulk_messages_and_see_them_after_refresh(): void
    {
        $admin = $this->createSuperAdmin();

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.template.bulk-save'), [
            'bulk_message_en' => 'Hello :name, saved English message.',
            'bulk_message_am' => 'ሰላም :name, የተቀመጠ የአማርኛ መልእክት።',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        $this->assertSame(
            'Hello :name, saved English message.',
            Translation::query()
                ->where('group', 'whatsapp_member')
                ->where('key', 'whatsapp_bulk_message_custom_body')
                ->where('locale', 'en')
                ->value('value')
        );

        $this->actingAs($admin)
            ->get(route('admin.whatsapp.template'))
            ->assertOk()
            ->assertSee('Hello :name, saved English message.', false)
            ->assertSee('ሰላም :name, የተቀመጠ የአማርኛ መልእክት።', false);
    }

    public function test_super_admin_can_send_bulk_sample_using_selected_members_language(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $admin = $this->createSuperAdmin();
        $member = $this->createActiveMember('Abel', '+447700900111');
        $member->update(['whatsapp_language' => 'am']);

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.template.bulk-test'), [
            'bulk_sample_member_id' => $member->id,
            'bulk_message_en' => 'Hello :name, English sample.',
            'bulk_message_am' => 'ሰላም :name, የአማርኛ ሙከራ መልእክት።',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request->url() === 'https://api.ultramsg.com/instance999/messages/chat'
                && $request['to'] === '+447700900111'
                && str_contains((string) $request['body'], 'ሰላም Abel, የአማርኛ ሙከራ መልእክት።');
        });
    }

    public function test_bulk_sample_can_use_single_selected_recipient_without_sample_member_field(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $admin = $this->createSuperAdmin();
        $member = $this->createActiveMember('Abel', '+447700900111');

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.template.bulk-test'), [
            'recipient_mode' => 'selected_active',
            'selected_member_ids' => [$member->id],
            'bulk_message_en' => 'Hello :name, English sample.',
            'bulk_message_am' => 'ሰላም :name, የአማርኛ ሙከራ መልእክት።',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request['to'] === '+447700900111'
                && str_contains((string) $request['body'], 'Hello Abel, English sample.');
        });
    }

    public function test_bulk_sample_prefers_single_selected_recipient_over_stale_sample_member(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $admin = $this->createSuperAdmin();
        $first = $this->createActiveMember('Abel', '+447700900111');
        $second = $this->createActiveMember('Sara', '+447700900112');

        $response = $this->actingAs($admin)->post(route('admin.whatsapp.template.bulk-test'), [
            'recipient_mode' => 'selected_active',
            'selected_member_ids' => [$second->id],
            'bulk_sample_member_id' => $first->id,
            'bulk_message_en' => 'Hello :name, English sample.',
            'bulk_message_am' => 'ሰላም :name, የአማርኛ ሙከራ መልእክት።',
        ]);

        $response->assertRedirect(route('admin.whatsapp.template'))
            ->assertSessionHas('success');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            return $request['to'] === '+447700900112'
                && str_contains((string) $request['body'], 'Hello Sara, English sample.');
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
                'bulk_message_en' => 'Hello :name',
                'bulk_message_am' => 'ሰላም :name',
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
                'bulk_message_en' => 'Hello :name',
                'bulk_message_am' => 'ሰላም :name',
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

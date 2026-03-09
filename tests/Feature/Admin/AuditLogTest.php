<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropAllTables();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->nullable()->unique();
            $table->string('whatsapp_phone')->nullable();
            $table->string('password');
            $table->string('role')->default('admin');
            $table->boolean('is_super_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('action')->nullable();
            $table->string('method', 10);
            $table->string('url', 2048);
            $table->string('target_type')->nullable();
            $table->string('target_id')->nullable();
            $table->string('target_label')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_write_request_creates_audit_log_and_redacts_sensitive_fields(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $targetAdmin = User::create([
            'name' => 'Editor User',
            'username' => 'editor1',
            'email' => 'editor@example.com',
            'password' => bcrypt('old-password'),
            'role' => 'editor',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($superAdmin)->put(
            route('admin.admins.update', $targetAdmin),
            [
                'name' => 'Updated Editor',
                'username' => $targetAdmin->username,
                'email' => $targetAdmin->email,
                'whatsapp_phone' => '',
                'role' => 'editor',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]
        );

        $response->assertRedirect(route('admin.admins.index'));

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('admin.admins.update', $auditLog->route_name);
        $this->assertSame('Updated admin user', $auditLog->action);
        $this->assertSame('User', $auditLog->target_type);
        $this->assertSame((string) $targetAdmin->id, $auditLog->target_id);
        $this->assertSame('[REDACTED]', $auditLog->request_summary['password']);
        $this->assertSame('[REDACTED]', $auditLog->request_summary['password_confirmation']);
        $this->assertContains('password', $auditLog->changed_fields ?? []);
        $this->assertSame('Editor User', $auditLog->meta['value_changes']['name']['before']);
        $this->assertSame('Updated Editor', $auditLog->meta['value_changes']['name']['after']);
        $this->assertSame('[REDACTED]', $auditLog->meta['value_changes']['password']['before']);
        $this->assertSame('[REDACTED]', $auditLog->meta['value_changes']['password']['after']);
        $this->assertSame(302, $auditLog->status_code);
    }

    public function test_super_admin_can_view_and_filter_audit_logs(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $otherAdmin = User::create([
            'name' => 'Other Admin',
            'username' => 'otheradmin',
            'email' => 'other@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        AuditLog::create([
            'admin_user_id' => $superAdmin->id,
            'route_name' => 'admin.admins.update',
            'action' => 'Updated admin user',
            'method' => 'PUT',
            'url' => 'http://localhost/admin/admins/2',
            'target_type' => 'User',
            'target_id' => '2',
            'target_label' => 'Editor User',
            'status_code' => 302,
            'request_summary' => ['name' => 'Updated Editor'],
            'changed_fields' => ['name'],
            'meta' => [
                'route_parameters' => ['admin' => ['type' => 'User', 'id' => '2']],
                'value_changes' => [
                    'name' => [
                        'before' => 'Editor User',
                        'after' => 'Updated Editor',
                    ],
                ],
            ],
        ]);

        AuditLog::create([
            'admin_user_id' => $otherAdmin->id,
            'route_name' => 'admin.members.destroy',
            'action' => 'Deleted member',
            'method' => 'DELETE',
            'url' => 'http://localhost/admin/members/3',
            'target_type' => 'Member',
            'target_id' => '3',
            'target_label' => 'Member Three',
            'status_code' => 302,
            'request_summary' => ['confirmed' => true],
            'changed_fields' => ['confirmed'],
            'meta' => ['route_parameters' => ['member' => ['type' => 'Member', 'id' => '3']]],
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.audit.index', [
            'admin_user_id' => $superAdmin->id,
        ]));

        $response->assertOk()
            ->assertSeeText('Updated admin user')
            ->assertSeeText('Editor User')
            ->assertSeeText('Updated Editor')
            ->assertDontSeeText('Deleted member');
    }

    public function test_audit_page_is_super_admin_only(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.audit.index'));

        $response->assertForbidden();
    }

    public function test_non_write_admin_get_requests_do_not_create_audit_logs(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.admins.index'));

        $response->assertOk();
        $this->assertSame(0, AuditLog::count());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use App\Services\WhatsAppReminderConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppConfirmationMessageSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_messages_do_not_include_member_access_links(): void
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
            'token' => str_repeat('z', 64),
            'locale' => 'en',
            'theme' => 'light',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_language' => 'en',
        ]);

        $service = $this->app->make(WhatsAppReminderConfirmationService::class);

        $this->assertTrue($service->sendOptInPrompt($member));
        $this->assertTrue($service->sendInvalidReplyPrompt($member));
        $this->assertTrue($service->sendConfirmedNotice($member));
        $this->assertTrue($service->sendRejectedNotice($member));
        $this->assertTrue($service->sendGoBackMessage($member));

        $recorded = Http::recorded();

        $this->assertCount(5, $recorded);

        $websiteUrl = 'https://abiytsom.abuneteklehaymanot.org/auth/go';
        $foundSafeGoBackUrl = false;
        $foundShareWarning = false;

        foreach ($recorded as [$request]) {
            $this->assertSame('https://api.ultramsg.com/instance999/messages/chat', $request->url());
            $this->assertSame('token-123', $request['token']);
            $this->assertSame('+447700900123', $request['to']);
            $this->assertIsString($request['body']);
            $this->assertStringNotContainsString('/member/access/', $request['body']);
            $this->assertStringNotContainsString($member->token, $request['body']);

            if (str_contains($request['body'], $websiteUrl)) {
                $foundSafeGoBackUrl = true;
            }

            if (str_contains($request['body'], 'do not share this link')) {
                $foundShareWarning = true;
            }
        }

        $this->assertTrue($foundSafeGoBackUrl);
        $this->assertTrue($foundShareWarning);
    }
}

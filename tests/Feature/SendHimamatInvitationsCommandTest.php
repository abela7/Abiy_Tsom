<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendHimamatInvitationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_himamat_invitation_campaign_once_per_member(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        $this->createActiveSeason();
        $firstMember = $this->createMember('a', '+447700900111');
        $secondMember = $this->createMember('b', '+447700900222');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan('himamat:send-invitations --campaign=holy-monday-launch')
            ->assertExitCode(0);

        $this->artisan('himamat:send-invitations --campaign=holy-monday-launch')
            ->assertExitCode(0);

        Http::assertSentCount(2);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($firstMember): bool {
            $body = (string) $request['body'];

            return $request['to'] === '+447700900111'
                && str_contains($body, 'Greetings Member a')
                && str_contains($body, '/himamat/access/'.$firstMember->token);
        });
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($secondMember): bool {
            $body = (string) $request['body'];

            return $request['to'] === '+447700900222'
                && str_contains($body, 'Greetings Member b')
                && str_contains($body, '/himamat/access/'.$secondMember->token);
        });

        $this->assertDatabaseHas('member_himamat_invitation_deliveries', [
            'member_id' => $firstMember->id,
            'campaign_key' => 'holy-monday-launch',
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);

        $this->assertDatabaseHas('member_himamat_invitation_deliveries', [
            'member_id' => $secondMember->id,
            'campaign_key' => 'holy-monday-launch',
            'channel' => 'whatsapp',
            'status' => 'sent',
        ]);
    }

    public function test_command_can_send_sample_invitation_without_recording_campaign_delivery(): void
    {
        config()->set('services.ultramsg.instance_id', 'instance999');
        config()->set('services.ultramsg.token', 'token-123');
        config()->set('app.url', 'https://abiytsom.abuneteklehaymanot.org');

        $this->createActiveSeason();
        $member = $this->createMember('s', '+447700900333');

        Http::fake([
            'https://api.ultramsg.com/instance999/messages/chat' => Http::response([
                'sent' => 'true',
                'message' => 'ok',
                'id' => 12345,
            ]),
        ]);

        $this->artisan(sprintf(
            'himamat:send-invitations --campaign=holy-monday-launch --sample-member-id=%d --sample-phone=+447700900999 --send-now',
            $member->id
        ))->assertExitCode(0);

        Http::assertSentCount(1);
        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($member): bool {
            $body = (string) $request['body'];

            return $request['to'] === '+447700900999'
                && str_contains($body, 'Greetings Member s')
                && str_contains($body, '/himamat/access/'.$member->token);
        });

        $this->assertDatabaseCount('member_himamat_invitation_deliveries', 0);
    }

    private function createActiveSeason(): void
    {
        \App\Models\LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);
    }

    private function createMember(string $fill, string $phone): Member
    {
        return Member::create([
            'baptism_name' => 'Member '.$fill,
            'token' => str_repeat($fill, 64),
            'locale' => 'en',
            'theme' => 'sepia',
            'whatsapp_phone' => $phone,
            'whatsapp_confirmation_status' => 'confirmed',
        ]);
    }
}

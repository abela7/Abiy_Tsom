<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberReminderOpen;
use App\Models\MemberSession;
use App\Models\TelegramAccessToken;
use App\Models\WeeklyTheme;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareDayReminderLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_day_code_without_member_session_falls_back_to_public_page(): void
    {
        $member = $this->createMember('a');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        $response = $this->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $response->assertRedirect(route('share.day.public', ['daily' => $daily]));

        $this->assertDatabaseCount('member_sessions', 0);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_share_day_public_open_is_tracked_for_member(): void
    {
        $member = $this->createMember('a1');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        $this->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ])->assertRedirect(route('share.day.public', ['daily' => $daily]));

        $this->assertDatabaseHas('member_reminder_opens', [
            'member_id' => $member->id,
            'daily_content_id' => $daily->id,
            'open_count' => 1,
            'authenticated_open_count' => 0,
            'public_open_count' => 1,
            'last_open_state' => 'link_only',
        ]);
    }

    public function test_share_day_code_with_matching_member_session_redirects_to_member_day(): void
    {
        $member = $this->createMember('b');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        [$sessionToken, $deviceId] = $this->createMemberSession($member);

        $response = $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
                'User-Agent' => 'Mozilla/5.0 Test Browser',
            ]);

        $response->assertRedirect($daily->memberDayUrl());

        $this->assertDatabaseCount('member_sessions', 1);
        $this->assertNotNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_share_day_authenticated_open_is_tracked(): void
    {
        $member = $this->createMember('b1');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        [$sessionToken, $deviceId] = $this->createMemberSession($member);

        $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
                'User-Agent' => 'Mozilla/5.0 Test Browser',
            ])
            ->assertRedirect($daily->memberDayUrl());

        $open = MemberReminderOpen::query()
            ->where('member_id', $member->id)
            ->where('daily_content_id', $daily->id)
            ->first();

        $this->assertNotNull($open);
        $this->assertSame(1, $open->open_count);
        $this->assertSame(1, $open->authenticated_open_count);
        $this->assertSame(0, $open->public_open_count);
        $this->assertSame('authenticated_session', $open->last_open_state);
        $this->assertNotNull($open->last_authenticated_open_at);
    }

    public function test_share_day_code_with_different_member_session_stays_public(): void
    {
        $intendedMember = $this->createMember('c');
        $otherMember = $this->createMember('d');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $intendedMember,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        [$sessionToken, $deviceId] = $this->createMemberSession($otherMember);

        $response = $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
                'User-Agent' => 'Mozilla/5.0 Test Browser',
            ]);

        $response->assertRedirect(route('share.day.public', ['daily' => $daily]));

        $this->assertDatabaseCount('member_sessions', 1);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_preview_bot_open_does_not_track_reminder_open(): void
    {
        $member = $this->createMember('d1');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        $this->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
            'User-Agent' => 'WhatsApp/2.24.10 A',
        ])->assertOk();

        $this->assertDatabaseCount('member_reminder_opens', 0);
    }

    public function test_share_day_code_cannot_be_used_on_auth_access_route(): void
    {
        $member = $this->createMember('e');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        $response = $this->get(route('auth.access', ['code' => $code]), [
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $response->assertRedirect(route('home'));

        $this->assertDatabaseCount('member_sessions', 0);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_share_day_code_cannot_be_used_on_auth_go_route(): void
    {
        $member = $this->createMember('f');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $daily->memberDayUrl(false)
        );

        $response = $this->get(route('auth.go', ['code' => $code]), [
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $response->assertOk()
            ->assertViewIs('auth.go');

        $this->assertDatabaseCount('member_sessions', 0);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_legacy_member_day_url_redirects_to_canonical_url(): void
    {
        $member = $this->createMember('legacy');
        $daily = $this->createPublishedDay();

        [$sessionToken, $deviceId] = $this->createMemberSession($member);

        $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get('/member/day/'.$daily->id)
            ->assertRedirect($daily->memberDayUrl());
    }

    public function test_legacy_member_commemorations_url_redirects_to_canonical_url(): void
    {
        $member = $this->createMember('legacy2');
        $daily = $this->createPublishedDay();

        [$sessionToken, $deviceId] = $this->createMemberSession($member);

        $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get('/member/day/'.$daily->id.'/commemorations')
            ->assertRedirect($daily->memberCommemorationsUrl());
    }

    private function createPublishedDay(): DailyContent
    {
        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $theme = WeeklyTheme::create([
            'lent_season_id' => $season->id,
            'week_number' => 1,
            'name_en' => 'Zewerede',
            'meaning' => 'He who descended from above',
            'week_start_date' => '2026-02-15',
            'week_end_date' => '2026-02-21',
        ]);

        return DailyContent::create([
            'lent_season_id' => $season->id,
            'weekly_theme_id' => $theme->id,
            'day_number' => 19,
            'date' => '2026-03-06',
            'day_title_en' => 'Test Day',
            'is_published' => true,
        ]);
    }

    private function createMember(string $fill): Member
    {
        return Member::create([
            'baptism_name' => 'Member '.$fill,
            'token' => str_repeat($fill, 64),
            'locale' => 'en',
            'theme' => 'sepia',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function createMemberSession(Member $member): array
    {
        $sessionToken = str_repeat((string) $member->id, 40);
        $deviceId = str_repeat(chr(96 + $member->id), 20);

        MemberSession::create([
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $sessionToken),
            'device_hash' => hash('sha256', $deviceId),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'last_used_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        return [$sessionToken, $deviceId];
    }

    private function findAccessToken(string $code): ?TelegramAccessToken
    {
        return TelegramAccessToken::query()
            ->where('token_hash', hash('sha256', $code))
            ->first();
    }
}

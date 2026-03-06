<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
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
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily], false)
        );

        $response = $this->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
            'User-Agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $response->assertOk()
            ->assertViewIs('member.share-day')
            ->assertSee(route('share.day.public', ['daily' => $daily]), false);

        $this->assertDatabaseCount('member_sessions', 0);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_share_day_code_with_matching_member_session_redirects_to_member_day(): void
    {
        $member = $this->createMember('b');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily], false)
        );

        [$sessionToken, $deviceId] = $this->createMemberSession($member);

        $response = $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
                'User-Agent' => 'Mozilla/5.0 Test Browser',
            ]);

        $response->assertRedirect(route('member.day', ['daily' => $daily]));

        $this->assertDatabaseCount('member_sessions', 1);
        $this->assertNotNull($this->findAccessToken($code)?->consumed_at);
    }

    public function test_share_day_code_with_different_member_session_stays_public(): void
    {
        $intendedMember = $this->createMember('c');
        $otherMember = $this->createMember('d');
        $daily = $this->createPublishedDay();
        $code = app(TelegramAuthService::class)->createCode(
            $intendedMember,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily], false)
        );

        [$sessionToken, $deviceId] = $this->createMemberSession($otherMember);

        $response = $this->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->get(route('share.day', ['daily' => $daily, 'code' => $code]), [
                'User-Agent' => 'Mozilla/5.0 Test Browser',
            ]);

        $response->assertOk()
            ->assertViewIs('member.share-day')
            ->assertSee(route('share.day.public', ['daily' => $daily]), false);

        $this->assertDatabaseCount('member_sessions', 1);
        $this->assertNull($this->findAccessToken($code)?->consumed_at);
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

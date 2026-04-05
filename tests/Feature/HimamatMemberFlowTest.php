<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\RequireMemberIdentityConfirmation;
use App\Models\EthiopianSynaxariumAnnual;
use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\HimamatSlot;
use App\Models\HimamatSlotResource;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberPersistentDevice;
use App\Models\MemberSession;
use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HimamatMemberFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_access_route_establishes_silent_member_session_and_redirects_to_preferences(): void
    {
        $this->createSeason();
        $member = $this->createMember('a');

        $response = $this->get('/himamat/access/'.$member->token);

        $response->assertRedirect(route('member.himamat.preferences'));
        $response->assertCookie(MemberSessionService::SESSION_COOKIE);
        $response->assertCookie(MemberSessionService::DEVICE_COOKIE);
        $response->assertCookie(PersistentLoginService::COOKIE_NAME);
        $response->assertCookie('trusted_device');

        $this->assertDatabaseCount('member_sessions', 1);
        $this->assertDatabaseCount('member_persistent_devices', 1);
    }

    public function test_preferences_page_creates_default_preference_row_on_first_load(): void
    {
        $season = $this->createSeason();
        $member = $this->createMember('d');

        $this->actingAsRememberedMember($member)
            ->get('/member/himamat/preferences')
            ->assertOk()
            ->assertSee('Holy Week Reminders')
            ->assertSee('Daily Introduction (7:00 AM)')
            ->assertSee('Save My Preferences');

        $this->assertDatabaseHas('member_himamat_preferences', [
            'member_id' => $member->id,
            'lent_season_id' => $season->id,
            'enabled' => 1,
            'intro_enabled' => 1,
            'third_enabled' => 1,
            'sixth_enabled' => 1,
            'ninth_enabled' => 1,
            'eleventh_enabled' => 1,
        ]);
    }

    public function test_preferences_api_updates_himamat_toggles_after_bridge_entry(): void
    {
        $season = $this->createSeason();
        $member = $this->createMember('b');

        $this->actingAsRememberedMember($member)
            ->postJson('/api/member/himamat/preferences', [
                'enabled' => true,
                'intro_enabled' => false,
                'third_enabled' => true,
                'sixth_enabled' => false,
                'ninth_enabled' => true,
                'eleventh_enabled' => false,
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Your Holy Week reminders have been saved.',
            ]);

        $this->assertDatabaseHas('member_himamat_preferences', [
            'member_id' => $member->id,
            'lent_season_id' => $season->id,
            'enabled' => 1,
            'intro_enabled' => 0,
            'third_enabled' => 1,
            'sixth_enabled' => 0,
            'ninth_enabled' => 1,
            'eleventh_enabled' => 0,
        ]);
    }

    public function test_preferences_page_uses_amharic_translation_keys_for_amharic_members(): void
    {
        $this->createSeason();
        $member = $this->createMember('e', 'am');

        $this->actingAsRememberedMember($member)
            ->get('/member/himamat/preferences')
            ->assertOk()
            ->assertSee('የሕማማት ሳምንት ማሳሰቢያዎች')
            ->assertSee('የዕለቱ መክፈቻ (7:00 AM)')
            ->assertSee('ምርጫዬን አስቀምጥ');
    }

    public function test_member_himamat_index_redirects_to_current_london_slot_and_day_view_renders(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-10 15:00:00', 'Europe/London'));

        $this->createSeason([
            'start_date' => '2026-02-15',
            'end_date' => '2026-04-12',
        ]);
        $member = $this->createMember('c');
        $day = $this->createPublishedDayWithSlots('good-friday', '2026-04-10', 'Good Friday');

        $this->actingAsRememberedMember($member)
            ->get('/member/himamat')
            ->assertRedirect(route('member.himamat.slot', ['day' => $day->slug, 'slot' => 'ninth']));

        $this->actingAsRememberedMember($member)
            ->get('/member/himamat/'.$day->slug.'/ninth')
            ->assertOk()
            ->assertSee('Good Friday')
            ->assertSee('The fig tree shows the call to repentance.')
            ->assertSee('Wear black in mourning and keep reverent silence.')
            ->assertSee('Synaxarium of Good Friday')
            ->assertSee('The church keeps the appointed Synaxarium in mourning and remembrance.')
            ->assertSee('Why do we say Kiryalaisson 12 times?')
            ->assertSee('To keep watch with repentance and mercy.')
            ->assertSee('Sacred Timeline')
            ->assertSee('Current')
            ->assertSee('Ninth Hour')
            ->assertSee('Bible Section')
            ->assertSee('Cross icon');
    }

    public function test_member_day_uses_manual_synaxarium_link_when_configured(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-06 09:00:00', 'Europe/London'));

        $this->createSeason();
        $member = $this->createMember('m');
        EthiopianSynaxariumAnnual::create([
            'month' => 8,
            'day' => 27,
            'is_main' => true,
            'sort_order' => 1,
            'celebration_en' => 'Manual Linked Saint',
            'description_en' => 'Fetched from the chosen Ethiopian day.',
        ]);

        $day = $this->createPublishedDayWithSlots('holy-monday', '2026-04-06', 'Holy Monday');
        $day->update([
            'synaxarium_source' => 'manual',
            'synaxarium_month' => 8,
            'synaxarium_day' => 27,
        ]);

        $this->actingAsRememberedMember($member)
            ->get('/member/himamat/'.$day->slug.'/third')
            ->assertOk()
            ->assertSee('Manual Linked Saint');
    }

    private function createSeason(array $overrides = []): LentSeason
    {
        return LentSeason::create(array_merge([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ], $overrides));
    }

    private function createMember(string $fill, string $locale = 'en'): Member
    {
        return Member::create([
            'baptism_name' => 'Member '.$fill,
            'token' => str_repeat($fill, 64),
            'locale' => $locale,
            'theme' => 'sepia',
            'whatsapp_phone' => '+447700900123',
            'whatsapp_confirmation_status' => 'confirmed',
        ]);
    }

    private function createPublishedDayWithSlots(string $slug, string $date, string $title): HimamatDay
    {
        $season = LentSeason::active();

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => $slug,
            'sort_order' => 1,
            'date' => $date,
            'title_en' => $title,
            'spiritual_meaning_en' => 'The fig tree shows the call to repentance.',
            'ritual_guide_intro_en' => 'Wear black in mourning and keep reverent silence.',
            'synaxarium_title_en' => 'Synaxarium of '.$title,
            'synaxarium_text_en' => 'The church keeps the appointed Synaxarium in mourning and remembrance.',
            'is_published' => true,
        ]);

        HimamatDayFaq::create([
            'himamat_day_id' => $day->id,
            'sort_order' => 1,
            'question_en' => 'Why do we say Kiryalaisson 12 times?',
            'answer_en' => 'To keep watch with repentance and mercy.',
        ]);

        $headers = [
            'intro' => 'Daily Introduction',
            'third' => 'Third Hour',
            'sixth' => 'Sixth Hour',
            'ninth' => 'Ninth Hour',
            'eleventh' => 'Eleventh Hour',
        ];

        foreach (config('himamat.slots', []) as $slot) {
            HimamatSlot::create([
                'himamat_day_id' => $day->id,
                'slot_key' => $slot['key'],
                'slot_order' => $slot['order'],
                'scheduled_time_london' => $slot['time'],
                'slot_header_en' => $headers[$slot['key']],
                'reminder_header_en' => $headers[$slot['key']],
                'reading_reference_en' => 'Reading '.$headers[$slot['key']],
                'reading_text_en' => 'Text for '.$headers[$slot['key']],
                'is_published' => true,
            ]);
        }

        $ninthSlot = HimamatSlot::query()
            ->where('himamat_day_id', $day->id)
            ->where('slot_key', 'ninth')
            ->firstOrFail();

        HimamatSlotResource::create([
            'himamat_slot_id' => $ninthSlot->id,
            'type' => HimamatSlotResource::TYPE_WEBSITE,
            'sort_order' => 1,
            'title_en' => 'Cross icon',
            'url' => 'https://example.com/cross-icon',
        ]);

        return $day;
    }

    private function actingAsRememberedMember(Member $member): static
    {
        $deviceId = Str::random(32);
        $sessionToken = Str::random(80);
        $selector = Str::random(24);
        $persistentToken = Str::random(64);
        $persistentPayload = $selector.'.'.$persistentToken;

        MemberSession::query()->create([
            'member_id' => $member->id,
            'token_hash' => hash('sha256', $sessionToken),
            'device_hash' => hash('sha256', $deviceId),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel Test Browser',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        MemberPersistentDevice::query()->create([
            'member_id' => $member->id,
            'selector' => $selector,
            'token_hash' => hash('sha256', $persistentToken),
            'device_hash' => hash('sha256', 'Laravel Test Browser||'),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Laravel Test Browser',
            'last_used_at' => now(),
            'expires_at' => now()->addDays(365),
        ]);

        $trustedCookie = RequireMemberIdentityConfirmation::makeTrustedCookie($member);

        return $this
            ->withServerVariables(['HTTP_USER_AGENT' => 'Laravel Test Browser'])
            ->withCredentials()
            ->withCookie(MemberSessionService::SESSION_COOKIE, $sessionToken)
            ->withCookie(MemberSessionService::DEVICE_COOKIE, $deviceId)
            ->withCookie(PersistentLoginService::COOKIE_NAME, $persistentPayload)
            ->withCookie($trustedCookie->getName(), $trustedCookie->getValue());
    }
}

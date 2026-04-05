<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireMemberIdentityConfirmation;
use App\Models\HimamatDay;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberHimamatInvitationDelivery;
use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HimamatAccessController extends Controller
{
    public function preferences(
        Request $request,
        string $token,
        MemberSessionService $sessions,
        PersistentLoginService $persistentLogins
    ): RedirectResponse {
        $member = Member::query()->where('token', $token)->first();
        if (! $member) {
            return redirect()->route('home');
        }

        $this->recordInvitationOpen($request, $member);

        return $this->establishAccess($request, $member, $sessions, $persistentLogins)
            ->route('member.himamat.preferences');
    }

    public function day(
        Request $request,
        string $token,
        string $day,
        string $slot,
        MemberSessionService $sessions,
        PersistentLoginService $persistentLogins
    ): RedirectResponse {
        $member = Member::query()->where('token', $token)->first();
        if (! $member) {
            return redirect()->route('home');
        }

        $this->recordInvitationOpen($request, $member);

        $season = LentSeason::active();
        $himamatDay = $season
            ? HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->where('slug', $day)
                ->where('is_published', true)
                ->first()
            : null;

        if (! $himamatDay || ! $himamatDay->slots()
            ->where('slot_key', $slot)
            ->where('is_published', true)
            ->exists()) {
            return $this->establishAccess($request, $member, $sessions, $persistentLogins)
                ->route('member.himamat.preferences');
        }

        return $this->establishAccess($request, $member, $sessions, $persistentLogins)
            ->route('member.himamat.slot', ['day' => $himamatDay->slug, 'slot' => $slot]);
    }

    private function establishAccess(
        Request $request,
        Member $member,
        MemberSessionService $sessions,
        PersistentLoginService $persistentLogins
    ): HimamatRedirector {
        $request->session()->regenerate();
        $sessions->establishSession($member, $request);

        $currentPersistentDevice = $persistentLogins->resolveFromRequest($request);
        if ($currentPersistentDevice && $currentPersistentDevice->member?->is($member)) {
            $persistentLogins->touch(
                $currentPersistentDevice,
                $request,
                $persistentLogins->currentPayload($request)
            );
        } else {
            $persistentLogins->issue(
                $member,
                $request,
                $persistentLogins->findTrustedDeviceFor($member, $request)
            );
        }

        RequireMemberIdentityConfirmation::confirm();

        return new HimamatRedirector($member);
    }

    private function recordInvitationOpen(Request $request, Member $member): void
    {
        $campaignKey = trim((string) $request->query('campaign', ''));

        $deliveryQuery = MemberHimamatInvitationDelivery::query()
            ->where('member_id', $member->id)
            ->where('channel', 'whatsapp')
            ->where('status', 'sent');

        if ($campaignKey !== '') {
            $deliveryQuery->where('campaign_key', $campaignKey);
        }

        /** @var MemberHimamatInvitationDelivery|null $delivery */
        $delivery = $deliveryQuery
            ->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->first();

        if (! $delivery && $campaignKey === '') {
            return;
        }

        if (! $delivery) {
            $delivery = MemberHimamatInvitationDelivery::query()
                ->where('member_id', $member->id)
                ->where('channel', 'whatsapp')
                ->where('status', 'sent')
                ->orderByDesc('delivered_at')
                ->orderByDesc('id')
                ->first();
        }

        if (! $delivery) {
            return;
        }

        $now = now();

        $delivery->forceFill([
            'open_count' => (int) $delivery->open_count + 1,
            'first_opened_at' => $delivery->first_opened_at ?: $now,
            'last_opened_at' => $now,
        ])->save();
    }
}

final class HimamatRedirector
{
    public function __construct(
        private readonly Member $member
    ) {}

    public function route(string $name, array $parameters = []): RedirectResponse
    {
        return redirect()
            ->route($name, $parameters)
            ->withCookie(RequireMemberIdentityConfirmation::makeTrustedCookie($this->member));
    }
}

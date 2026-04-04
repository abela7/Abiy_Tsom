<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireMemberIdentityConfirmation;
use App\Models\HimamatDay;
use App\Models\LentSeason;
use App\Models\Member;
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

        $season = LentSeason::active();
        $himamatDay = $season
            ? HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->where('slug', $day)
                ->first()
            : null;

        if (! $himamatDay || ! $himamatDay->slots()->where('slot_key', $slot)->exists()) {
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

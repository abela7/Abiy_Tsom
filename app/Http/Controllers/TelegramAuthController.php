<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\TelegramAccessToken;
use App\Models\User;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TelegramAuthController extends Controller
{
    public function access(
        Request $request,
        TelegramAuthService $telegramAuthService,
        MemberSessionService $memberSessionService
    ): RedirectResponse {
        $code = (string) $request->query('code', '');
        if (trim($code) === '') {
            return redirect()->route('home');
        }

        $purpose = $this->detectPurpose($request->query('purpose'));
        $token = $telegramAuthService->consumeCode($code, $purpose);

        if (! $token) {
            return redirect()->route('home');
        }

        if ($telegramAuthService->isMemberToken($token)) {
            return $this->authenticateMember($request, $token, $memberSessionService, $telegramAuthService);
        }

        if ($telegramAuthService->isAdminToken($token)) {
            return $this->authenticateAdmin($request, $token, $telegramAuthService);
        }

        return redirect()->route('home');
    }

    public function miniConnect(Request $request): View
    {
        $code = trim((string) $request->query('code', ''));
        $purpose = trim((string) $request->query('purpose', ''));
        if (! in_array($purpose, [TelegramAuthService::PURPOSE_MEMBER_ACCESS, TelegramAuthService::PURPOSE_ADMIN_ACCESS], true)) {
            $purpose = '';
        }

        return view('telegram.mini-connect', [
            'prefilledCode' => $code,
            'purposeHint' => $purpose,
            'telegramAccessUrl' => route('telegram.access'),
        ]);
    }

    /**
     * Create a one-time Telegram login link for the current admin user.
     */
    public function createAdminLoginLink(Request $request, TelegramAuthService $telegramAuthService): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return redirect()->route('admin.login');
        }

        $next = (string) $request->input('next', '');
        $expiresIn = $request->integer('expires_in', 15);
        $ttl = max(5, min(120, $expiresIn));

        $code = $telegramAuthService->createCode(
            $user,
            TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            $telegramAuthService->sanitizeRedirectPath($next, $this->adminFallbackPath($user)),
            $ttl
        );

        return redirect()
            ->back()
            ->with('telegram_access_url', route('telegram.access', [
                'code' => $code,
                'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            ]))
            ->with('telegram_access_expires', $ttl);
    }

    private function detectPurpose(mixed $purpose): ?string
    {
        if (! is_string($purpose)) {
            return null;
        }

        return match (trim($purpose)) {
            TelegramAuthService::PURPOSE_ADMIN_ACCESS => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            default => null,
        };
    }

    private function authenticateMember(
        Request $request,
        TelegramAccessToken $token,
        MemberSessionService $memberSessionService,
        TelegramAuthService $telegramAuthService
    ): RedirectResponse {
        $member = $token->actor;
        if (! $member instanceof Member) {
            return redirect()->route('home');
        }

        if (! $memberSessionService->establishSession($member, $request)) {
            return redirect()->route('home');
        }

        $request->session()->regenerate();

        if ($member->passcode_enabled) {
            return redirect()->route('member.passcode');
        }

        $next = $telegramAuthService->sanitizeRedirectPath($token->redirect_to, '/member/home');
        return redirect($next);
    }

    private function authenticateAdmin(
        Request $request,
        TelegramAccessToken $token,
        TelegramAuthService $telegramAuthService
    ): RedirectResponse {
        $admin = $token->actor;
        if (! $admin instanceof User) {
            return redirect()->route('admin.login');
        }

        Auth::login($admin);
        $request->session()->regenerate();

        $next = $telegramAuthService->sanitizeRedirectPath($token->redirect_to, $this->adminFallbackPath($admin));

        return redirect($next);
    }

    private function adminFallbackPath(User $user): string
    {
        return match ($user->role) {
            'writer' => route('admin.daily.index'),
            default => route('admin.dashboard'),
        };
    }
}

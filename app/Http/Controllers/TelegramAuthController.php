<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\TelegramAccessToken;
use App\Models\User;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Illuminate\Http\JsonResponse;
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

    public function miniConnectSubmit(
        Request $request,
        TelegramAuthService $telegramAuthService
    ): JsonResponse {
        $rawInitData = (string) $request->input('init_data', '');
        $startParam = trim((string) $request->input('start_param', ''));
        $prefilledCode = trim((string) $request->input('code', ''));

        $telegramPayload = $telegramAuthService->parseWebAppInitData($rawInitData);
        if (! $telegramPayload) {
            return response()->json([
                'status' => 'error',
                'message' => 'This mini app must be opened from Telegram.',
            ], 422);
        }

        $telegramUserId = (string) $telegramPayload['user_id'];
        $actor = $telegramAuthService->actorFromTelegramId($telegramUserId);

        [$startRole, $startCode] = $this->extractStartPayload($startParam);
        $startRolePurpose = $this->purposeFromStartRole($startRole);
        $resolvedPurposeHint = $startRolePurpose;

        if ($startCode !== '') {
            [$startParamActor, $startParamPurpose] = $this->consumeCodeBinding(
                $startCode,
                $telegramAuthService,
                $startRolePurpose
            );

            if ($startParamActor instanceof Member || $startParamActor instanceof User) {
                if ($actor instanceof Member || $actor instanceof User) {
                    if (! $this->sameActor($actor, $startParamActor)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'This Telegram account is already linked with another profile.',
                        ], 403);
                    }
                } else {
                    $telegramAuthService->bindActorToTelegramId($startParamActor, $telegramUserId);
                    $actor = $startParamActor;
                }

                $resolvedPurposeHint = $startParamPurpose ?? $startRolePurpose;
            } elseif ($prefilledCode === '' && $actor === null) {
                return response()->json([
                    'status' => 'not_linked',
                    'message' => 'This one-time link is already used or invalid.',
                ], 422);
            }
        }

        if (! $actor && $prefilledCode !== '') {
            [$prefilledActor, $prefilledPurpose] = $this->consumeCodeBinding(
                $prefilledCode,
                $telegramAuthService,
                $startRolePurpose ?? $resolvedPurposeHint
            );

            if ($prefilledActor instanceof Member || $prefilledActor instanceof User) {
                $telegramAuthService->bindActorToTelegramId($prefilledActor, $telegramUserId);
                $actor = $prefilledActor;
                $resolvedPurposeHint = $prefilledPurpose ?? $resolvedPurposeHint;
            } else {
                return response()->json([
                    'status' => 'not_linked',
                    'message' => 'This one-time code could not be used.',
                ], 422);
            }
        }

        if (! $actor) {
            return response()->json([
                'status' => 'not_linked',
                'message' => 'No saved Telegram link. Ask your admin for the one-tap start-app link.',
            ], 422);
        }

        $requiredPurpose = $this->normalizePurposeHint($resolvedPurposeHint);
        if ($requiredPurpose !== null && $this->purposeForActor($actor, null) !== $requiredPurpose) {
            return response()->json([
                'status' => 'error',
                'message' => 'This Telegram link is not for this account type.',
            ], 403);
        }

        $purpose = $this->purposeForActor($actor, $requiredPurpose);
        $redirectTo = $this->launchTargetForActor($actor);

        $code = $telegramAuthService->createCode(
            $actor,
            $purpose,
            $redirectTo,
            $actor instanceof Member ? 120 : 30
        );

        return response()->json([
            'status' => 'linked',
            'access_url' => route('telegram.access', [
                'code' => $code,
                'purpose' => $purpose,
            ]),
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

        $miniMode = (string) $request->input('telegram_mode', '');
        if ($miniMode === 'mini') {
            $botUsername = config('services.telegram.bot_username');
            $botName = is_string($botUsername) ? trim((string) $botUsername) : '';
            if ($botName !== '') {
                $botName = ltrim($botName, '@');
                $payload = $this->buildStartAppPayload('admin', $code);
                return redirect()
                    ->back()
                    ->with('telegram_access_url', route('telegram.access', [
                        'code' => $code,
                        'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
                    ]))
                    ->with('telegram_access_expires', $ttl)
                    ->with(
                        'telegram_mini_access_url',
                        'https://t.me/' . $botName . '?startapp=' . rawurlencode($payload)
                    );
            }

            return redirect()
                ->back()
                ->with('error', __('app.telegram_bot_username_missing'));
        }

        return redirect()
            ->back()
            ->with('telegram_access_url', route('telegram.access', [
                'code' => $code,
                'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            ]))
            ->with('telegram_access_expires', $ttl)
            ->with('telegram_mini_access_url', null);
    }

    private function detectPurpose(mixed $purpose): ?string
    {
        if (! is_string($purpose)) {
            return null;
        }

        return match (trim($purpose)) {
            TelegramAuthService::PURPOSE_MEMBER_ACCESS, 'member' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            TelegramAuthService::PURPOSE_ADMIN_ACCESS => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            'admin' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            default => null,
        };
    }

    private function purposeForActor(Member|User $actor, ?string $requestedPurpose): string
    {
        if ($actor instanceof User) {
            return TelegramAuthService::PURPOSE_ADMIN_ACCESS;
        }

        return TelegramAuthService::PURPOSE_MEMBER_ACCESS;
    }

    private function launchTargetForActor(Member|User $actor): string
    {
        if ($actor instanceof User) {
            return $this->adminFallbackPath($actor);
        }

        return route('member.home');
    }

    private function consumeCodeBinding(
        string $code,
        TelegramAuthService $telegramAuthService,
        ?string $purpose = null
    ): array
    {
        $normalized = trim($code);
        if (! preg_match('/^[A-Za-z0-9]{20,128}$/', $normalized)) {
            return [null, null];
        }

        $token = $telegramAuthService->consumeCode($normalized, $purpose);
        if (! $token || ! $token->actor) {
            return [null, null];
        }

        return [$token->actor, $token->purpose];
    }

    private function normalizePurposeHint(?string $purposeHint): ?string
    {
        return match ($purposeHint) {
            TelegramAuthService::PURPOSE_MEMBER_ACCESS, TelegramAuthService::PURPOSE_ADMIN_ACCESS => $purposeHint,
            default => null,
        };
    }

    private function extractStartPayload(string $startParam): array
    {
        $normalized = trim($startParam);
        if ($normalized === '') {
            return [null, null];
        }

        if (preg_match('/^(member|admin):([A-Za-z0-9]{20,128})$/i', $normalized, $matches) === 1) {
            return [strtolower($matches[1]), trim($matches[2])];
        }

        if (preg_match('/^(member|admin)$/i', $normalized) === 1) {
            return [strtolower($normalized), null];
        }

        return [null, null];
    }

    private function purposeFromStartRole(?string $role): ?string
    {
        return match ($role) {
            'member' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            'admin' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            default => null,
        };
    }

    private function buildStartAppPayload(string $mode, string $code): string
    {
        return strtolower($mode) . ':' . $code;
    }

    private function sameActor(?object $first, ?object $second): bool
    {
        if (! $first || ! $second) {
            return false;
        }

        return get_class($first) === get_class($second) && (string) $first->getKey() === (string) $second->getKey();
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

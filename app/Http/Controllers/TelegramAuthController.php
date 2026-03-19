<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\DetectsPreviewBots;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\TelegramAccessToken;
use App\Models\Translation;
use App\Models\User;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TelegramAuthController extends Controller
{
    use DetectsPreviewBots;

    /**
     * Landing page for WhatsApp go-back links.
     *
     * Preview bots receive OG tags only (code is NOT consumed).
     * Human browsers with a valid code are authenticated server-side
     * and redirected — no client-side JS redirect chain needed.
     */
    public function go(
        Request $request,
        TelegramAuthService $telegramAuthService,
        MemberSessionService $memberSessionService
    ): View|Response {
        // Preview bots get OG tags only.
        if ($this->isPreviewBot($request)) {
            return view('auth.go');
        }

        $currentMember = $memberSessionService->resolveMember($request);

        // Human browser with a valid code: authenticate server-side.
        $code = (string) $request->query('code', '');

        if ($code !== '' && preg_match('/^[A-Za-z0-9]{20,128}$/', $code)) {
            $token = $telegramAuthService->consumeCode(
                $code,
                TelegramAuthService::PURPOSE_MEMBER_ACCESS
            );

            if ($token && $telegramAuthService->isMemberToken($token)) {
                $member = $token->actor;

                if ($member instanceof Member) {
                    $memberSessionService->establishSession($member, $request);
                    $request->session()->regenerate();

                    $redirectUrl = $member->passcode_enabled
                        ? route('member.passcode')
                        : $telegramAuthService->sanitizeRedirectPath(
                            $token->redirect_to,
                            '/member/home'
                        );

                    return response()->view('auth.authenticated', [
                        'redirectUrl' => $redirectUrl,
                    ]);
                }
            }

            if ($token && $telegramAuthService->isAdminToken($token)) {
                return $this->authenticateAdmin($request, $token, $telegramAuthService);
            }
        }

        if ($currentMember instanceof Member) {
            $redirectUrl = $currentMember->passcode_enabled
                ? route('member.passcode')
                : route('member.home');

            return response()->view('auth.authenticated', [
                'redirectUrl' => $redirectUrl,
            ]);
        }

        // No code or invalid code and no existing member session —
        // show OG page which redirects to home.
        return view('auth.go');
    }

    public function access(
        Request $request,
        TelegramAuthService $telegramAuthService,
        MemberSessionService $memberSessionService
    ): RedirectResponse|\Illuminate\Http\Response {
        $code = (string) $request->query('code', '');
        $fallback = $this->resolveFallbackUrl($request);

        if (trim($code) === '') {
            return redirect($fallback ?? route('home'));
        }

        $purpose = $this->detectPurpose($request->query('purpose'));
        if ($purpose === null) {
            $peekedToken = $telegramAuthService->peekCode($code);

            if (! $peekedToken || ! $this->isDirectAuthPurpose($peekedToken->purpose)) {
                return redirect($fallback ?? route('home'));
            }

            $purpose = $peekedToken->purpose;
        }

        $token = $telegramAuthService->consumeCode($code, $purpose);

        if (! $token) {
            // Code is expired or already consumed — send to public day page
            // if a safe fallback was provided, otherwise home.
            return redirect($fallback ?? route('home'));
        }

        if ($telegramAuthService->isMemberToken($token)) {
            return $this->authenticateMember($request, $token, $memberSessionService, $telegramAuthService);
        }

        if ($telegramAuthService->isAdminToken($token)) {
            return $this->authenticateAdmin($request, $token, $telegramAuthService);
        }

        return redirect($fallback ?? route('home'));
    }

    /**
     * Resolve a safe same-origin fallback URL from the request.
     * Only accepts URLs on the same host to prevent open-redirect attacks.
     */
    private function resolveFallbackUrl(Request $request): ?string
    {
        $raw = trim((string) $request->query('fallback', ''));
        if ($raw === '') {
            return null;
        }

        // Allow absolute URLs only if they share our host.
        if (preg_match('#^https?://#i', $raw)) {
            $parsed = parse_url($raw);
            $host = $parsed['host'] ?? '';
            if (strcasecmp($host, $request->getHost()) !== 0) {
                return null;
            }

            return $raw;
        }

        // Allow root-relative paths (e.g. /share/day/10/public).
        if (str_starts_with($raw, '/')) {
            return $raw;
        }

        return null;
    }

    /**
     * Embed page for Telegram Web App — shows YouTube video inline so user stays in Telegram.
     * Optional: title (text below player).
     */
    public function embed(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
    {
        $vid = trim((string) $request->query('vid', ''));
        if ($vid === '' || ! preg_match('/^[a-zA-Z0-9_-]{11}$/', $vid)) {
            return redirect()->route('home');
        }

        $title = trim((string) $request->query('title', ''));

        try {
            $html = view('telegram.embed', ['videoId' => $vid, 'title' => $title])->render();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[Telegram embed] Render failed.', [
                'vid' => $vid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Error</title></head>'.
                '<body style="background:#0f172a;color:#f8fafc;font-family:system-ui;padding:24px;text-align:center">'.
                '<p>Unable to load the video. Please try again.</p>'.
                '<a href="'.e(route('home')).'" style="color:#0a6286">Go back</a></body></html>',
                200
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return response($html)
            ->header('Cache-Control', 'public, max-age=300')
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Generic audio player for Telegram Web App (non-YouTube audio).
     */
    public function audioPlayer(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $url = trim((string) $request->query('url', ''));
        $title = trim((string) $request->query('title', ''));

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return redirect()->route('home');
        }

        return view('telegram.audio-player', ['audioUrl' => $url, 'title' => $title]);
    }

    /**
     * Public commemorations page for Telegram Web App (no auth required).
     */
    public function commemorations(\App\Models\DailyContent $daily, \App\Services\EthiopianCalendarService $ethCalendar): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        if (! $daily->is_published || ! $daily->date) {
            return redirect()->route('home');
        }

        $locale = request()->query('lang', app()->getLocale());
        $locale = in_array($locale, ['en', 'am']) ? $locale : 'en';
        app()->setLocale($locale);

        $ethDateInfo = $ethCalendar->getDateInfo($daily->date, $locale);

        return view('telegram.commemorations', compact('daily', 'ethDateInfo', 'locale'));
    }

    /**
     * Mezmur player page with lyrics for Telegram Web App.
     */
    public function mezmurPlayer(Request $request): \Illuminate\View\View|\Illuminate\Http\RedirectResponse
    {
        $mezmurId = (int) $request->query('id', 0);
        $locale = in_array($request->query('lang', 'en'), ['en', 'am']) ? $request->query('lang') : 'en';

        $mezmur = \App\Models\DailyContentMezmur::find($mezmurId);
        if (! $mezmur) {
            return redirect()->route('home');
        }

        $title = localized($mezmur, 'title', $locale) ?? '';
        $description = localized($mezmur, 'description', $locale) ?? '';
        $lyrics = localized($mezmur, 'lyrics', $locale) ?? '';
        $mediaUrl = $mezmur->mediaUrl($locale);
        $videoId = null;

        if ($mediaUrl && preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/', $mediaUrl, $m)) {
            $videoId = $m[1];
        }

        return view('telegram.mezmur-player', compact('mezmur', 'title', 'description', 'lyrics', 'mediaUrl', 'videoId', 'locale'));
    }

    /**
     * Telegram Web App Home — countdown + Go to Today button.
     * Requires valid code (member). Renders minimal HTML with real-time countdown.
     */
    public function webappHome(
        Request $request,
        TelegramAuthService $telegramAuthService
    ): \Illuminate\View\View|\Illuminate\Http\RedirectResponse {
        $code = (string) $request->query('code', '');
        if (trim($code) === '') {
            return redirect()->route('home');
        }

        $purpose = $this->detectPurpose($request->query('purpose'));
        $token = $telegramAuthService->consumeCode($code, $purpose);

        if (! $token || ! $token->actor instanceof Member) {
            return redirect()->route('home');
        }

        $member = $token->actor;
        $locale = in_array($member->locale ?? '', ['en', 'am'], true) ? $member->locale : 'en';
        app()->setLocale($locale);
        Translation::loadFromDb($locale);

        $easterTimezone = config('app.easter_timezone', 'Europe/London');
        $easterAt = Carbon::parse(
            config('app.easter_date', '2026-04-12 03:00'),
            $easterTimezone
        );
        $lentStartAt = Carbon::parse(
            config('app.lent_start_date', '2026-02-15 03:00'),
            $easterTimezone
        );

        $todayUrl = null;
        $viewTodayLabel = __('app.view_today');
        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();

        if ($season) {
            $today = Carbon::today();
            $daily = DailyContent::query()
                ->where('lent_season_id', $season->id)
                ->whereDate('date', $today->toDateString())
                ->where('is_published', true)
                ->first();

            if (! $daily) {
                $baseQuery = fn () => DailyContent::query()
                    ->where('lent_season_id', $season->id)
                    ->where('is_published', true);
                if ($today->lt($season->start_date)) {
                    $daily = ($baseQuery)()->orderBy('day_number')->first();
                } elseif ($today->gt($season->end_date)) {
                    $daily = ($baseQuery)()->orderByDesc('day_number')->first();
                } else {
                    $daily = ($baseQuery)()->where('date', '>=', $today)->orderBy('date')->first()
                        ?? ($baseQuery)()->where('date', '<=', $today)->orderByDesc('date')->first();
                }
            }

            if ($daily) {
                $todayCode = $telegramAuthService->createCode(
                    $member,
                    TelegramAuthService::PURPOSE_MEMBER_ACCESS,
                    $daily->memberDayUrl(),
                    30
                );
                $todayUrl = url(route('auth.access', [
                    'code' => $todayCode,
                    'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
                ]));
            }
        }

        return response()
            ->view('telegram.home', [
                'easterAt' => $easterAt,
                'lentStartAt' => $lentStartAt,
                'todayUrl' => $todayUrl,
                'viewTodayLabel' => $viewTodayLabel,
            ])
            ->header('Cache-Control', 'public, max-age=60');
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
            'telegramAccessUrl' => route('auth.access'),
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
            'access_url' => route('auth.access', [
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
                    ->with('telegram_access_url', route('auth.access', [
                        'code' => $code,
                        'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
                    ]))
                    ->with('telegram_access_expires', $ttl)
                    ->with(
                        'telegram_mini_access_url',
                        'https://t.me/'.$botName.'?startapp='.rawurlencode($payload)
                    );
            }

            return redirect()
                ->back()
                ->with('error', __('app.telegram_bot_username_missing'));
        }

        return redirect()
            ->back()
            ->with('telegram_access_url', route('auth.access', [
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
    ): array {
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

    private function isDirectAuthPurpose(?string $purpose): bool
    {
        return in_array($purpose, [
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            TelegramAuthService::PURPOSE_ADMIN_ACCESS,
        ], true);
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
        return strtolower($mode).':'.$code;
    }

    private function sameActor(?object $first, ?object $second): bool
    {
        if (! $first || ! $second) {
            return false;
        }

        return get_class($first) === get_class($second) && (string) $first->getKey() === (string) $second->getKey();
    }

    /**
     * Authenticate a member via a one-time token and redirect to their target page.
     *
     * Returns a 200 HTML page (not a 302 redirect) so that session cookies are
     * fully stored by the browser before it navigates to the cookie-protected
     * member area.  Some mobile WebViews (notably the WhatsApp in-app browser)
     * do not reliably process Set-Cookie headers from 302 responses on first
     * visit, which caused authenticated users to land back on the home page.
     */
    private function authenticateMember(
        Request $request,
        TelegramAccessToken $token,
        MemberSessionService $memberSessionService,
        TelegramAuthService $telegramAuthService
    ): RedirectResponse|\Illuminate\Http\Response {
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

        // Return a 200 HTML page instead of a 302 redirect.  The queued session
        // cookies are attached to this response by AddQueuedCookiesToResponse
        // middleware.  A short JS delay ensures the browser stores the cookies
        // before navigating to the cookie-protected target page.
        return response()->view('auth.authenticated', ['redirectUrl' => $next]);
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

    /**
     * Show the "Link Telegram" page for the currently logged-in admin/writer/editor.
     * Generates a fresh short code they can type into the Telegram bot.
     */
    public function myTelegramLink(Request $request, TelegramAuthService $telegramAuthService): View
    {
        /** @var User $user */
        $user = $request->user();
        $code = $telegramAuthService->createUserLinkCode($user, 30);
        $botUsername = ltrim((string) config('services.telegram.bot_username', ''), '@');

        return view('admin.telegram-link', compact('code', 'botUsername', 'user'));
    }
}

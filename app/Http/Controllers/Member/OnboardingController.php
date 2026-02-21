<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\MemberSessionService;
use App\Services\WhatsAppReminderConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handles member registration and identification.
 */
class OnboardingController extends Controller
{
    public function __construct(
        private readonly WhatsAppReminderConfirmationService $confirmation,
        private readonly MemberSessionService $sessions,
    ) {}

    /**
     * Show the welcome / onboarding page.
     */
    public function welcome(): View
    {
        $season = LentSeason::active();

        return view('member.welcome', compact('season'));
    }

    /**
     * Register a new member and start WhatsApp opt-in confirmation if requested.
     */
    public function register(Request $request): JsonResponse
    {
        if ($request->exists('whatsapp_phone')) {
            $request->merge([
                'whatsapp_phone' => normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone')),
            ]);
        }

        $validated = $request->validate([
            'baptism_name' => ['required', 'string', 'max:255'],
            'whatsapp_reminder_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'regex:/^\+447\d{9}$/'],
            'whatsapp_language' => ['nullable', 'string', 'in:en,am'],
            'whatsapp_reminder_time' => ['nullable', 'date_format:H:i'],
        ]);

        $reminderRequested = $request->boolean('whatsapp_reminder_enabled');
        $reminderPhone = $validated['whatsapp_phone'] ?? null;
        $reminderTime = $this->normalizeReminderTime($validated['whatsapp_reminder_time'] ?? null);
        $reminderLang = $validated['whatsapp_language'] ?? 'en';

        if ($reminderRequested && (! $reminderPhone || ! $reminderTime)) {
            return response()->json([
                'success' => false,
                'message' => __('app.whatsapp_reminder_requires_phone_and_time'),
            ], 422);
        }

        $memberPayload = [
            'baptism_name' => $validated['baptism_name'],
            'token' => $this->generateUniqueToken(),
            'locale' => app()->getLocale(),
            'theme' => 'light',
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'none',
        ];

        if ($reminderPhone) {
            $memberPayload['whatsapp_phone'] = $reminderPhone;
        }

        if ($reminderTime) {
            $memberPayload['whatsapp_reminder_time'] = $reminderTime;
        }

        if ($reminderRequested) {
            $memberPayload['whatsapp_language'] = $reminderLang;
            $memberPayload['whatsapp_confirmation_status'] = 'pending';
            $memberPayload['whatsapp_confirmation_requested_at'] = now();
        }

        $member = Member::create($memberPayload);

        // Bind and persist this browser as the trusted member device.
        if (! $this->sessions->establishSession($member, $request)) {
            return response()->json([
                'success' => false,
                'message' => __('app.failed'),
            ], 500);
        }

        $promptSent = false;
        $confirmationPending = $reminderRequested;
        if ($reminderRequested && $reminderPhone) {
            $promptSent = $this->confirmation->sendOptInPrompt($member);
        }

        return response()->json([
            'success' => true,
            'redirect_url' => route('member.home'),
            'whatsapp_confirmation_pending' => $confirmationPending,
            'whatsapp_confirmation_prompt_sent' => $promptSent,
            'message' => $confirmationPending
                ? ($promptSent
                    ? __('app.whatsapp_confirmation_pending_notice')
                    : __('app.whatsapp_confirmation_send_failed_notice'))
                : null,
            'member' => [
                'id' => $member->id,
                'baptism_name' => $member->baptism_name,
                'passcode_enabled' => $member->passcode_enabled,
                'whatsapp_reminder_enabled' => $member->whatsapp_reminder_enabled,
                'whatsapp_phone' => $member->whatsapp_phone,
                'whatsapp_reminder_time' => $member->whatsapp_reminder_time,
                'whatsapp_language' => $member->whatsapp_language,
                'whatsapp_confirmation_status' => $member->whatsapp_confirmation_status,
            ],
        ]);
    }

    /**
     * Identify the currently authenticated member session.
     */
    public function identify(Request $request): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('member');

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'member' => [
                'id' => $member->id,
                'baptism_name' => $member->baptism_name,
                'passcode_enabled' => $member->passcode_enabled,
                'locale' => $member->locale,
                'theme' => $member->theme,
                'whatsapp_reminder_enabled' => $member->whatsapp_reminder_enabled,
                'whatsapp_phone' => $member->whatsapp_phone,
                'whatsapp_reminder_time' => $member->whatsapp_reminder_time,
                'whatsapp_language' => $member->whatsapp_language,
                'whatsapp_confirmation_status' => $member->whatsapp_confirmation_status,
            ],
        ]);
    }

    /**
     * Authenticate from member access token and attach secure device session.
     */
    public function access(Request $request, string $token): RedirectResponse
    {
        $next = $this->sanitizeNextPath((string) $request->query('next', '/member/home'));

        if (! preg_match('/^[A-Za-z0-9]{20,128}$/', $token)) {
            return redirect('/');
        }

        $member = Member::where('token', $token)->first();
        if (! $member) {
            return redirect('/');
        }

        if (! $this->sessions->establishSession($member, $request)) {
            $fallback = $this->fallbackUrlForNext($next);

            return redirect($fallback ?? '/');
        }

        session()->forget("member_unlocked_{$member->id}");

        if ($member->passcode_enabled) {
            return redirect()->route('member.passcode');
        }

        return redirect($next);
    }

    private function normalizeReminderTime(?string $time): ?string
    {
        if (! is_string($time) || trim($time) === '') {
            return null;
        }

        return $time.':00';
    }

    private function generateUniqueToken(): string
    {
        $token = Str::random(64);
        while (Member::where('token', $token)->exists()) {
            $token = Str::random(64);
        }

        return $token;
    }

    private function sanitizeNextPath(string $next): string
    {
        $next = trim($next);
        if ($next === '') {
            return '/member/home';
        }

        if (preg_match('#^https?://#i', $next) === 1) {
            $parsed = parse_url($next);
            if (! is_array($parsed)) {
                return '/member/home';
            }

            $host = $parsed['host'] ?? null;
            if (! is_string($host) || strcasecmp($host, request()->getHost()) !== 0) {
                return '/member/home';
            }

            $path = $parsed['path'] ?? '';
            $query = $parsed['query'] ?? '';
            $next = (string) $path;
            if (is_string($query) && $query !== '') {
                $next .= '?'.$query;
            }
        }

        if (str_starts_with($next, 'member/')) {
            $next = '/'.$next;
        }

        if (str_starts_with($next, '/member/')) {
            return $next;
        }

        return '/member/home';
    }

    private function fallbackUrlForNext(string $next): ?string
    {
        $path = parse_url($next, PHP_URL_PATH);
        if (! is_string($path) || trim($path) === '') {
            $path = $next;
        }

        $path = '/'.ltrim($path, '/');

        if (preg_match('#^/member/day/(\\d+)$#', $path, $matches)) {
            return route('share.day.public', ['daily' => (int) $matches[1]]);
        }

        return null;
    }
}

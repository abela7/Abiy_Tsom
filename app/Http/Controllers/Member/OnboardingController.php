<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\Member;
use App\Services\UltraMsgService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Handles member registration and identification.
 */
class OnboardingController extends Controller
{
    public function __construct(private readonly UltraMsgService $ultraMsg) {}

    /**
     * Show the welcome / onboarding page.
     */
    public function welcome(): View
    {
        $season = LentSeason::active();

        return view('member.welcome', compact('season'));
    }

    /**
     * Register a new member — returns a unique token, then fires
     * a WhatsApp confirmation message if the member opted in.
     */
    public function register(Request $request): JsonResponse
    {
        if ($request->exists('whatsapp_phone')) {
            $request->merge([
                'whatsapp_phone' => normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone')),
            ]);
        }

        $validated = $request->validate([
            'baptism_name'             => ['required', 'string', 'max:255'],
            'whatsapp_reminder_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone'           => ['nullable', 'string', 'regex:/^\+447\d{9}$/'],
            'whatsapp_language'        => ['nullable', 'string', 'in:en,am'],
            'whatsapp_reminder_time'   => ['nullable', 'date_format:H:i'],
        ]);

        $reminderEnabled = $request->boolean('whatsapp_reminder_enabled');
        $reminderPhone   = $validated['whatsapp_phone'] ?? null;
        $reminderTime    = $this->normalizeReminderTime($validated['whatsapp_reminder_time'] ?? null);
        $reminderLang    = $validated['whatsapp_language'] ?? 'en';

        if ($reminderEnabled && (! $reminderPhone || ! $reminderTime)) {
            return response()->json([
                'success' => false,
                'message' => __('app.whatsapp_reminder_requires_phone_and_time'),
            ], 422);
        }

        $token = Str::random(64);
        while (Member::where('token', $token)->exists()) {
            $token = Str::random(64);
        }

        $memberPayload = [
            'baptism_name'             => $validated['baptism_name'],
            'token'                    => $token,
            'locale'                   => app()->getLocale(),
            'theme'                    => 'light',
            'whatsapp_reminder_enabled' => $reminderEnabled,
        ];

        if ($reminderPhone) {
            $memberPayload['whatsapp_phone'] = $reminderPhone;
        }

        if ($reminderTime) {
            $memberPayload['whatsapp_reminder_time'] = $reminderTime;
        }

        if ($reminderEnabled) {
            $memberPayload['whatsapp_language'] = $reminderLang;
        }

        $member = Member::create($memberPayload);

        // Send WhatsApp confirmation immediately after registration.
        if ($reminderEnabled && $reminderPhone) {
            $this->sendWelcomeMessage($member, $reminderLang);
        }

        return response()->json([
            'success' => true,
            'token'   => $member->token,
            'member'  => [
                'id'                       => $member->id,
                'baptism_name'             => $member->baptism_name,
                'whatsapp_reminder_enabled' => $member->whatsapp_reminder_enabled,
                'whatsapp_phone'           => $member->whatsapp_phone,
                'whatsapp_reminder_time'   => $member->whatsapp_reminder_time,
                'whatsapp_language'        => $member->whatsapp_language,
            ],
        ]);
    }

    /**
     * Identify an existing member by their token.
     */
    public function identify(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string', 'size:64'],
        ]);

        $member = Member::where('token', $request->input('token'))->first();

        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Member not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'member'  => [
                'id'                       => $member->id,
                'baptism_name'             => $member->baptism_name,
                'passcode_enabled'         => $member->passcode_enabled,
                'locale'                   => $member->locale,
                'theme'                    => $member->theme,
                'whatsapp_reminder_enabled' => $member->whatsapp_reminder_enabled,
                'whatsapp_phone'           => $member->whatsapp_phone,
                'whatsapp_reminder_time'   => $member->whatsapp_reminder_time,
                'whatsapp_language'        => $member->whatsapp_language,
            ],
        ]);
    }

    /**
     * Send the welcome / confirmation WhatsApp message to the member.
     * Failures are logged but never bubble up — registration already succeeded.
     */
    private function sendWelcomeMessage(Member $member, string $lang): void
    {
        if (! $this->ultraMsg->isConfigured()) {
            Log::info('UltraMsg not configured — skipping welcome message.', [
                'member_id' => $member->id,
            ]);

            return;
        }

        try {
            // Pick the correct template key based on chosen language.
            $messageKey = $lang === 'am'
                ? 'app.whatsapp_welcome_message_am'
                : 'app.whatsapp_welcome_message_en';

            $body = __($messageKey, ['name' => $member->baptism_name]);

            $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $body);
        } catch (\Throwable $e) {
            Log::warning('Failed to send WhatsApp welcome message.', [
                'member_id' => $member->id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function normalizeReminderTime(?string $time): ?string
    {
        if (! is_string($time) || trim($time) === '') {
            return null;
        }

        return $time.':00';
    }
}

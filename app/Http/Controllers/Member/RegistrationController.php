<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Handles new member registration with phone/email verification.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
    ) {}

    /**
     * Step 1: Create unverified member and send verification code.
     */
    public function register(Request $request): JsonResponse
    {
        // Normalize UK phone if present.
        if ($request->exists('phone')) {
            $raw = (string) $request->input('phone');
            $normalized = normalizeUkWhatsAppPhone($raw);
            if ($normalized !== null) {
                $request->merge(['phone' => $normalized]);
            }
        }

        $validated = $request->validate([
            'baptism_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
            'locale' => ['nullable', 'string', 'in:en,am'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $phone = $validated['phone'];
        $isUk = $this->verification->isUkPhone($phone);

        // Non-UK users must provide an email for verification.
        if (! $isUk && empty($validated['email'])) {
            return response()->json([
                'success' => false,
                'requires_email' => true,
                'message' => __('app.registration_email_required'),
            ], 422);
        }

        $member = Member::create([
            'baptism_name' => $validated['baptism_name'],
            'token' => $this->generateUniqueToken(),
            'locale' => $validated['locale'] ?? app()->getLocale(),
            'theme' => 'sepia',
            'whatsapp_phone' => $phone,
            'whatsapp_language' => $validated['locale'] ?? 'en',
            'email' => $validated['email'] ?? null,
            // UK users get WhatsApp reminders enabled after verification.
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'none',
        ]);

        // Attribute referral if cookie exists.
        $refCode = $request->cookie('ref');
        if (is_string($refCode) && preg_match('/^[a-z0-9]{8}$/', $refCode)) {
            $referrer = \App\Models\User::where('referral_code', $refCode)->first();
            if ($referrer) {
                $member->update(['referred_by' => $referrer->id]);
            }
        }

        $codeSent = $this->verification->sendCode($member);

        return response()->json([
            'success' => true,
            'verification_pending' => true,
            'channel' => $isUk ? 'whatsapp' : 'email',
            'code_sent' => $codeSent,
            'member_phone' => maskPhone($phone),
            'member_email' => $validated['email'] ?? null,
            'message' => $codeSent
                ? __('app.verification_code_sent')
                : __('app.verification_code_send_failed'),
        ]);
    }

    /**
     * Step 2: Validate the 6-digit verification code.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        $member = Member::where('whatsapp_phone', $validated['phone'])
            ->whereNull('phone_verified_at')
            ->whereNull('email_verified_at')
            ->latest()
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => __('app.verification_member_not_found'),
            ], 404);
        }

        if (! $this->verification->validateCode($member, $validated['code'])) {
            return response()->json([
                'success' => false,
                'message' => __('app.verification_code_invalid'),
            ], 422);
        }

        // Mark as verified.
        $isUk = $this->verification->isUkPhone($member->whatsapp_phone);
        $member->update(array_filter([
            'phone_verified_at' => $isUk ? now() : null,
            'email_verified_at' => ! $isUk ? now() : null,
            'whatsapp_reminder_enabled' => $isUk,
            'whatsapp_confirmation_status' => $isUk ? 'confirmed' : 'none',
            'whatsapp_confirmation_responded_at' => $isUk ? now() : null,
        ], fn ($v) => $v !== null));

        $this->verification->clearCode($member);

        return response()->json([
            'success' => true,
            'redirect_url' => $member->personalUrl('/home'),
        ]);
    }

    /**
     * Resend the verification code.
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
        ]);

        $member = Member::where('whatsapp_phone', $validated['phone'])
            ->whereNull('phone_verified_at')
            ->whereNull('email_verified_at')
            ->latest()
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => __('app.verification_member_not_found'),
            ], 404);
        }

        $codeSent = $this->verification->sendCode($member);

        return response()->json([
            'success' => true,
            'code_sent' => $codeSent,
            'message' => $codeSent
                ? __('app.verification_code_sent')
                : __('app.verification_code_send_failed'),
        ]);
    }

    private function generateUniqueToken(): string
    {
        $token = Str::random(64);
        while (Member::where('token', $token)->exists()) {
            $token = Str::random(64);
        }

        return $token;
    }
}

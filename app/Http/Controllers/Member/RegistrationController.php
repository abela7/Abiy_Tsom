<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\VerificationService;
use App\Services\WhatsAppReminderConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Handles new member registration.
 *
 * UK users: WhatsApp YES/NO confirmation (starts conversation for clickable links).
 * Non-UK users: email verification code.
 */
class RegistrationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
        private readonly WhatsAppReminderConfirmationService $confirmation,
    ) {}

    /**
     * Step 1: Create member and send confirmation/verification.
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

        // Reuse an existing unverified member with the same phone to avoid duplicates.
        $member = Member::where('whatsapp_phone', $phone)
            ->whereNull('phone_verified_at')
            ->whereNull('email_verified_at')
            ->where('whatsapp_confirmation_status', '!=', 'confirmed')
            ->latest()
            ->first();

        if ($member) {
            $member->update([
                'baptism_name' => $validated['baptism_name'],
                'locale' => $validated['locale'] ?? app()->getLocale(),
                'whatsapp_language' => $validated['locale'] ?? 'en',
                'email' => $validated['email'] ?? null,
                'whatsapp_confirmation_status' => $isUk ? 'pending' : 'none',
                'whatsapp_confirmation_requested_at' => $isUk ? now() : null,
            ]);
        } else {
            $member = Member::create([
                'baptism_name' => $validated['baptism_name'],
                'token' => $this->generateUniqueToken(),
                'locale' => $validated['locale'] ?? app()->getLocale(),
                'theme' => 'sepia',
                'whatsapp_phone' => $phone,
                'whatsapp_language' => $validated['locale'] ?? 'en',
                'email' => $validated['email'] ?? null,
                'whatsapp_reminder_enabled' => false,
                'whatsapp_confirmation_status' => $isUk ? 'pending' : 'none',
                'whatsapp_confirmation_requested_at' => $isUk ? now() : null,
            ]);
        }

        // Attribute referral if cookie exists.
        $refCode = $request->cookie('ref');
        if (is_string($refCode) && preg_match('/^[a-z0-9]{8}$/', $refCode)) {
            $referrer = \App\Models\User::where('referral_code', $refCode)->first();
            if ($referrer) {
                $member->update(['referred_by' => $referrer->id]);
            }
        }

        if ($isUk) {
            // Send "Reply YES to confirm" on WhatsApp.
            $promptSent = $this->confirmation->sendOptInPrompt($member);

            Log::info('[Registration] UK WhatsApp prompt attempt', [
                'member_id' => $member->id,
                'phone' => $phone,
                'prompt_sent' => $promptSent,
            ]);

            if (! $promptSent) {
                // Clean up the member record — don't leave orphaned pending members.
                $member->delete();

                return response()->json([
                    'success' => false,
                    'message' => __('app.verification_code_send_failed'),
                ], 502);
            }

            return response()->json([
                'success' => true,
                'verification_pending' => true,
                'channel' => 'whatsapp',
                'code_sent' => true,
                'member_phone' => maskPhone($phone),
                'message' => __('app.registration_whatsapp_prompt_sent'),
            ]);
        }

        // Non-UK: send email verification code.
        $codeSent = $this->verification->sendCode($member);

        Log::info('[Registration] Non-UK email code attempt', [
            'member_id' => $member->id,
            'code_sent' => $codeSent,
        ]);

        if (! $codeSent) {
            $member->delete();

            return response()->json([
                'success' => false,
                'message' => __('app.verification_code_send_failed'),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'verification_pending' => true,
            'channel' => 'email',
            'code_sent' => true,
            'member_phone' => maskPhone($phone),
            'member_email' => $validated['email'] ?? null,
            'message' => __('app.verification_code_sent'),
        ]);
    }

    /**
     * Step 2: Validate 6-digit verification code (email users only).
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

        // Mark as verified (email flow).
        $member->update([
            'email_verified_at' => now(),
        ]);

        $this->verification->clearCode($member);

        return response()->json([
            'success' => true,
            'redirect_url' => $member->personalUrl('/home'),
        ]);
    }

    /**
     * Poll: check if a member has been confirmed via WhatsApp YES reply.
     */
    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
        ]);

        $member = Member::where('whatsapp_phone', $validated['phone'])
            ->latest()
            ->first();

        if (! $member) {
            return response()->json(['status' => 'not_found']);
        }

        if ($member->whatsapp_confirmation_status === 'confirmed') {
            return response()->json([
                'status' => 'confirmed',
                'redirect_url' => $member->personalUrl('/home'),
            ]);
        }

        if ($member->whatsapp_confirmation_status === 'rejected') {
            return response()->json([
                'status' => 'rejected',
            ]);
        }

        // Still pending or email-verified.
        $isVerified = $member->phone_verified_at !== null || $member->email_verified_at !== null;

        if ($isVerified) {
            return response()->json([
                'status' => 'confirmed',
                'redirect_url' => $member->personalUrl('/home'),
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    /**
     * Resend: re-send WhatsApp prompt (UK) or email code (non-UK).
     */
    public function resend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^\+\d{7,15}$/'],
        ]);

        $member = Member::where('whatsapp_phone', $validated['phone'])
            ->latest()
            ->first();

        if (! $member) {
            return response()->json([
                'success' => false,
                'message' => __('app.verification_member_not_found'),
            ], 404);
        }

        $isUk = $this->verification->isUkPhone($member->whatsapp_phone);

        if ($isUk) {
            // Re-send the YES/NO prompt.
            $member->update([
                'whatsapp_confirmation_status' => 'pending',
                'whatsapp_confirmation_requested_at' => now(),
            ]);
            $sent = $this->confirmation->sendOptInPrompt($member);
        } else {
            $sent = $this->verification->sendCode($member);
        }

        return response()->json([
            'success' => true,
            'code_sent' => $sent,
            'message' => $sent
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

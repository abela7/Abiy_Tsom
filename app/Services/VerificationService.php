<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends and validates 6-digit verification codes via WhatsApp or email.
 */
final class VerificationService
{
    public function __construct(
        private readonly UltraMsgService $ultraMsg,
    ) {}

    /**
     * Generate a 6-digit code, store its hash, and send it to the member.
     */
    public function sendCode(Member $member): bool
    {
        $code = $this->generateCode();

        $member->update([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(10),
        ]);

        if ($this->isUkPhone($member->whatsapp_phone)) {
            return $this->sendViaWhatsApp($member, $code);
        }

        if ($member->email) {
            return $this->sendViaEmail($member, $code);
        }

        Log::warning('VerificationService: no channel available.', ['member_id' => $member->id]);

        return false;
    }

    /**
     * Validate a code against the stored hash and expiry.
     */
    public function validateCode(Member $member, string $code): bool
    {
        if (! $member->verification_code || ! $member->verification_code_expires_at) {
            return false;
        }

        if ($member->verification_code_expires_at->isPast()) {
            return false;
        }

        return Hash::check($code, $member->verification_code);
    }

    /**
     * Clear the stored verification code after successful validation.
     */
    public function clearCode(Member $member): void
    {
        $member->update([
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ]);
    }

    /**
     * Check if a phone number is a UK mobile number.
     */
    public function isUkPhone(?string $phone): bool
    {
        return is_string($phone) && (bool) preg_match('/^\+447\d{9}$/', $phone);
    }

    private function sendViaWhatsApp(Member $member, string $code): bool
    {
        $message = __('app.verification_code_whatsapp', ['code' => $code]);

        if (! $this->ultraMsg->isConfigured()) {
            Log::warning('VerificationService: UltraMsg not configured.');

            return false;
        }

        return $this->ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message);
    }

    private function sendViaEmail(Member $member, string $code): bool
    {
        try {
            Mail::to($member->email)->send(new VerificationCodeMail($code, $member->baptism_name));

            return true;
        } catch (\Throwable $e) {
            Log::warning('VerificationService: email send failed.', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}

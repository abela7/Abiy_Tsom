<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\MemberVerificationCode;
use App\Services\MemberSessionService;
use App\Services\PersistentLoginService;
use App\Services\UltraMsgService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PersistentAuthController extends Controller
{
    public function bridge(Request $request): View
    {
        return view('member.restore-session', [
            'restoreUrl' => route('member.auth.restore'),
            'storageKey' => PersistentLoginService::STORAGE_KEY,
            'redirectUrl' => $this->sanitizeRedirect($request->query('redirect')),
            'homeUrl' => route('home'),
        ]);
    }

    public function restore(
        Request $request,
        PersistentLoginService $persistentLogins,
        MemberSessionService $sessions
    ): JsonResponse {
        $request->validate([
            'remember_token' => ['required', 'string'],
            'expected_member_token' => ['nullable', 'string', 'regex:/^[A-Za-z0-9]{64}$/'],
        ]);

        $device = $persistentLogins->resolvePayload($request->input('remember_token'));
        if (! $device || ! $device->member) {
            return response()->json([
                'success' => false,
                'message' => __('app.member_restore_failed'),
            ], 401);
        }

        $expectedToken = $request->input('expected_member_token');
        if (is_string($expectedToken) && $expectedToken !== '' && ! hash_equals((string) $device->member->token, $expectedToken)) {
            return response()->json([
                'success' => false,
                'member_mismatch' => true,
                'message' => __('app.member_restore_failed'),
            ], 409);
        }

        $request->session()->regenerate();
        $sessions->establishSession($device->member, $request);
        $persistentLogins->touch($device, $request, (string) $request->input('remember_token'));

        return response()->json([
            'success' => true,
            'remember_token' => (string) $request->input('remember_token'),
        ]);
    }

    public function sendVerificationCode(Request $request, UltraMsgService $ultraMsgService): JsonResponse
    {
        /** @var Member|null $member */
        $member = $request->attributes->get('member');
        if (! $member) {
            return response()->json(['success' => false, 'message' => __('app.failed')], 404);
        }

        if (! $member->whatsapp_phone) {
            return response()->json([
                'success' => false,
                'message' => __('app.member_guest_no_whatsapp'),
            ], 422);
        }

        $recentCodeExists = MemberVerificationCode::query()
            ->where('member_id', $member->id)
            ->whereNull('used_at')
            ->where('created_at', '>=', now()->subSeconds(45))
            ->exists();

        if ($recentCodeExists) {
            return response()->json([
                'success' => false,
                'message' => __('app.member_verification_rate_limited'),
            ], 429);
        }

        MemberVerificationCode::query()
            ->where('member_id', $member->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minutes = max(5, (int) config('session.member_verification_code_minutes', 10));

        $verification = MemberVerificationCode::create([
            'member_id' => $member->id,
            'phone' => (string) $member->whatsapp_phone,
            'code_hash' => hash('sha256', $code),
            'device_hash' => hash('sha256', (string) $request->ip().'|'.((string) $request->userAgent())),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        $message = __('app.member_verification_code_message', [
            'code' => $code,
            'minutes' => $minutes,
        ]);

        if (! $ultraMsgService->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            $verification->delete();

            return response()->json([
                'success' => false,
                'message' => __('app.member_verification_code_send_failed'),
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => __('app.member_verification_code_sent', [
                'phone' => $this->maskPhone((string) $member->whatsapp_phone),
            ]),
            'masked_phone' => $this->maskPhone((string) $member->whatsapp_phone),
        ]);
    }

    public function verifyCode(
        Request $request,
        PersistentLoginService $persistentLogins,
        MemberSessionService $sessions
    ): JsonResponse {
        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
            'redirect_to' => ['nullable', 'string'],
        ]);

        /** @var Member|null $member */
        $member = $request->attributes->get('member');
        if (! $member) {
            return response()->json(['success' => false, 'message' => __('app.failed')], 404);
        }

        $verification = MemberVerificationCode::query()
            ->where('member_id', $member->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $verification) {
            return response()->json([
                'success' => false,
                'message' => __('app.member_verification_invalid_code'),
            ], 422);
        }

        if ($verification->attempts >= 5) {
            $verification->forceFill(['used_at' => now()])->save();

            return response()->json([
                'success' => false,
                'message' => __('app.member_verification_invalid_code'),
            ], 429);
        }

        if (! hash_equals($verification->code_hash, hash('sha256', (string) $request->input('code')))) {
            $verification->forceFill([
                'attempts' => $verification->attempts + 1,
            ])->save();

            return response()->json([
                'success' => false,
                'message' => __('app.member_verification_invalid_code'),
            ], 422);
        }

        $verification->forceFill([
            'used_at' => now(),
        ])->save();

        $request->session()->regenerate();
        $sessions->establishSession($member, $request);
        $rememberToken = $persistentLogins->issue($member, $request);

        return response()->json([
            'success' => true,
            'message' => __('app.member_verification_success'),
            'remember_token' => $rememberToken,
            'redirect_url' => $this->sanitizeRedirect($request->input('redirect_to')),
        ]);
    }

    private function sanitizeRedirect(mixed $redirect): string
    {
        $fallback = url('/member/home');

        if (! is_string($redirect)) {
            return $fallback;
        }

        $normalized = trim($redirect);
        if ($normalized === '') {
            return $fallback;
        }

        if (Str::startsWith($normalized, url('/'))) {
            return $normalized;
        }

        if (Str::startsWith($normalized, '/')) {
            return url($normalized);
        }

        return $fallback;
    }

    private function maskPhone(string $phone): string
    {
        if ($phone === '') {
            return '';
        }

        $visiblePrefix = mb_substr($phone, 0, 4);
        $visibleSuffix = mb_substr($phone, -4);
        $maskedLength = max(0, mb_strlen($phone) - 8);

        return $visiblePrefix.str_repeat('*', $maskedLength).$visibleSuffix;
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\TelegramAccessToken;
use App\Services\MemberSessionService;
use App\Services\TelegramAuthService;
use App\Services\WhatsAppReminderConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Member settings — theme, language, identity, and WhatsApp reminders.
 */
class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $member = $request->attributes->get('member');
        $customActivities = $member
            ? $member->customActivities()->orderBy('sort_order')->get()
            : collect();

        $botUsername = ltrim((string) config('services.telegram.bot_username', ''), '@');
        $telegramBotUrl = $botUsername ? 'https://t.me/' . $botUsername : null;

        return view('member.settings', compact('member', 'customActivities', 'telegramBotUrl'));
    }

    /**
     * Confirm member identity — validates phone or email, sets session flag.
     */
    public function confirmIdentity(Request $request): JsonResponse
    {
        $request->validate([
            'confirm_identity' => ['required', 'string'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');
        $input = mb_strtolower(trim($request->input('confirm_identity')));

        $matches = false;
        if ($member->whatsapp_phone && mb_strtolower($member->whatsapp_phone) === $input) {
            $matches = true;
        }
        if ($member->email && mb_strtolower($member->email) === $input) {
            $matches = true;
        }

        if (! $matches) {
            return response()->json([
                'success' => false,
                'message' => __('app.identity_confirmation_failed'),
            ], 422);
        }

        \App\Http\Middleware\RequireMemberIdentityConfirmation::confirm();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Update member preferences (theme, locale).
     */
    public function update(
        Request $request,
        WhatsAppReminderConfirmationService $confirmation
    ): JsonResponse {
        if ($request->exists('whatsapp_phone')) {
            $request->merge([
                'whatsapp_phone' => normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone')),
            ]);
        }

        $request->validate([
            'locale' => ['nullable', 'string', 'in:en,am'],
            'theme' => ['nullable', 'string', 'in:light,dark,sepia'],
            'baptism_name' => ['nullable', 'string', 'min:1', 'max:255'],
            'whatsapp_reminder_enabled' => ['nullable', 'boolean'],
            'whatsapp_phone' => ['nullable', 'string', 'regex:/^\+447\d{9}$/'],
            'whatsapp_reminder_time' => ['nullable', 'date_format:H:i'],
            'whatsapp_language' => ['nullable', 'string', 'in:en,am'],
            'email_reminder_enabled' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $updates = [];
        $shouldSendPrompt = false;
        if ($request->filled('locale')) {
            $updates['locale'] = $request->input('locale');
            session(['locale' => $request->input('locale')]);
        }
        if ($request->filled('theme')) {
            $updates['theme'] = $request->input('theme');
        }
        if ($request->filled('baptism_name')) {
            $trimmed = trim($request->input('baptism_name'));
            if ($trimmed !== '') {
                $updates['baptism_name'] = $trimmed;
            }
        }

        $hasReminderPayload = $request->exists('whatsapp_reminder_enabled')
            || $request->exists('whatsapp_phone')
            || $request->exists('whatsapp_reminder_time')
            || $request->exists('whatsapp_language');

        if ($hasReminderPayload) {
            $nextEnabled = $request->exists('whatsapp_reminder_enabled')
                ? $request->boolean('whatsapp_reminder_enabled')
                : (bool) $member->whatsapp_reminder_enabled;

            $nextPhone = $request->exists('whatsapp_phone')
                ? normalizeUkWhatsAppPhone((string) $request->input('whatsapp_phone'))
                : $member->whatsapp_phone;

            $nextTime = $request->exists('whatsapp_reminder_time')
                ? $this->normalizeReminderTime($request->input('whatsapp_reminder_time'))
                : $member->whatsapp_reminder_time;

            $nextLang = $request->exists('whatsapp_language')
                ? (string) $request->input('whatsapp_language', 'en')
                : (string) ($member->whatsapp_language ?? 'en');

            if ($nextEnabled && (! $nextPhone || ! $nextTime)) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.whatsapp_reminder_requires_phone_and_time'),
                ], 422);
            }

            if ($request->exists('whatsapp_phone')) {
                $updates['whatsapp_phone'] = $nextPhone;
            }

            if ($request->exists('whatsapp_reminder_time')) {
                $updates['whatsapp_reminder_time'] = $nextTime;
            }

            if ($request->exists('whatsapp_language')) {
                $updates['whatsapp_language'] = $nextLang;
            }

            $phoneChanged = $request->exists('whatsapp_phone')
                && $nextPhone !== $member->whatsapp_phone;

            $requiresConfirmation = $nextEnabled
                && (
                    $member->whatsapp_confirmation_status !== 'confirmed'
                    || $phoneChanged
                );

            if ($requiresConfirmation) {
                $shouldSendPrompt = true;
                $updates['whatsapp_reminder_enabled'] = false;
                $updates['whatsapp_confirmation_status'] = 'pending';
                $updates['whatsapp_confirmation_requested_at'] = now();
                $updates['whatsapp_confirmation_responded_at'] = null;
                $updates['whatsapp_last_sent_date'] = null;
            } elseif ($request->exists('whatsapp_reminder_enabled')) {
                $updates['whatsapp_reminder_enabled'] = $nextEnabled;

                if (! $nextEnabled) {
                    $updates['whatsapp_last_sent_date'] = null;
                    $updates['whatsapp_confirmation_status'] = 'none';
                    $updates['whatsapp_confirmation_requested_at'] = null;
                    $updates['whatsapp_confirmation_responded_at'] = null;
                }
            }
        }

        if ($request->exists('email_reminder_enabled')) {
            $canEnable = $member->email && $member->email_verified_at;
            $updates['email_reminder_enabled'] = $canEnable && $request->boolean('email_reminder_enabled');
        }

        if (! empty($updates)) {
            $member->update($updates);
        }

        $freshMember = $member->fresh();
        $pending = $freshMember?->whatsapp_confirmation_status === 'pending';
        $promptSent = true;

        if ($shouldSendPrompt && $freshMember) {
            $promptSent = $confirmation->sendOptInPrompt($freshMember);
        }

        return response()->json([
            'success' => true,
            'whatsapp_confirmation_pending' => $pending,
            'whatsapp_confirmation_prompt_sent' => $shouldSendPrompt ? $promptSent : null,
            'message' => $shouldSendPrompt
                ? ($promptSent
                    ? __('app.whatsapp_confirmation_pending_notice')
                    : __('app.whatsapp_confirmation_send_failed_notice'))
                : null,
            'member' => $freshMember,
        ]);
    }

    /**
     * Generate a one-time Telegram link for the current member to link their account.
     */
    public function generateTelegramLink(Request $request, TelegramAuthService $telegramAuthService): JsonResponse
    {
        $botUsername = ltrim((string) config('services.telegram.bot_username', ''), '@');
        if ($botUsername === '') {
            return response()->json([
                'success' => false,
                'message' => __('app.telegram_bot_username_missing'),
            ], 503);
        }

        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        [$fullToken, $shortCode] = $telegramAuthService->createMemberLinkCode(
            $member,
            route('member.home'),
            30
        );

        $payload = 'member:' . $fullToken;
        $link = 'https://t.me/' . $botUsername . '?start=' . rawurlencode($payload);

        return response()->json([
            'success' => true,
            'link' => $link,
            'code' => $shortCode,
            'expires_in' => 30,
            'message' => __('app.telegram_settings_link_generated'),
        ]);
    }

    /**
     * Unlink Telegram from the current member's account.
     */
    public function unlinkTelegram(Request $request): JsonResponse
    {
        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        $member->forceFill(['telegram_chat_id' => null])->save();

        return response()->json([
            'success' => true,
            'message' => __('app.telegram_settings_unlinked'),
        ]);
    }

    /**
     * Delete the member account and all associated data, then log out.
     */
    public function deleteAccount(Request $request, MemberSessionService $sessionService): JsonResponse
    {
        /** @var \App\Models\Member $member */
        $member = $request->attributes->get('member');

        try {
            DB::transaction(function () use ($member, $sessionService): void {
                TelegramAccessToken::query()
                    ->where('actor_type', get_class($member))
                    ->where('actor_id', $member->getKey())
                    ->delete();

                // Delete all related data (most children also cascade on member delete).
                $member->checklists()->delete();
                $member->customChecklists()->delete();
                $member->customActivities()->delete();

                // Revoke every session for this member.
                $sessionService->revokeAllMemberSessions($member);

                // Delete the member record itself.
                $member->delete();
            });

            $sessionService->forgetCookies();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('app.failed'),
            ], 500);
        }
    }

    private function normalizeReminderTime(mixed $time): ?string
    {
        if (! is_string($time)) {
            return null;
        }

        $trimmed = trim($time);
        if ($trimmed === '') {
            return null;
        }

        return $trimmed.':00';
    }
}

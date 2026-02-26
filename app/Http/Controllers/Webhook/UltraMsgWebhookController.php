<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\WhatsAppReminderConfirmationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives inbound WhatsApp webhooks from UltraMsg.
 */
class UltraMsgWebhookController extends Controller
{
    public function handle(Request $request, WhatsAppReminderConfirmationService $confirmation): JsonResponse
    {
        Log::info('[Webhook] Incoming request', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        if (! $this->verifySecret($request)) {
            Log::warning('[Webhook] Secret mismatch — rejected');

            return response()->json(['error' => 'unauthorized'], 401);
        }

        $payload = $request->all();

        if ($this->isFromSelf($payload)) {
            Log::info('[Webhook] Ignored: outgoing message');

            return response()->json(['success' => true, 'ignored' => 'outgoing']);
        }

        $phone = $this->extractPhone($payload);
        $messageText = $this->extractMessageText($payload);

        Log::info('[Webhook] Parsed', [
            'phone' => $phone,
            'text' => $messageText,
        ]);

        if (! $phone || ! $messageText) {
            Log::info('[Webhook] Ignored: missing phone or text');

            return response()->json(['success' => true, 'ignored' => 'missing_phone_or_text']);
        }

        $reply = $confirmation->parseReply($messageText);

        $member = Member::query()
            ->where('whatsapp_phone', $phone)
            ->where('whatsapp_confirmation_status', 'pending')
            ->orderByDesc('whatsapp_confirmation_requested_at')
            ->first();

        if (! $reply) {
            if ($member) {
                Log::info('[Webhook] Invalid reply — re-sending prompt', [
                    'member_id' => $member->id,
                    'text' => $messageText,
                ]);
                $confirmation->sendInvalidReplyPrompt($member);

                return response()->json(['success' => true, 'action' => 'invalid_reply_re_prompted']);
            }

            Log::info('[Webhook] Ignored: not a YES/NO reply and no pending member', [
                'text' => $messageText,
            ]);

            return response()->json(['success' => true, 'ignored' => 'not_yes_no']);
        }

        if (! $member) {
            Log::info('[Webhook] Ignored: no pending member for phone', ['phone' => $phone]);

            return response()->json(['success' => true, 'ignored' => 'no_pending_member']);
        }

        Log::info('[Webhook] Processing reply', [
            'member_id' => $member->id,
            'reply' => $reply,
        ]);

        if ($reply === 'yes') {
            $member->forceFill([
                'whatsapp_reminder_enabled' => true,
                'whatsapp_confirmation_status' => 'confirmed',
                'whatsapp_confirmation_responded_at' => now(),
            ])->save();

            $confirmation->sendConfirmedNotice($member);

            Log::info('[Webhook] Member confirmed', ['member_id' => $member->id]);

            return response()->json(['success' => true, 'action' => 'confirmed']);
        }

        $member->forceFill([
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'rejected',
            'whatsapp_confirmation_responded_at' => now(),
            'whatsapp_last_sent_date' => null,
        ])->save();

        $confirmation->sendRejectedNotice($member);

        Log::info('[Webhook] Member rejected', ['member_id' => $member->id]);

        return response()->json(['success' => true, 'action' => 'rejected']);
    }

    /**
     * Incoming webhook can contain nested fields; extract sender phone.
     */
    private function extractPhone(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'from'),
            data_get($payload, 'data.from'),
            data_get($payload, 'sender'),
            data_get($payload, 'data.sender'),
            data_get($payload, 'chatId'),
            data_get($payload, 'data.chatId'),
            data_get($payload, 'remoteJid'),
            data_get($payload, 'data.remoteJid'),
        ];

        foreach ($candidates as $raw) {
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }

            $digits = preg_replace('/\D/', '', $raw);
            $normalized = normalizeUkWhatsAppPhone($digits);
            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Extract inbound message body text from common UltraMsg payload keys.
     */
    private function extractMessageText(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'body'),
            data_get($payload, 'data.body'),
            data_get($payload, 'message'),
            data_get($payload, 'data.message'),
            data_get($payload, 'text'),
            data_get($payload, 'data.text'),
        ];

        foreach ($candidates as $raw) {
            if (is_string($raw) && trim($raw) !== '') {
                return trim($raw);
            }
        }

        return null;
    }

    /**
     * Reject requests missing the shared webhook secret.
     * If no secret is configured, all requests are allowed (dev mode).
     */
    private function verifySecret(Request $request): bool
    {
        $expected = config('services.ultramsg.webhook_secret');

        if (! $expected) {
            return true;
        }

        $provided = $request->header('X-Webhook-Secret')
            ?? $request->query('secret');

        return hash_equals($expected, (string) $provided);
    }

    /**
     * Ignore webhook events generated by our own outgoing messages.
     */
    private function isFromSelf(array $payload): bool
    {
        $flags = [
            data_get($payload, 'fromMe'),
            data_get($payload, 'data.fromMe'),
            data_get($payload, 'sentByMe'),
            data_get($payload, 'data.sentByMe'),
        ];

        foreach ($flags as $flag) {
            if (is_bool($flag) && $flag === true) {
                return true;
            }
            if (is_string($flag) && in_array(strtolower($flag), ['true', '1', 'yes'], true)) {
                return true;
            }
            if (is_int($flag) && $flag === 1) {
                return true;
            }
        }

        // If webhook type is clearly outgoing, skip it.
        $type = data_get($payload, 'type') ?? data_get($payload, 'event_type') ?? data_get($payload, 'data.type');
        if (is_string($type) && str_contains(strtolower($type), 'out')) {
            return true;
        }

        return false;
    }
}

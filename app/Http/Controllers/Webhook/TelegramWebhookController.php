<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\LentSeason;
use App\Models\DailyContent;
use App\Models\Member;
use App\Models\User;
use App\Services\TelegramAuthService;
use App\Services\TelegramService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Telegram bot webhook entry point.
 */
class TelegramWebhookController extends Controller
{
    public function handle(
        Request $request,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        if (! $telegramService->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'Telegram bot not configured.'], 503);
        }

        if (! $this->verifySecret($request)) {
            Log::warning('[TelegramWebhook] Invalid secret.', ['ip' => $request->ip()]);

            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $message = $request->input('message');
        if (! is_array($message)) {
            return response()->json(['success' => false, 'message' => 'No message payload.'], 400);
        }

        $chatId = trim((string) data_get($message, 'chat.id', ''));
        if ($chatId === '') {
            return response()->json(['success' => false, 'message' => 'No chat id.'], 400);
        }

        $text = trim((string) data_get($message, 'text', ''));
        if (! $text) {
            return response()->json(['success' => true, 'message' => 'No text command.']);
        }

        $parts = preg_split('/\s+/', $text, 2);
        $rawCommand = strtolower((string) ($parts[0] ?? ''));
        $argument = trim((string) ($parts[1] ?? ''));
        $command = (string) preg_replace('/@.*/', '', $rawCommand);

        Log::info('[TelegramWebhook] Incoming command', [
            'chat_id' => $chatId,
            'command' => $command,
            'argument_provided' => $argument !== '',
        ]);

        return match ($command) {
            '/start' => $this->handleStart($chatId, $argument, $telegramAuthService, $telegramService),
            '/home' => $this->handleHome($chatId, $telegramAuthService, $telegramService),
            '/help' => $this->reply($telegramService, $chatId, $this->helpMessage()),
            '/menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
            '/admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
            '/connect' => $this->handleConnect(
                $chatId,
                $argument,
                $telegramAuthService,
                $telegramService
            ),
            '/me' => $this->handleMe(
                $chatId,
                $telegramAuthService,
                $telegramService
            ),
            '/day',
            '/today' => $this->handleToday(
                $chatId,
                $telegramAuthService,
                $telegramService
            ),
            default => $this->reply($telegramService, $chatId, $this->fallbackMessage()),
        };
    }

    private function handleStart(
        string $chatId,
        string $argument,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        if (! $argument) {
            $actor = $this->actorFromChatId($chatId);
            if (! $actor) {
                return $this->reply($telegramService, $chatId, $this->welcomeMessage());
            }

            return $this->reply(
                $telegramService,
                $chatId,
                'Quick actions:',
                $this->quickLinksKeyboard($actor, $telegramAuthService)
            );
        }

        $action = $this->resolveStartAction($argument);
        if ($action) {
            return match ($action) {
                'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
                'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService),
                'today' => $this->handleToday($chatId, $telegramAuthService, $telegramService),
                'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
                'me' => $this->handleMe($chatId, $telegramAuthService, $telegramService),
                default => $this->reply(
                    $telegramService,
                    $chatId,
                    "Unknown Telegram entry point: /start {$argument}"
                ),
            };
        }

        return $this->bindFromCode($chatId, $argument, $telegramAuthService, $telegramService, 'start');
    }

    private function handleConnect(
        string $chatId,
        string $argument,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        if (! $argument) {
            return $this->reply(
                $telegramService,
                $chatId,
                "Send /connect <code> to bind this Telegram account.\nUse a one-time code from your admin/member login link or legacy member token."
            );
        }

        return $this->bindFromCode($chatId, $argument, $telegramAuthService, $telegramService, 'connect');
    }

    private function handleMenu(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->reply(
                $telegramService,
                $chatId,
                "Your Telegram account is not linked yet.\nUse /connect <code> with one of your one-time codes."
            );
        }

        return $this->reply(
            $telegramService,
            $chatId,
            'Quick actions:',
            $this->quickLinksKeyboard($actor, $telegramAuthService)
        );
    }

    private function handleMe(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->reply(
                $telegramService,
                $chatId,
                $this->notLinkedMessage()
            );
        }

        return $this->reply(
            $telegramService,
            $chatId,
            'Your secure launch links:',
            $this->quickLinksKeyboard($actor, $telegramAuthService)
        );
    }

    private function handleHome(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->reply($telegramService, $chatId, 'Only linked members can use /home.');
        }

        $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);

        return $this->reply($telegramService, $chatId, 'Open your member home:', [
            'inline_keyboard' => [
                [['text' => 'Open member home', 'url' => $homeLink]],
            ],
        ]);
    }

    private function handleAdmin(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof User) {
            return $this->reply($telegramService, $chatId, 'Only linked admins can use /admin.');
        }

        $adminLink = $this->adminSecureLink($actor, $telegramAuthService);

        return $this->reply($telegramService, $chatId, 'Open your admin panel:', [
            'inline_keyboard' => [
                [['text' => 'Open admin panel', 'url' => $adminLink]],
            ],
        ]);
    }

    private function handleToday(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->reply(
                $telegramService,
                $chatId,
                'Only linked members can use /day.'
            );
        }

        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return $this->reply($telegramService, $chatId, 'No active season configured yet.');
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->first();

        if (! $daily) {
            return $this->reply($telegramService, $chatId, 'No published content available for today yet.');
        }

        $code = $telegramAuthService->createCode(
            $actor,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily]),
            30
        );

        $link = route('telegram.access', [
            'code' => $code,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]);

        return $this->reply($telegramService, $chatId, "Day {$daily->day_number} content:", [
            'inline_keyboard' => [
                [['text' => 'Open today', 'url' => $link]],
            ],
        ]);
    }

    private function bindFromCode(
        string $chatId,
        string $argument,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        string $source
    ): JsonResponse {
        $code = $this->normalizeCodePayload($argument);
        $token = $telegramAuthService->consumeCode($code);
        if ($token && $token->actor) {
            $this->syncTelegramChatId($token->actor, $chatId);

            return $this->reply(
                $telegramService,
                $chatId,
                "Telegram account linked via {$source}.",
                $this->quickLinksKeyboard($token->actor, $telegramAuthService)
            );
        }

        if (preg_match('/^[A-Za-z0-9]{20,128}$/', $code)) {
            $member = Member::query()->where('token', $code)->first();
            if ($member) {
                $this->syncTelegramChatId($member, $chatId);

                return $this->reply(
                    $telegramService,
                    $chatId,
                    "Telegram account linked to {$this->actorDisplayName($member)}.",
                    $this->quickLinksKeyboard($member, $telegramAuthService)
                );
            }
        }

        return $this->reply(
            $telegramService,
            $chatId,
            'Could not verify that code. Use a valid login link token or linked member token.'
        );
    }

    private function quickLinksKeyboard(Member|User $actor, TelegramAuthService $telegramAuthService): array
    {
        $keyboard = [];

        if ($actor instanceof Member) {
            $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);

            $keyboard[] = [
                ['text' => 'Open member home', 'url' => $homeLink],
            ];

            $todayLink = $this->memberTodaySecureLink($actor, $telegramAuthService);
            if ($todayLink !== null) {
                $keyboard[] = [
                    ['text' => 'Open today', 'url' => $todayLink],
                ];
            }

            return ['inline_keyboard' => $keyboard];
        }

        $adminLink = $this->adminSecureLink($actor, $telegramAuthService);

        $keyboard[] = [
            ['text' => 'Open admin panel', 'url' => $adminLink],
        ];

        return ['inline_keyboard' => $keyboard];
    }

    private function memberTodaySecureLink(Member $member, TelegramAuthService $telegramAuthService): ?string
    {
        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return null;
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->first();

        if (! $daily) {
            return null;
        }

        $code = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily]),
            30
        );

        return route('telegram.access', [
            'code' => $code,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]);
    }

    private function memberHomeSecureLink(Member $member, TelegramAuthService $telegramAuthService): string
    {
        $homeCode = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.home'),
            120
        );

        return route('telegram.access', [
            'code' => $homeCode,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]);
    }

    private function adminSecureLink(User $admin, TelegramAuthService $telegramAuthService): string
    {
        $adminCode = $telegramAuthService->createCode(
            $admin,
            TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            $this->adminFallbackPath($admin),
            30
        );

        return route('telegram.access', [
            'code' => $adminCode,
            'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
        ]);
    }

    private function actorFromChatId(string $chatId): Member|User|null
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return null;
        }

        $member = Member::query()->where('telegram_chat_id', $chatId)->first();
        if ($member instanceof Member) {
            return $member;
        }

        return User::query()->where('telegram_chat_id', $chatId)->first();
    }

    private function syncTelegramChatId(Member|User $actor, string $chatId): void
    {
        $chatId = trim($chatId);
        if ($chatId === '') {
            return;
        }

        $this->releaseTelegramChatIdBinding($actor, $chatId);

        if ((string) $actor->telegram_chat_id === $chatId) {
            return;
        }

        $actor->forceFill(['telegram_chat_id' => $chatId])->save();
    }

    private function releaseTelegramChatIdBinding(Member|User $actor, string $chatId): void
    {
        Member::query()
            ->where('telegram_chat_id', $chatId)
            ->update(['telegram_chat_id' => null]);

        User::query()
            ->where('telegram_chat_id', $chatId)
            ->update(['telegram_chat_id' => null]);
    }

    private function actorDisplayName(Member|User $actor): string
    {
        return $actor instanceof Member ? $actor->baptism_name : $actor->name;
    }

    private function adminFallbackPath(User $user): string
    {
        return match ($user->role) {
            'writer' => route('admin.daily.index'),
            default => route('admin.dashboard'),
        };
    }

    private function normalizeCodePayload(string $payload): string
    {
        return (string) preg_replace('/^(?:amiy|am|member|admin):/', '', trim($payload));
    }

    private function resolveStartAction(string $argument): ?string
    {
        return match (strtolower(trim($argument))) {
            'menu' => 'menu',
            'home' => 'home',
            'today', 'day' => 'today',
            'admin' => 'admin',
            'me' => 'me',
            default => null,
        };
    }

    private function verifySecret(Request $request): bool
    {
        $expected = (string) config('services.telegram.webhook_secret', '');
        if ($expected === '') {
            return true;
        }

        $provided = (string) (
            $request->header('X-Telegram-Bot-Api-Secret-Token')
            ?? $request->query('secret', '')
        );

        return hash_equals($expected, $provided);
    }

    private function reply(
        TelegramService $telegramService,
        string $chatId,
        string $text,
        array $replyMarkup = []
    ): JsonResponse {
        $options = [];
        if (! empty($replyMarkup)) {
            $options['reply_markup'] = $replyMarkup;
        }

        $ok = $telegramService->sendTextMessage($chatId, $text, $options);

        return response()->json([
            'success' => $ok,
            'delivered' => $ok,
            'sent' => $ok,
        ]);
    }

    private function welcomeMessage(): string
    {
        return "Welcome to Abiy Tsom.\n\n"
            . "Use one-time Telegram link from admin/member section, or a legacy member token.\n\n"
            . "Available commands:\n"
            . "/home (linked members)\n"
            . "/admin (linked admins)\n"
            . "/menu\n"
            . "/connect <code>\n"
            . "/me\n"
            . "/day\n"
            . "/help";
    }

    private function fallbackMessage(): string
    {
        return 'Unknown command. Use /menu to see quick options.';
    }

    private function helpMessage(): string
    {
        return "Abiy Tsom commands:\n"
            . "/menu - Open quick links\n"
            . "/home - Open member home\n"
            . "/admin - Open admin panel\n"
            . "/connect <code> - Link this Telegram to your account\n"
            . "/me - Get a secure one-time login link\n"
            . "/day - Open today\n"
            . "/help - Show this help";
    }

    private function notLinkedMessage(): string
    {
        return "Your Telegram account is not linked yet.\n"
            . "Use /connect <code> with a code from your member/admin flow.";
    }
}

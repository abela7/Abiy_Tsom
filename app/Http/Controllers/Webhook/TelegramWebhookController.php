<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\TelegramAccessToken;
use App\Models\User;
use App\Services\TelegramAuthService;
use App\Services\TelegramBotBuilderService;
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
    public function __construct(
        private readonly TelegramBotBuilderService $telegramBotBuilder
    ) {}

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

        $callbackQuery = $request->input('callback_query');
        if (is_array($callbackQuery)) {
            return $this->handleCallbackQuery(
                $callbackQuery,
                $telegramAuthService,
                $telegramService
            );
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
            '/help' => $this->reply($telegramService, $chatId, $this->helpMessage(), $this->launchKeyboard()),
            '/menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
            '/admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
            '/me' => $this->handleMe($chatId, $telegramAuthService, $telegramService),
            '/day',
            '/today' => $this->handleToday($chatId, $telegramAuthService, $telegramService),
            default => $this->handlePlainText($chatId, $text, $telegramAuthService, $telegramService),
        };
    }

    private function handleCallbackQuery(
        array $callbackQuery,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $message = $callbackQuery['message'] ?? null;
        $chatId = (string) data_get($message, 'chat.id', '');
        if ($chatId === '') {
            $chatId = (string) data_get($callbackQuery, 'from.id', '');
        }
        $messageId = (int) data_get($message, 'message_id', 0);
        $action = (string) data_get($callbackQuery, 'data', '');
        $callbackId = (string) data_get($callbackQuery, 'id', '');

        if ($chatId === '' || $action === '') {
            return response()->json(['success' => false, 'message' => 'Invalid callback payload.'], 400);
        }

        if ($callbackId !== '') {
            $telegramService->answerCallbackQuery($callbackId, '');
        }

        return match ($action) {
            'have_account' => $this->handleHaveAccount($chatId, $messageId, $telegramService),
            'unlink' => $this->handleUnlink($chatId, $messageId, $telegramAuthService, $telegramService),
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService, $messageId),
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService, $messageId),
            'today' => $this->handleToday($chatId, $telegramAuthService, $telegramService, $messageId),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService, $messageId),
            'me' => $this->handleMe($chatId, $telegramAuthService, $telegramService, $messageId),
            'help' => $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->helpMessage(), $this->launchKeyboard()),
            default => $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard()),
        };
    }

    private function handlePlainText(
        string $chatId,
        string $text,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $normalized = strtolower(trim($text));

        $linked = match ($normalized) {
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService),
            'today',
            'day' => $this->handleToday($chatId, $telegramAuthService, $telegramService),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
            'help' => $this->reply($telegramService, $chatId, $this->helpMessage(), $this->launchKeyboard()),
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
            'unlink' => $this->handleUnlink($chatId, 0, $telegramAuthService, $telegramService),
            default => null,
        };

        if ($linked !== null) {
            return $linked;
        }

        $codeAttempt = preg_replace('/\s+/', '', $text);
        if (preg_match('/^[A-Za-z0-9]{6,8}$/', $codeAttempt)) {
            $linkResult = $this->tryLinkByShortCode($chatId, $codeAttempt, $telegramAuthService, $telegramService);
            if ($linkResult !== null) {
                return $linkResult;
            }
        }

        return $this->reply($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard());
    }

    private function tryLinkByShortCode(
        string $chatId,
        string $code,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): ?JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if ($actor) {
            return null;
        }

        $member = $telegramAuthService->consumeByShortCode($code);
        if (! $member) {
            return null;
        }

        $this->syncTelegramChatId($member, $chatId);

        $keyboard = $this->mainMenuKeyboard($member, $telegramAuthService);
        $keyboard = $this->ensureMenuHasOpenAppButton($keyboard, $member, $telegramAuthService);

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_linked_success')."\n\n".__('app.telegram_menu_heading'),
            $keyboard
        );
    }

    /**
     * Ensure the menu always has at least one working "Open app" button.
     */
    private function ensureMenuHasOpenAppButton(array $keyboard, Member|User $actor, TelegramAuthService $telegramAuthService): array
    {
        $rows = $keyboard['inline_keyboard'] ?? [];
        if ($rows === []) {
            $openUrl = $this->actorOpenAppUrl($actor, $telegramAuthService);

            return ['inline_keyboard' => [
                [['text' => $this->telegramBotBuilder->menuButtonLabel(), 'web_app' => ['url' => $openUrl]]],
            ]];
        }

        $hasWebApp = false;
        foreach ($rows as $row) {
            foreach ($row as $btn) {
                if (isset($btn['web_app']['url'])) {
                    $hasWebApp = true;
                    break 2;
                }
            }
        }

        if (! $hasWebApp) {
            $openUrl = $this->actorOpenAppUrl($actor, $telegramAuthService);
            $rows[] = [['text' => $this->telegramBotBuilder->menuButtonLabel(), 'web_app' => ['url' => $openUrl]]];
        }

        return ['inline_keyboard' => $rows];
    }

    private function actorOpenAppUrl(Member|User $actor, TelegramAuthService $telegramAuthService): string
    {
        $code = $telegramAuthService->createCode(
            $actor,
            $actor instanceof Member ? TelegramAuthService::PURPOSE_MEMBER_ACCESS : TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            $actor instanceof Member ? route('member.home') : $this->adminFallbackPath($actor),
            $actor instanceof Member ? 120 : 30
        );

        return url(route('telegram.access', [
            'code' => $code,
            'purpose' => $actor instanceof Member ? TelegramAuthService::PURPOSE_MEMBER_ACCESS : TelegramAuthService::PURPOSE_ADMIN_ACCESS,
        ]));
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
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_start_welcome'),
                    $this->startChoiceKeyboard()
                );
            }

            return $this->reply(
                $telegramService,
                $chatId,
                $this->menuHeading(),
                $this->mainMenuKeyboard($actor, $telegramAuthService)
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
                'help' => $this->reply($telegramService, $chatId, $this->helpMessage(), $this->launchKeyboard()),
                default => $this->reply(
                    $telegramService,
                    $chatId,
                    $this->notLinkedMessage(),
                    $this->miniConnectKeyboard()
                ),
            };
        }

        if ($this->isRoleHint(trim((string) $argument))) {
            $actor = $this->actorFromChatId($chatId);

            if (! $actor) {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    $this->notLinkedMessage(),
                    $this->miniConnectKeyboard($this->purposeFromStartRoleHint($argument))
                );
            }

            return $this->reply(
                $telegramService,
                $chatId,
                $this->menuHeading(),
                $this->mainMenuKeyboard($actor, $telegramAuthService)
            );
        }

        return $this->bindFromCode($chatId, $argument, $telegramAuthService, $telegramService, 'start');
    }

    private function handleUnlink(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.telegram_bot_unlink_not_linked'), $this->startChoiceKeyboard());
        }

        $this->wipeTelegramDataForChat($chatId, $actor);

        return $this->replyAfterDelete(
            $telegramService,
            $chatId,
            $messageId,
            __('app.telegram_bot_unlinked'),
            $this->startChoiceKeyboard()
        );
    }

    private function wipeTelegramDataForChat(string $chatId, Member|User $actor): void
    {
        $this->releaseTelegramChatIdBinding($actor, $chatId);

        TelegramAccessToken::query()
            ->where('actor_type', $actor->getMorphClass())
            ->where('actor_id', $actor->getKey())
            ->delete();
    }

    private function handleHaveAccount(string $chatId, int $messageId, TelegramService $telegramService): JsonResponse
    {
        $text = __('app.telegram_start_have_account_code_instructions');
        $keyboard = ['inline_keyboard' => [
            [['text' => __('app.telegram_start_open_app'), 'web_app' => ['url' => url(route('home'))]]],
        ]];

        return $this->replyAfterDelete($telegramService, $chatId, $messageId, $text, $keyboard);
    }

    private function handleMenu(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        return $this->replyAfterDelete(
            $telegramService,
            $chatId,
            $messageId,
            $this->menuHeading(),
            $this->mainMenuKeyboard($actor, $telegramAuthService)
        );
    }

    private function handleMe(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->buttonEnabled('me', 'member')) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard());
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        return $this->replyAfterDelete(
            $telegramService,
            $chatId,
            $messageId,
            'Your secure launch links:',
            $this->quickLinksKeyboard($actor, $telegramAuthService)
        );
    }

    private function handleHome(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('home')) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard());
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);
        if (! $homeLink) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, 'Could not generate secure member home link right now.', []);
        }

        $homeLabel = $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home');

        return $this->replyAfterDelete($telegramService, $chatId, $messageId, $homeLabel.':', [
            'inline_keyboard' => [
                [['text' => $homeLabel, 'web_app' => ['url' => $homeLink]]],
            ],
        ]);
    }

    private function handleAdmin(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('admin')) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard());
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof User) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->miniConnectKeyboard(TelegramAuthService::PURPOSE_ADMIN_ACCESS));
        }

        $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
        $adminLabel = $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel');

        return $this->replyAfterDelete($telegramService, $chatId, $messageId, $adminLabel.':', [
            'inline_keyboard' => [
                [['text' => $adminLabel, 'web_app' => ['url' => $adminLink]]],
            ],
        ]);
    }

    private function handleToday(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('day')) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard());
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, 'No active season configured yet.', []);
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->first();

        if (! $daily) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, 'No published content available for today yet.', []);
        }

        $code = $telegramAuthService->createCode(
            $actor,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $daily]),
            30
        );

        $link = url(route('telegram.access', [
            'code' => $code,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]));

        $label = $this->telegramBotBuilder->buttonLabel('today', 'member', 'Today');

        return $this->replyAfterDelete($telegramService, $chatId, $messageId, "Day {$daily->day_number} content:", [
            'inline_keyboard' => [
                [['text' => $label, 'web_app' => ['url' => $link]]],
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
            'Could not verify that code. Use a valid login link or linked member token.'
        );
    }

    private function quickLinksKeyboard(Member|User $actor, TelegramAuthService $telegramAuthService): array
    {
        $keyboard = [];

        if ($actor instanceof Member) {
            $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);
            $keyboard[] = [
                ['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home'), 'web_app' => ['url' => $homeLink]],
            ];

            $todayLink = $this->memberTodaySecureLink($actor, $telegramAuthService);
            if ($todayLink !== null) {
                $keyboard[] = [
                    ['text' => $this->telegramBotBuilder->buttonLabel('today', 'member', 'Today'), 'web_app' => ['url' => $todayLink]],
                ];
            }

            return ['inline_keyboard' => $keyboard];
        }

        $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
        $keyboard[] = [
            ['text' => $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel'), 'web_app' => ['url' => $adminLink]],
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

        return url(route('telegram.access', [
            'code' => $code,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]));
    }

    private function memberHomeSecureLink(Member $member, TelegramAuthService $telegramAuthService): string
    {
        $homeCode = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.home'),
            120
        );

        return url(route('telegram.access', [
            'code' => $homeCode,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]));
    }

    private function adminSecureLink(User $admin, TelegramAuthService $telegramAuthService): string
    {
        $adminCode = $telegramAuthService->createCode(
            $admin,
            TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            $this->adminFallbackPath($admin),
            30
        );

        return url(route('telegram.access', [
            'code' => $adminCode,
            'purpose' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
        ]));
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
            'help' => 'help',
            default => null,
        };
    }

    private function isRoleHint(string $argument): bool
    {
        return in_array($argument, ['member', 'admin', 'writer', 'editor'], true);
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

    private function replyOrEdit(
        TelegramService $telegramService,
        string $chatId,
        string $text,
        array $replyMarkup = [],
        int $messageId = 0
    ): JsonResponse {
        if ($messageId > 0) {
            $ok = $telegramService->editMessageText($chatId, $messageId, $text, $replyMarkup);
        } else {
            return $this->reply($telegramService, $chatId, $text, $replyMarkup);
        }

        return response()->json([
            'success' => $ok,
            'delivered' => $ok,
            'sent' => $ok,
        ]);
    }

    /**
     * Delete the previous message and send a new one. Keeps chat clean (no history).
     */
    private function replyAfterDelete(
        TelegramService $telegramService,
        string $chatId,
        int $messageId,
        string $text,
        array $replyMarkup = []
    ): JsonResponse {
        if ($messageId > 0) {
            $telegramService->deleteMessage($chatId, $messageId);
        }

        return $this->reply($telegramService, $chatId, $text, $replyMarkup);
    }

    private function startChoiceKeyboard(): array
    {
        $appUrl = route('home');
        $rows = [
            [['text' => __('app.telegram_start_new'), 'web_app' => ['url' => $appUrl]]],
            [['text' => __('app.telegram_start_have_account'), 'callback_data' => 'have_account']],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function welcomeMessage(): string
    {
        return $this->telegramBotBuilder->welcomeMessage();
    }

    private function fallbackMessage(): string
    {
        return 'I did not recognize this input. Use the buttons below.';
    }

    private function helpMessage(): string
    {
        $message = $this->telegramBotBuilder->helpMessage();
        if (str_contains(strtolower($message), '/connect')) {
            return 'Use the buttons below to open the Abiy Tsom app securely.';
        }

        return $message;
    }

    private function notLinkedMessage(): string
    {
        $message = $this->telegramBotBuilder->notLinkedMessage();
        if (str_contains(strtolower($message), '/connect')) {
            return 'Your account is not linked yet. Open the app and continue securely.';
        }

        return $message;
    }

    private function menuHeading(): string
    {
        return 'Quick actions:';
    }

    private function launchKeyboard(): array
    {
        $rows = [
            [
                ['text' => $this->telegramBotBuilder->menuButtonLabel(), 'web_app' => ['url' => $this->miniConnectUrl()]],
            ],
        ];

        if ($this->telegramBotBuilder->commandEnabled('menu')) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('menu', 'member', 'Menu'), 'callback_data' => 'menu']];
        }

        return ['inline_keyboard' => $rows];
    }

    private function miniConnectKeyboard(?string $purpose = null): array
    {
        return ['inline_keyboard' => [
            [['text' => $this->telegramBotBuilder->menuButtonLabel(), 'web_app' => ['url' => $this->miniConnectUrl($purpose)]]],
        ]];
    }

    private function miniConnectUrl(?string $purpose = null): string
    {
        if ($purpose !== null) {
            return route('telegram.mini.connect', ['purpose' => $purpose]);
        }

        return route('telegram.mini.connect');
    }

    private function purposeFromStartRoleHint(string $argument): ?string
    {
        return match (strtolower(trim($argument))) {
            'member' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            'admin', 'writer', 'editor' => TelegramAuthService::PURPOSE_ADMIN_ACCESS,
            default => null,
        };
    }

    private function mainMenuKeyboard(Member|User $actor, TelegramAuthService $telegramAuthService): array
    {
        $rows = [];

        if ($actor instanceof Member) {
            $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);
            $todayLink = $this->memberTodaySecureLink($actor, $telegramAuthService);

            $memberRow = [];
            if ($this->telegramBotBuilder->buttonEnabled('home', 'member')) {
                $memberRow[] = ['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home'), 'web_app' => ['url' => $homeLink]];
            }

            if ($this->telegramBotBuilder->buttonEnabled('today', 'member') && $todayLink !== null) {
                $memberRow[] = ['text' => $this->telegramBotBuilder->buttonLabel('today', 'member', 'Today'), 'web_app' => ['url' => (string) $todayLink]];
            }

            if ($memberRow !== []) {
                $rows[] = $memberRow;
            }

            if ($this->telegramBotBuilder->buttonEnabled('me', 'member')) {
                $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('me', 'member', 'My links'), 'callback_data' => 'me']];
            }

            if ($this->telegramBotBuilder->buttonEnabled('help', 'member')) {
                $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('help', 'member', 'Help'), 'callback_data' => 'help']];
            }

            $rows[] = [['text' => __('app.telegram_bot_unlink'), 'callback_data' => 'unlink']];

            if ($rows === []) {
                $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);
                $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home'), 'web_app' => ['url' => $homeLink]]];
            }

            return ['inline_keyboard' => $rows];
        }

        if ($this->telegramBotBuilder->buttonEnabled('admin', 'admin')) {
            $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel'), 'web_app' => ['url' => $adminLink]]];
        }

        if ($this->telegramBotBuilder->buttonEnabled('help', 'admin')) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('help', 'admin', 'Help'), 'callback_data' => 'help']];
        }

        $rows[] = [['text' => __('app.telegram_bot_unlink'), 'callback_data' => 'unlink']];

        if ($rows === []) {
            $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel'), 'web_app' => ['url' => $adminLink]]];
        }

        return ['inline_keyboard' => $rows];
    }
}

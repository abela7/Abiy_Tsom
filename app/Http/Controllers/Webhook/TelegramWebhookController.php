<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
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
    ) {
    }

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
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService, $messageId),
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService, $messageId),
            'today' => $this->handleToday($chatId, $telegramAuthService, $telegramService, $messageId),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService, $messageId),
            'me' => $this->handleMe($chatId, $telegramAuthService, $telegramService, $messageId),
            'help' => $this->replyOrEdit($telegramService, $chatId, $this->helpMessage(), $this->launchKeyboard(), $messageId),
            default => $this->replyOrEdit($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard(), $messageId),
        };
    }

    private function handlePlainText(
        string $chatId,
        string $text,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $normalized = strtolower(trim($text));

        return match ($normalized) {
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService),
            'today',
            'day' => $this->handleToday($chatId, $telegramAuthService, $telegramService),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
            'help' => $this->reply($telegramService, $chatId, $this->helpMessage(), $this->launchKeyboard()),
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
            default => $this->reply($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard()),
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

    private function handleHaveAccount(string $chatId, int $messageId, TelegramService $telegramService): JsonResponse
    {
        $text = __('app.telegram_start_have_account_instructions');
        $keyboard = ['inline_keyboard' => [
            [['text' => __('app.telegram_start_open_app'), 'web_app' => ['url' => route('home')]]],
        ]];

        if ($messageId > 0) {
            $ok = $telegramService->editMessageText($chatId, $messageId, $text, $keyboard);
        } else {
            $ok = $telegramService->sendTextMessage($chatId, $text, ['reply_markup' => $keyboard]);
        }

        return response()->json(['success' => $ok, 'delivered' => $ok, 'sent' => $ok]);
    }

    private function handleMenu(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyOrEdit($telegramService, $chatId, $this->notLinkedMessage(), $this->startChoiceKeyboard(), $messageId);
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $this->menuHeading(),
            $this->mainMenuKeyboard($actor, $telegramAuthService),
            $messageId
        );
    }

    private function handleMe(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->buttonEnabled('me', 'member')) {
            return $this->replyOrEdit($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard(), $messageId);
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyOrEdit($telegramService, $chatId, $this->notLinkedMessage(), $this->startChoiceKeyboard(), $messageId);
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            'Your secure launch links:',
            $this->quickLinksKeyboard($actor, $telegramAuthService),
            $messageId
        );
    }

    private function handleHome(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('home')) {
            return $this->replyOrEdit($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard(), $messageId);
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyOrEdit($telegramService, $chatId, $this->notLinkedMessage(), $this->startChoiceKeyboard(), $messageId);
        }

        $homeLink = $this->memberHomeSecureLink($actor, $telegramAuthService);
        if (! $homeLink) {
            return $this->replyOrEdit($telegramService, $chatId, 'Could not generate secure member home link right now.', [], $messageId);
        }

        $homeLabel = $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home');

        return $this->replyOrEdit($telegramService, $chatId, $homeLabel . ':', [
            'inline_keyboard' => [
                [['text' => $homeLabel, 'web_app' => ['url' => $homeLink]]],
            ],
        ], $messageId);
    }

    private function handleAdmin(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('admin')) {
            return $this->replyOrEdit($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard(), $messageId);
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof User) {
            return $this->replyOrEdit($telegramService, $chatId, $this->notLinkedMessage(), $this->miniConnectKeyboard(TelegramAuthService::PURPOSE_ADMIN_ACCESS), $messageId);
        }

        $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
        $adminLabel = $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel');

        return $this->replyOrEdit($telegramService, $chatId, $adminLabel . ':', [
            'inline_keyboard' => [
                [['text' => $adminLabel, 'web_app' => ['url' => $adminLink]]],
            ],
        ], $messageId);
    }

    private function handleToday(
        string $chatId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        int $messageId = 0
    ): JsonResponse {
        if (! $this->telegramBotBuilder->commandEnabled('day')) {
            return $this->replyOrEdit($telegramService, $chatId, $this->fallbackMessage(), $this->launchKeyboard(), $messageId);
        }

        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyOrEdit($telegramService, $chatId, $this->notLinkedMessage(), $this->startChoiceKeyboard(), $messageId);
        }

        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return $this->replyOrEdit($telegramService, $chatId, 'No active season configured yet.', [], $messageId);
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->first();

        if (! $daily) {
            return $this->replyOrEdit($telegramService, $chatId, 'No published content available for today yet.', [], $messageId);
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

        $label = $this->telegramBotBuilder->buttonLabel('today', 'member', 'Today');

        return $this->replyOrEdit($telegramService, $chatId, "Day {$daily->day_number} content:", [
            'inline_keyboard' => [
                [['text' => $label, 'web_app' => ['url' => $link]]],
            ],
        ], $messageId);
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

            if ($rows === []) {
                $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', 'Home'), 'web_app' => ['url' => route('member.home')]]];
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

        if ($rows === []) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('admin', 'admin', 'Admin panel'), 'web_app' => ['url' => $this->adminFallbackPath($actor)]]];
        }

        return ['inline_keyboard' => $rows];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ContentSuggestion;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use App\Models\TelegramAccessToken;
use App\Models\TelegramBotState;
use App\Models\Translation;
use App\Models\User;
use App\Services\EthiopianCalendarService;
use App\Services\TelegramAuthService;
use App\Services\TelegramBotBuilderService;
use App\Services\TelegramContentFormatter;
use App\Services\TelegramService;
use App\Services\UltraMsgService;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Telegram bot webhook entry point.
 */
class TelegramWebhookController extends Controller
{
    /** @var array<string, string> Amharic => English */
    private const PAULINE_BOOKS = [
        'ሮሜ' => 'Romans', '1ኛ ቆሮንቶስ' => '1 Corinthians', '2ኛ ቆሮንቶስ' => '2 Corinthians',
        'ገላትያ' => 'Galatians', 'ኤፌሶን' => 'Ephesians', 'ፊልጵስዩስ' => 'Philippians',
        'ቈሎስይስ' => 'Colossians', '1ኛ ተሰሎንቄ' => '1 Thessalonians', '2ኛ ተሰሎንቄ' => '2 Thessalonians',
        '1ኛ ጢሞቴዎስ' => '1 Timothy', '2ኛ ጢሞቴዎስ' => '2 Timothy', 'ቲቶ' => 'Titus',
        'ፊልሞና' => 'Philemon', 'ዕብራውያን' => 'Hebrews',
    ];

    /** @var array<string, string> Amharic => English */
    private const CATHOLIC_BOOKS = [
        'ያዕቆብ' => 'James', '1ኛ ጴጥሮስ' => '1 Peter', '2ኛ ጴጥሮስ' => '2 Peter',
        '1ኛ ዮሐንስ' => '1 John', '2ኛ ዮሐንስ' => '2 John', '3ኛ ዮሐንስ' => '3 John',
        'ይሁዳ' => 'Jude',
    ];

    /** @var array<string, string> Amharic => English */
    private const GOSPEL_BOOKS = [
        'ማቴዎስ' => 'Matthew', 'ማርቆስ' => 'Mark', 'ሉቃስ' => 'Luke', 'ዮሐንስ' => 'John',
    ];
    public function __construct(
        private readonly TelegramBotBuilderService $telegramBotBuilder,
        private readonly TelegramContentFormatter $contentFormatter,
        private readonly UltraMsgService $ultraMsg,
        private readonly EthiopianCalendarService $ethiopianCalendar
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

        // Store Telegram user's language for locale inference (User actors have no locale field)
        $from = data_get($request->all(), 'callback_query.from') ?? data_get($request->all(), 'message.from') ?? [];
        $tgLang = (string) ($from['language_code'] ?? 'am');
        $request->attributes->set('telegram_language_code', in_array($tgLang, ['am', 'ti'], true) ? $tgLang : 'am');

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

        // Centralised locale resolution for ALL message-based paths (slash commands + plain text).
        $this->applyLocaleForChat($chatId);

        $activeState = TelegramBotState::getAnyActive($chatId);
        $photos = data_get($message, 'photo', []);
        if (
            is_array($photos)
            && $photos !== []
            && $activeState?->action === 'suggest'
        ) {
            return $this->handleSuggestPhotoInput($chatId, $photos, $activeState, $telegramService);
        }

        $text = trim((string) data_get($message, 'text', ''));
        if (! $text) {
            if ($activeState?->action === 'suggest' && $activeState->step === 'await_image') {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_send_photo_or_skip'),
                    $this->structuredSuggestStepKeyboard('await_image')
                );
            }

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
            '/help' => $this->handleHelpCommand($chatId, $telegramService),
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

        // Centralised locale resolution for ALL callback-based paths.
        $this->applyLocaleForChat($chatId);

        if (str_starts_with($action, 'check_')) {
            return $this->handleChecklistToggle($chatId, $messageId, $action, $telegramAuthService, $telegramService);
        }

        if (str_starts_with($action, 'today_sec_')) {
            return $this->handleTodaySection($chatId, $messageId, $action, $telegramAuthService, $telegramService);
        }

        if (str_starts_with($action, 'progress_')) {
            return $this->handleProgressPeriod($chatId, $messageId, $action, $telegramAuthService, $telegramService);
        }

        if (in_array($action, ['lang_en', 'lang_am', 'lang_toggle'], true)) {
            return $this->handleLanguageChange($chatId, $messageId, $action, $telegramAuthService, $telegramService);
        }

        if (str_starts_with($action, 'suggest_') || $action === 'suggest' || $action === 'my_suggestions') {
            return $this->handleSuggestCallback($chatId, $messageId, $action, $telegramAuthService, $telegramService);
        }

        if ($action === 'staff_main_page') {
            return $this->handleStaffMainPage($chatId, $messageId, $telegramAuthService, $telegramService);
        }

        if ($action === 'staff_portal') {
            return $this->handleStaffPortal($chatId, $messageId, $telegramAuthService, $telegramService);
        }

        if ($action === 'link_admin_start') {
            return $this->handleLinkAdminStart($chatId, $messageId, $telegramService);
        }

        if ($action === 'link_member_whatsapp') {
            return $this->handleLinkMemberWhatsapp($chatId, $messageId, $telegramService);
        }

        if ($action === 'link_role_member') {
            return $this->handleLinkRoleChoice('member', $chatId, $messageId, $telegramService);
        }

        if ($action === 'link_role_admin') {
            return $this->handleLinkRoleChoice('admin', $chatId, $messageId, $telegramService);
        }

        if ($action === 'subscribe_wa_yes') {
            return $this->handleSubscribeWaYes($chatId, $messageId, $telegramService);
        }

        if ($action === 'subscribe_wa_no') {
            return $this->handleSubscribeWaNo($chatId, $messageId, $telegramService);
        }

        return match ($action) {
            'have_account' => $this->handleHaveAccount($chatId, $messageId, $telegramService),
            'start_over' => $this->handleStartOver($chatId, $messageId, $telegramService),
            'unlink' => $this->handleUnlink($chatId, $messageId, $telegramAuthService, $telegramService),
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService, $messageId),
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService, $messageId),
            'today' => $this->handleToday($chatId, $telegramAuthService, $telegramService, $messageId),
            'progress' => $this->handleProgress($chatId, $messageId, $telegramAuthService, $telegramService),
            'checklist' => $this->handleChecklist($chatId, $messageId, $telegramAuthService, $telegramService),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService, $messageId),
            'me' => $this->handleMe($chatId, $telegramAuthService, $telegramService, $messageId),
            'help' => $this->handleHelp($chatId, $messageId, $telegramAuthService, $telegramService),
            default => $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->fallbackMessage(), $this->launchKeyboard()),
        };
    }

    private function handlePlainText(
        string $chatId,
        string $text,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        // Locale already applied centrally in handle() before dispatch.
        $normalized = strtolower(trim($text));

        // Check for an active wizard state first — wizard input takes priority
        $activeState = TelegramBotState::getAnyActive($chatId);
        if ($activeState !== null) {
            // Universal cancel keyword
            if ($normalized === 'cancel') {
                $action = $activeState->action;
                $activeState->clear();

                $cancelMsg = in_array($action, ['link_admin', 'link_member', 'subscribe_wa'], true)
                    ? __('app.telegram_link_cancelled')
                    : __('app.telegram_suggest_cancelled');

                return $this->reply($telegramService, $chatId, $cancelMsg);
            }

            if ($activeState->action === 'link_admin') {
                return $this->handleLinkAdminText($chatId, $text, $activeState, $telegramAuthService, $telegramService);
            }

            if ($activeState->action === 'link_member') {
                return $this->handleLinkMemberText($chatId, $text, $activeState, $telegramService);
            }

            if ($activeState->action === 'subscribe_wa') {
                return $this->handleSubscribeWaText($chatId, $text, $activeState, $telegramService);
            }

            if ($activeState->action === 'suggest') {
                return $this->handleSuggestTextInput($chatId, $text, $activeState, $telegramAuthService, $telegramService);
            }
        }

        $linked = match ($normalized) {
            'home' => $this->handleHome($chatId, $telegramAuthService, $telegramService),
            'today',
            'day' => $this->handleToday($chatId, $telegramAuthService, $telegramService),
            'progress' => $this->handleProgress($chatId, 0, $telegramAuthService, $telegramService),
            'checklist' => $this->handleChecklist($chatId, 0, $telegramAuthService, $telegramService),
            'admin' => $this->handleAdmin($chatId, $telegramAuthService, $telegramService),
            'help' => $this->handleHelp($chatId, 0, $telegramAuthService, $telegramService),
            'menu' => $this->handleMenu($chatId, $telegramAuthService, $telegramService),
            'unlink' => $this->handleUnlink($chatId, 0, $telegramAuthService, $telegramService),
            'suggest' => $this->handleSuggestCallback($chatId, 0, 'suggest', $telegramAuthService, $telegramService),
            'my suggestions', 'my_suggestions' => $this->handleSuggestCallback($chatId, 0, 'my_suggestions', $telegramAuthService, $telegramService),
            'portal' => $this->handleStaffPortal($chatId, 0, $telegramAuthService, $telegramService),
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

        return response()->json(['success' => true]);
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

        // Check for a staff (admin/writer/editor) short code first.
        // If found, the account is identified — but we require WhatsApp verification
        // before binding it, to confirm the user owns the account.
        $staffUser = $telegramAuthService->consumeAdminByShortCode($code);
        if ($staffUser instanceof User) {
            return $this->startAdminWhatsAppVerification($chatId, $staffUser, $telegramService);
        }

        // Fall back to the regular member short code.
        $member = $telegramAuthService->consumeByShortCode($code);
        if (! $member) {
            return null;
        }

        $this->syncTelegramChatId($member, $chatId);

        $keyboard = $this->mainMenuKeyboard($member, $telegramAuthService);

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_linked_success')."\n\n".__('app.telegram_menu_heading'),
            $keyboard
        );
    }

    /**
     * Starts WhatsApp verification for a staff user whose short code was already validated.
     * We already know WHO they are — just need to confirm via WhatsApp before binding.
     */
    private function startAdminWhatsAppVerification(
        string $chatId,
        User $user,
        TelegramService $telegramService
    ): JsonResponse {
        $phone = $user->whatsapp_phone ?? '';

        if ($phone === '') {
            // No WhatsApp on file — bind directly (no verification possible).
            $this->syncTelegramChatId($user, $chatId);
            $keyboard = $this->mainMenuKeyboard($user, app(TelegramAuthService::class));

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_success')."\n\n".__('app.telegram_menu_heading'),
                $keyboard
            );
        }

        $code = (string) random_int(100000, 999999);

        $sent = $this->ultraMsg->sendTextMessage(
            $phone,
            "Your Abiy Tsom Telegram link code is: *{$code}*\n\nIt expires in 10 minutes. Do not share it."
        );

        if (! $sent) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_whatsapp_failed')
            );
        }

        TelegramBotState::startFor($chatId, 'link_admin', 'verify_code', [
            'user_id' => $user->id,
            'code' => $code,
            'code_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_link_code_sent')
        );
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

            if ($actor instanceof Member && ! $this->memberHasLocale($actor)) {
                return $this->showLanguageChoice($telegramService, $chatId);
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
                'help' => $this->handleHelp($chatId, 0, $telegramAuthService, $telegramService),
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

            if ($actor instanceof Member && ! $this->memberHasLocale($actor)) {
                return $this->showLanguageChoice($telegramService, $chatId);
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

    private function handleHelpCommand(string $chatId, TelegramService $telegramService): JsonResponse
    {
        return $this->reply($telegramService, $chatId, $this->helpMessage(), $this->helpKeyboard());
    }

    /**
     * Show the start/welcome screen again, respecting the actor's saved
     * language or falling back to the Telegram client language.
     */
    private function handleStartOver(
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        return $this->replyAfterDelete(
            $telegramService,
            $chatId,
            $messageId,
            __('app.telegram_start_welcome'),
            $this->startChoiceKeyboard()
        );
    }

    private function handleHelp(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->helpMessage(), $this->helpKeyboard());
    }

    private function handleLanguageChange(
        string $chatId,
        int $messageId,
        string $action,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);

        // Unlinked guest — persist the choice and redisplay the start screen.
        if (! $actor instanceof Member && ! $actor instanceof User) {
            $currentLocale = $this->guestLocale($chatId);
            $newLocale = match ($action) {
                'lang_en'     => 'en',
                'lang_am'     => 'am',
                'lang_toggle' => $currentLocale === 'en' ? 'am' : 'en',
                default       => null,
            };
            if ($newLocale === null) {
                return response()->json(['success' => true]);
            }

            TelegramBotState::storeLocale($chatId, $newLocale);
            app()->setLocale($newLocale);
            Translation::loadFromDb($newLocale);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_start_welcome'),
                $this->startChoiceKeyboard(),
                $messageId
            );
        }

        // Linked Member — determine current locale from saved field
        // Linked User (staff) — determine current locale from stored state
        $currentLocale = $actor instanceof Member
            ? ($actor->locale ?? 'am')
            : $this->guestLocale($chatId);

        $newLocale = match ($action) {
            'lang_en'     => 'en',
            'lang_am'     => 'am',
            'lang_toggle' => $currentLocale === 'en' ? 'am' : 'en',
            default       => null,
        };
        if ($newLocale === null) {
            return response()->json(['success' => true]);
        }

        if ($actor instanceof Member) {
            // Members store locale in their own DB column
            $actor->update(['locale' => $newLocale]);
        } else {
            // Staff users store locale in TelegramBotState (same key as guests)
            TelegramBotState::storeLocale($chatId, $newLocale);
        }

        $this->applyLocaleForActor($actor);

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $this->menuHeading(),
            $this->mainMenuKeyboard($actor, $telegramAuthService),
            $messageId
        );
    }

    private function showLanguageChoice(TelegramService $telegramService, string $chatId): JsonResponse
    {
        app()->setLocale('en');
        Translation::loadFromDb('en');

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_choose_language'),
            $this->languageChoiceKeyboard()
        );
    }

    private function languageChoiceKeyboard(): array
    {
        app()->setLocale('en');
        Translation::loadFromDb('en');

        return ['inline_keyboard' => [
            [
                ['text' => __('app.telegram_lang_en'), 'callback_data' => 'lang_en'],
                ['text' => __('app.telegram_lang_am'), 'callback_data' => 'lang_am'],
            ],
        ]];
    }

    private function languageToggleButton(Member|User $actor): array
    {
        // For Members use the saved locale; for staff users use the active locale
        // (already set by applyLocaleForActor before this point).
        $locale = $actor instanceof Member
            ? ($actor->locale ?? 'am')
            : app()->getLocale();

        $targetLocale = $locale === 'en' ? 'am' : 'en';
        $label        = $locale === 'en' ? __('app.telegram_lang_switch_am') : __('app.telegram_lang_switch_en');
        $callback     = $targetLocale === 'en' ? 'lang_en' : 'lang_am';

        return ['text' => $label, 'callback_data' => $callback];
    }

    private function memberHasLocale(Member $member): bool
    {
        return in_array($member->locale ?? '', ['en', 'am'], true);
    }

    /**
     * Resolve the locale for an unlinked (guest) chat.
     * Checks the persisted TelegramBotState locale first,
     * then falls back to the Telegram client language code.
     */
    private function guestLocale(string $chatId): string
    {
        $stored = TelegramBotState::getStoredLocale($chatId);
        if ($stored !== null) {
            return $stored;
        }

        return request()->attributes->get('telegram_language_code', 'am');
    }

    /**
     * Single entry-point for locale resolution — works for linked actors
     * (Member / User) and unlinked guests alike.
     */
    private function applyLocaleForChat(string $chatId): void
    {
        $actor = $this->actorFromChatId($chatId);

        if ($actor instanceof Member || $actor instanceof User) {
            $this->applyLocaleForActor($actor);
        } else {
            $locale = $this->guestLocale($chatId);
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }
    }

    private function applyLocaleForActor(Member|User $actor): void
    {
        if ($actor instanceof Member) {
            $locale = $this->memberHasLocale($actor) ? $actor->locale : 'am';
        } else {
            $stored = TelegramBotState::getStoredLocale((string) ($actor->telegram_chat_id ?? ''));
            $locale = $stored ?? request()->attributes->get('telegram_language_code', 'am');
        }
        app()->setLocale($locale);
        Translation::loadFromDb($locale);
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
            [['text' => '📲 '.__('app.telegram_link_via_whatsapp'), 'callback_data' => 'link_member_whatsapp']],
            [['text' => '🌐 '.__('app.telegram_start_open_app'), 'url' => url(route('home'))]],
            [['text' => '🔄 '.__('app.telegram_cant_access_restart'), 'callback_data' => 'start_over']],
        ]];

        return $this->replyAfterDelete($telegramService, $chatId, $messageId, $text, $keyboard, 'HTML');
    }

    /**
     * Starts the member WhatsApp linking wizard (asks for phone number).
     */
    private function handleLinkMemberWhatsapp(string $chatId, int $messageId, TelegramService $telegramService): JsonResponse
    {
        TelegramBotState::startFor($chatId, 'link_member', 'ask_phone');

        $text = '<b>📲 '.__('app.telegram_link_heading')."</b>\n\n".__('app.telegram_link_enter_phone');

        return $this->replyOrEdit($telegramService, $chatId, $text, [], $messageId, 'HTML');
    }

    /**
     * Handles plain-text input during the link_member wizard.
     */
    private function handleLinkMemberText(
        string $chatId,
        string $text,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        return match ($state->step) {
            'ask_phone' => $this->handleLinkMemberPhone($chatId, $text, $state, $telegramService),
            'verify_code' => $this->handleLinkMemberCode($chatId, $text, $state, $telegramService),
            // Re-show the role choice if the user types instead of tapping a button
            'choose_role' => $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_choose_role'),
                $this->linkRoleChoiceKeyboard((string) $state->get('role', 'editor'))
            ),
            // Re-show the subscribe offer if the user types instead of tapping a button
            'not_found_offer' => $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_not_registered'),
                $this->subscribeOrNotKeyboard()
            ),
            default => response()->json(['success' => true]),
        };
    }

    /**
     * Validates the member's phone, checks both members and users tables,
     * and either sends a code, offers a role choice, or offers reminder signup.
     */
    private function handleLinkMemberPhone(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $normalized = normalizeUkWhatsAppPhone(trim($input));

        if ($normalized === null) {
            return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
        }

        $member = Member::query()->where('whatsapp_phone', $normalized)->first();
        $user   = User::query()->where('whatsapp_phone', $normalized)->first();

        // Number not in either table — offer WhatsApp reminder signup
        if (! $member instanceof Member && ! $user instanceof User) {
            $state->advance('not_found_offer', ['phone' => $normalized]);

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_not_registered'),
                $this->subscribeOrNotKeyboard()
            );
        }

        // Number linked to BOTH a member account AND a staff account — offer choice
        if ($member instanceof Member && $user instanceof User) {
            $state->advance('choose_role', [
                'member_id' => $member->id,
                'user_id'   => $user->id,
                'phone'     => $normalized,
                'role'      => $user->role,
            ]);

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_choose_role'),
                $this->linkRoleChoiceKeyboard($user->role)
            );
        }

        // Staff (admin/editor/writer) account only — send code directly
        if ($user instanceof User) {
            return $this->sendLinkCode($chatId, $user->whatsapp_phone, 'user', $user->id, $state, $telegramService);
        }

        // Member account only — send code directly
        return $this->sendLinkCode($chatId, $normalized, 'member', $member->id, $state, $telegramService);
    }

    /**
     * Generates and sends a 6-digit WhatsApp verification code,
     * then advances the wizard to the verify_code step.
     */
    private function sendLinkCode(
        string $chatId,
        string $phone,
        string $linkType,
        int $actorId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $code = (string) random_int(100000, 999999);

        $sent = $this->ultraMsg->sendTextMessage(
            $phone,
            "Your Abiy Tsom Telegram link code is: *{$code}*\n\nIt expires in 10 minutes. Do not share it."
        );

        if (! $sent) {
            return $this->reply($telegramService, $chatId, __('app.telegram_link_whatsapp_failed'));
        }

        $idKey = $linkType === 'user' ? 'user_id' : 'member_id';
        $state->advance('verify_code', [
            'link_type'      => $linkType,
            $idKey           => $actorId,
            'code'           => $code,
            'code_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        return $this->reply(
            $telegramService,
            $chatId,
            $linkType === 'user'
                ? __('app.telegram_link_code_sent')
                : __('app.telegram_link_member_code_sent')
        );
    }

    /**
     * Validates the 6-digit code, links the account (member or staff user), and shows the main menu.
     */
    private function handleLinkMemberCode(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $expectedCode  = (string) $state->get('code', '');
        $codeExpiresAt = $state->get('code_expires_at');
        $linkType      = (string) $state->get('link_type', 'member');

        if ($codeExpiresAt && now()->isAfter($codeExpiresAt)) {
            $state->clear();

            return $this->reply($telegramService, $chatId, __('app.telegram_link_wrong_code'));
        }

        if (trim($input) !== $expectedCode) {
            return $this->reply($telegramService, $chatId, __('app.telegram_link_wrong_code'));
        }

        // Link as staff User (admin / editor / writer)
        if ($linkType === 'user') {
            $userId = (int) $state->get('user_id', 0);
            $user   = User::query()->find($userId);

            if (! $user instanceof User) {
                $state->clear();

                return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
            }

            $this->syncTelegramChatId($user, $chatId);
            $state->clear();
            $this->applyLocaleForActor($user);

            return $this->reply(
                $telegramService,
                $chatId,
                '✅ '.__('app.telegram_link_success')."\n\n".__('app.telegram_menu_heading'),
                $this->mainMenuKeyboard($user, app(TelegramAuthService::class))
            );
        }

        // Link as regular Member (default)
        $memberId = (int) $state->get('member_id', 0);
        $member   = Member::query()->find($memberId);

        if (! $member instanceof Member) {
            $state->clear();

            return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
        }

        $this->syncTelegramChatId($member, $chatId);
        $state->clear();
        $this->applyLocaleForActor($member);

        return $this->reply(
            $telegramService,
            $chatId,
            '✅ '.__('app.telegram_link_success')."\n\n".__('app.telegram_menu_heading'),
            $this->mainMenuKeyboard($member, app(TelegramAuthService::class))
        );
    }

    /**
     * Handles the role-choice callback when a phone is linked to both a member and a staff account.
     * Sends a verification code for the chosen account type.
     */
    private function handleLinkRoleChoice(
        string $choice,
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        $state = TelegramBotState::getAnyActive($chatId);

        if (! $state || $state->action !== 'link_member' || $state->step !== 'choose_role') {
            return $this->reply($telegramService, $chatId, __('app.telegram_link_cancelled'));
        }

        if ($choice === 'member') {
            $memberId = (int) $state->get('member_id', 0);
            $member   = Member::query()->find($memberId);

            if (! $member instanceof Member) {
                $state->clear();

                return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
            }

            return $this->sendLinkCode($chatId, $member->whatsapp_phone, 'member', $member->id, $state, $telegramService);
        }

        // Admin / editor / writer
        $userId = (int) $state->get('user_id', 0);
        $user   = User::query()->find($userId);

        if (! $user instanceof User) {
            $state->clear();

            return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
        }

        return $this->sendLinkCode($chatId, $user->whatsapp_phone, 'user', $user->id, $state, $telegramService);
    }

    /** Returns the inline keyboard for the dual-role account choice. */
    private function linkRoleChoiceKeyboard(string $role): array
    {
        $roleLabel = match ($role) {
            'super_admin' => 'Super Admin',
            'editor'      => 'Editor',
            'writer'      => 'Writer',
            default       => ucfirst($role),
        };

        return ['inline_keyboard' => [
            [['text' => '👤 '.__('app.telegram_link_as_member'), 'callback_data' => 'link_role_member']],
            [['text' => '🔧 '.$roleLabel,                        'callback_data' => 'link_role_admin']],
        ]];
    }

    /** Returns the inline keyboard for the "subscribe to WhatsApp reminders?" offer. */
    private function subscribeOrNotKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => __('app.telegram_subscribe_yes'), 'callback_data' => 'subscribe_wa_yes']],
            [['text' => __('app.telegram_subscribe_no'),  'callback_data' => 'subscribe_wa_no']],
        ]];
    }

    // =========================================================================
    // WhatsApp Reminder Subscription (unregistered phone)
    // =========================================================================

    /**
     * User tapped "Yes" on the subscribe offer — clear the link_member state,
     * start the subscribe_wa wizard, and ask for their name.
     */
    private function handleSubscribeWaYes(
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        $linkState = TelegramBotState::getAnyActive($chatId);
        $phone     = $linkState ? (string) $linkState->get('phone', '') : '';

        $linkState?->clear();

        if ($phone === '') {
            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_link_cancelled'), [], $messageId);
        }

        TelegramBotState::startFor($chatId, 'subscribe_wa', 'ask_name', ['phone' => $phone]);

        return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_subscribe_ask_name'), [], $messageId);
    }

    /**
     * User tapped "No" on the subscribe offer — clear state and show a helpful message.
     */
    private function handleSubscribeWaNo(
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        TelegramBotState::getAnyActive($chatId)?->clear();

        $text = str_replace(':url', url(route('home')), __('app.telegram_subscribe_cancelled'));

        return $this->replyOrEdit($telegramService, $chatId, $text, [], $messageId);
    }

    /** Routes plain-text input for the subscribe_wa wizard. */
    private function handleSubscribeWaText(
        string $chatId,
        string $text,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        return match ($state->step) {
            'ask_name' => $this->handleSubscribeWaName($chatId, $text, $state, $telegramService),
            'ask_time' => $this->handleSubscribeWaTime($chatId, $text, $state, $telegramService),
            default    => response()->json(['success' => true]),
        };
    }

    /** Captures the user's name and advances to the time-selection step. */
    private function handleSubscribeWaName(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $name = trim($input);

        if (mb_strlen($name) < 2 || mb_strlen($name) > 64) {
            return $this->reply($telegramService, $chatId, __('app.telegram_subscribe_ask_name'));
        }

        $state->advance('ask_time', ['name' => $name]);

        return $this->reply($telegramService, $chatId, __('app.telegram_subscribe_ask_time'));
    }

    /**
     * Validates the 24hr time, creates a minimal member record, links their Telegram,
     * and sends a WhatsApp opt-in confirmation.
     */
    private function handleSubscribeWaTime(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $input = trim($input);

        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $input)) {
            return $this->reply($telegramService, $chatId, __('app.telegram_subscribe_invalid_time'));
        }

        $phone  = (string) $state->get('phone', '');
        $name   = (string) $state->get('name', 'Member');
        $locale = app()->getLocale();

        $member = Member::create([
            'baptism_name'                       => $name,
            'token'                              => Str::random(64),
            'whatsapp_phone'                     => $phone,
            'whatsapp_reminder_time'             => $input,
            'whatsapp_reminder_enabled'          => false,
            'whatsapp_confirmation_status'       => 'pending',
            'whatsapp_confirmation_requested_at' => now(),
            'whatsapp_language'                  => $locale,
            'locale'                             => $locale,
        ]);

        $this->syncTelegramChatId($member, $chatId);
        $state->clear();

        // Send WhatsApp opt-in prompt so they confirm via WhatsApp reply YES
        $this->ultraMsg->sendTextMessage(
            $phone,
            trans('app.whatsapp_confirmation_prompt_message', ['name' => $name])
        );

        $this->applyLocaleForActor($member);

        $successText = str_replace(':time', $input, __('app.telegram_subscribe_success'));
        $keyboard    = $this->mainMenuKeyboard($member, app(TelegramAuthService::class));

        return $this->reply($telegramService, $chatId, $successText, $keyboard);
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

        if ($actor instanceof Member && ! $this->memberHasLocale($actor)) {
            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
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
            __('app.telegram_quick_links_heading'),
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
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $easterTimezone = config('app.easter_timezone', 'Europe/London');
        $easterAt = CarbonImmutable::parse(
            config('app.easter_date', '2026-04-12 03:00'),
            $easterTimezone
        );
        $lentStartAt = CarbonImmutable::parse(
            config('app.lent_start_date', '2026-02-15 03:00'),
            $easterTimezone
        );

        $formatted = $this->contentFormatter->formatHomeCountdown($easterAt, $lentStartAt);
        $keyboard = $actor instanceof Member
            ? $this->mainMenuKeyboard($actor, $telegramAuthService)
            : $this->staffMainPageKeyboard();

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $formatted['text'],
            $keyboard,
            $messageId,
            $formatted['use_html'] ? 'HTML' : null
        );
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
        $adminLabel = $this->telegramBotBuilder->buttonLabel('admin', 'admin', __('app.telegram_builder_admin_panel'));

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
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        if ($actor instanceof Member && ! $this->memberHasLocale($actor)) {
            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.no_active_season'), []);
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->with(['weeklyTheme', 'mezmurs', 'books', 'references'])
            ->first();

        if (! $daily) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.no_content_today'), []);
        }

        $formatted = $this->contentFormatter->formatDaySection($daily, $actor, 'bible');
        $keyboard = $formatted['keyboard'] ?? ($actor instanceof Member
            ? $this->mainMenuKeyboard($actor, $telegramAuthService)
            : $this->staffMainPageKeyboard());
        $parseMode = ($formatted['use_html'] ?? false) ? 'HTML' : null;

        return $this->replyOrEdit($telegramService, $chatId, $formatted['text'], $keyboard, $messageId, $parseMode);
    }

    private function handleTodaySection(
        string $chatId,
        int $messageId,
        string $action,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $parts = explode('_', $action, 4);
        if (count($parts) < 4 || ($parts[0] ?? '') !== 'today' || ($parts[1] ?? '') !== 'sec') {
            return response()->json(['success' => true]);
        }

        $sectionCode = $parts[2] ?? '';
        $dailyId = (int) ($parts[3] ?? 0);
        $sectionMap = [
            'b' => 'bible',
            'm' => 'mezmur',
            's' => 'sinksar',
            'k' => 'books',
            'r' => 'reference',
            'f' => 'reflection',
        ];
        $section = $sectionMap[$sectionCode] ?? 'bible';

        $daily = DailyContent::query()
            ->where('id', $dailyId)
            ->where('is_published', true)
            ->with(['weeklyTheme', 'mezmurs', 'books', 'references'])
            ->first();

        if (! $daily) {
            return response()->json(['success' => true]);
        }

        $formatted = $this->contentFormatter->formatDaySection($daily, $actor, $section);
        $parseMode = ($formatted['use_html'] ?? false) ? 'HTML' : null;

        $telegramService->editMessageText(
            $chatId,
            $messageId,
            $formatted['text'],
            $formatted['keyboard'] ?? [],
            $parseMode
        );

        return response()->json(['success' => true]);
    }

    private function handleProgress(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService,
        string $period = 'all'
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        if (! $this->memberHasLocale($actor)) {
            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        try {
            $formatted = $this->contentFormatter->formatProgressForPeriod($actor, $period);
        } catch (\Throwable $e) {
            Log::error('[TelegramWebhook] Progress formatting failed.', [
                'period' => $period,
                'member_id' => $actor->id,
                'error' => $e->getMessage(),
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                '⚠️ '.__('app.progress').' — '.__('app.error_try_again'),
                ['inline_keyboard' => [[['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']]]],
                $messageId
            );
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $formatted['text'],
            $formatted['keyboard'],
            $messageId,
            $formatted['use_html'] ? 'HTML' : null
        );
    }

    private function handleProgressPeriod(
        string $chatId,
        int $messageId,
        string $action,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $period = match ($action) {
            'progress_daily' => 'daily',
            'progress_weekly' => 'weekly',
            'progress_monthly' => 'monthly',
            'progress_all' => 'all',
            default => 'all',
        };

        return $this->handleProgress($chatId, $messageId, $telegramAuthService, $telegramService, $period);
    }

    private function handleChecklist(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        if (! $this->memberHasLocale($actor)) {
            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        $season = LentSeason::query()->latest('id')->where('is_active', true)->first();
        if (! $season) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.no_active_season'), []);
        }

        $today = CarbonImmutable::now();
        $daily = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today->toDateString())
            ->where('is_published', true)
            ->first();

        if (! $daily) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.no_content_today'), []);
        }

        $activities = Activity::where('lent_season_id', $daily->lent_season_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $customActivities = $actor->customActivities()->orderBy('sort_order')->get();

        $checklist = MemberChecklist::where('member_id', $actor->id)
            ->where('daily_content_id', $daily->id)
            ->get()
            ->keyBy('activity_id');

        $customChecklist = MemberCustomChecklist::where('member_id', $actor->id)
            ->where('daily_content_id', $daily->id)
            ->get()
            ->keyBy('member_custom_activity_id');

        $formatted = $this->contentFormatter->formatChecklistMessage(
            $daily,
            $actor,
            $activities,
            $customActivities,
            $checklist,
            $customChecklist
        );

        return $this->replyOrEdit($telegramService, $chatId, $formatted['text'], $formatted['keyboard'], $messageId);
    }

    private function handleChecklistToggle(
        string $chatId,
        int $messageId,
        string $action,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof Member) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $parts = explode('_', $action, 4);
        if (count($parts) < 4 || ($parts[0] ?? '') !== 'check' || ! in_array($parts[1] ?? '', ['a', 'c'], true)) {
            return response()->json(['success' => true]);
        }

        $dailyId = (int) ($parts[2] ?? 0);
        $itemId = (int) ($parts[3] ?? 0);
        $isCustom = ($parts[1] ?? '') === 'c';

        $daily = DailyContent::query()->find($dailyId);
        if (! $daily || ! $daily->is_published) {
            return response()->json(['success' => true]);
        }

        if ($isCustom) {
            $entry = MemberCustomChecklist::where('member_id', $actor->id)
                ->where('daily_content_id', $dailyId)
                ->where('member_custom_activity_id', $itemId)
                ->first();
            $newState = $entry ? ! $entry->completed : true;
            MemberCustomChecklist::updateOrCreate(
                [
                    'member_id' => $actor->id,
                    'daily_content_id' => $dailyId,
                    'member_custom_activity_id' => $itemId,
                ],
                ['completed' => $newState]
            );
        } else {
            $entry = MemberChecklist::where('member_id', $actor->id)
                ->where('daily_content_id', $dailyId)
                ->where('activity_id', $itemId)
                ->first();
            $newState = $entry ? ! $entry->completed : true;
            MemberChecklist::updateOrCreate(
                [
                    'member_id' => $actor->id,
                    'daily_content_id' => $dailyId,
                    'activity_id' => $itemId,
                ],
                ['completed' => $newState]
            );
        }

        $activities = Activity::where('lent_season_id', $daily->lent_season_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $customActivities = $actor->customActivities()->orderBy('sort_order')->get();

        $checklist = MemberChecklist::where('member_id', $actor->id)
            ->where('daily_content_id', $daily->id)
            ->get()
            ->keyBy('activity_id');

        $customChecklist = MemberCustomChecklist::where('member_id', $actor->id)
            ->where('daily_content_id', $daily->id)
            ->get()
            ->keyBy('member_custom_activity_id');

        $formatted = $this->contentFormatter->formatChecklistMessage(
            $daily,
            $actor,
            $activities,
            $customActivities,
            $checklist,
            $customChecklist
        );

        $telegramService->editMessageText($chatId, $messageId, $formatted['text'], $formatted['keyboard']);

        return response()->json(['success' => true]);
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
            $actor = $token->actor;
            if ($actor instanceof Member && ! $this->memberHasLocale($actor)) {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_linked_success')."\n\n".__('app.telegram_choose_language'),
                    $this->languageChoiceKeyboard()
                );
            }
            $this->applyLocaleForActor($actor);
            $keyboard = $actor instanceof Member
                ? $this->mainMenuKeyboard($actor, $telegramAuthService)
                : $this->quickLinksKeyboard($actor, $telegramAuthService);

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_linked_success')."\n\n".__('app.telegram_menu_heading'),
                $keyboard
            );
        }

        if (preg_match('/^[A-Za-z0-9]{20,128}$/', $code)) {
            $member = Member::query()->where('token', $code)->first();
            if ($member) {
                $this->syncTelegramChatId($member, $chatId);
                if (! $this->memberHasLocale($member)) {
                    return $this->reply(
                        $telegramService,
                        $chatId,
                        __('app.telegram_linked_success')."\n\n".__('app.telegram_choose_language'),
                        $this->languageChoiceKeyboard()
                    );
                }
                $this->applyLocaleForActor($member);

                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_linked_success')."\n\n".__('app.telegram_menu_heading'),
                    $this->mainMenuKeyboard($member, $telegramAuthService)
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

        return url(route('auth.access', [
            'code' => $code,
            'purpose' => TelegramAuthService::PURPOSE_MEMBER_ACCESS,
        ]));
    }

    private function memberHomeSecureLink(Member $member, TelegramAuthService $telegramAuthService): string
    {
        $homeCode = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('telegram.webapp.home'),
            120
        );

        return url(route('telegram.webapp.home', [
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

        return url(route('auth.access', [
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

    private function staffActorFromChatId(string $chatId): ?User
    {
        $actor = $this->actorFromChatId($chatId);

        if (! $actor instanceof User || ! $actor->isAdmin()) {
            return null;
        }

        return $actor;
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
        array $replyMarkup = [],
        ?string $parseMode = null
    ): JsonResponse {
        $options = [];
        if (! empty($replyMarkup)) {
            $options['reply_markup'] = $replyMarkup;
        }
        if ($parseMode !== null) {
            $options['parse_mode'] = $parseMode;
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
        int $messageId = 0,
        ?string $parseMode = null
    ): JsonResponse {
        if ($messageId > 0) {
            $ok = $telegramService->editMessageText($chatId, $messageId, $text, $replyMarkup, $parseMode);
            if ($ok) {
                return response()->json(['success' => true]);
            }
            // Fallback: delete old message and send fresh one
            $telegramService->deleteMessage($chatId, $messageId);
        }

        return $this->reply($telegramService, $chatId, $text, $replyMarkup, $parseMode);
    }

    /**
     * Delete the previous message and send a new one. Keeps chat clean (no history).
     */
    private function replyAfterDelete(
        TelegramService $telegramService,
        string $chatId,
        int $messageId,
        string $text,
        array $replyMarkup = [],
        ?string $parseMode = null
    ): JsonResponse {
        if ($messageId > 0) {
            $telegramService->deleteMessage($chatId, $messageId);
        }

        return $this->reply($telegramService, $chatId, $text, $replyMarkup, $parseMode);
    }

    private function startChoiceKeyboard(): array
    {
        $appUrl = route('home');
        $locale = app()->getLocale();
        $langLabel = $locale === 'en'
            ? __('app.telegram_lang_switch_am')
            : __('app.telegram_lang_switch_en');
        $langCallback = $locale === 'en' ? 'lang_am' : 'lang_en';

        $rows = [
            [['text' => __('app.telegram_start_new'), 'web_app' => ['url' => $appUrl]]],
            [['text' => __('app.telegram_start_have_account'), 'callback_data' => 'have_account']],
            [['text' => $langLabel, 'callback_data' => $langCallback]],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function welcomeMessage(): string
    {
        return $this->telegramBotBuilder->welcomeMessage();
    }

    private function fallbackMessage(): string
    {
        return __('app.telegram_fallback_message');
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
        return __('app.telegram_menu_heading');
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

    /** Keyboard shown with the help message — includes Contact us button. */
    private function helpKeyboard(): array
    {
        $launch = $this->launchKeyboard();
        $contactRow = [['text' => '📞 '.__('app.telegram_help_contact_us'), 'url' => 'https://abuneteklehaymanot.org/contact-us/']];
        $launch['inline_keyboard'] = array_merge([$contactRow], $launch['inline_keyboard']);

        return $launch;
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

    // =========================================================================
    // Admin WhatsApp Linking
    // =========================================================================

    /**
     * Entry point when an unlinked user tries to use a feature that requires
     * an admin/editor/writer account. Creates a link_admin wizard state and
     * prompts for username.
     */
    private function handleLinkAdminStart(
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        TelegramBotState::startFor($chatId, 'link_admin', 'ask_phone');

        $text = '<b>🔗 '.__('app.telegram_link_heading')."</b>\n\n"
            .__('app.telegram_link_intro')."\n\n"
            .__('app.telegram_link_enter_phone');

        return $this->replyOrEdit($telegramService, $chatId, $text, [], $messageId, 'HTML');
    }

    /**
     * Handles plain-text input during the link_admin wizard.
     */
    private function handleLinkAdminText(
        string $chatId,
        string $text,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        return match ($state->step) {
            'ask_phone', 'ask_username' => $this->handleLinkAdminPhone($chatId, $text, $state, $telegramAuthService, $telegramService),
            'verify_code' => $this->handleLinkAdminCode($chatId, $text, $state, $telegramAuthService, $telegramService),
            default => response()->json(['success' => true]),
        };
    }

    /**
     * Looks up a staff user by WhatsApp phone number, sends a verification code,
     * and advances the wizard to verify_code.
     */
    private function handleLinkAdminPhone(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $normalized = normalizeUkWhatsAppPhone(trim($input));

        if ($normalized === null) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_phone_not_found')
            );
        }

        $user = User::query()->where('whatsapp_phone', $normalized)->first();

        if (! $user instanceof User) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_phone_not_found')
            );
        }

        // Generate a 6-digit numeric code
        $code = (string) random_int(100000, 999999);

        $sent = $this->ultraMsg->sendTextMessage(
            $normalized,
            "Your Abiy Tsom Telegram link code is: *{$code}*\n\nIt expires in 10 minutes. Do not share it."
        );

        if (! $sent) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_whatsapp_failed')
            );
        }

        $state->advance('verify_code', [
            'user_id' => $user->id,
            'code' => $code,
            'code_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_link_code_sent')
        );
    }

    private function handleLinkAdminCode(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $expectedCode = (string) $state->get('code', '');
        $codeExpiresAt = $state->get('code_expires_at');
        $userId = (int) $state->get('user_id', 0);

        // Check code expiry
        if ($codeExpiresAt && now()->isAfter($codeExpiresAt)) {
            $state->clear();

            return $this->reply(
                $telegramService,
                $chatId,
                "Code expired. Please start the linking process again by tapping 'Suggest'."
            );
        }

        if (trim($input) !== $expectedCode) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_wrong_code')
            );
        }

        $user = User::query()->find($userId);
        if (! $user instanceof User) {
            $state->clear();

            return $this->reply($telegramService, $chatId, 'User not found. Please try again.');
        }

        $this->syncTelegramChatId($user, $chatId);

        // Was there a pending next action?
        $pendingAction = $state->get('pending_action', '');
        $state->clear();

        $this->applyLocaleForActor($user);
        $successText = '✅ '.__('app.telegram_link_success')."\n\n".__('app.telegram_menu_heading');
        $keyboard = $this->mainMenuKeyboard($user, $telegramAuthService);

        // If they were trying to suggest, immediately start the wizard
        if ($pendingAction === 'suggest') {
            return $this->reply($telegramService, $chatId, $successText, $keyboard);
        }

        return $this->reply($telegramService, $chatId, $successText, $keyboard);
    }

    // =========================================================================
    // Suggestion Wizard & My Suggestions
    // =========================================================================

    /**
     * Central router for all suggest-related callbacks and actions.
     */
    private function handleSuggestCallback(
        string $chatId,
        int $messageId,
        string $action,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->actorFromChatId($chatId);

        if ($actor instanceof Member) {
            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_staff_portal_access_denied'),
                $this->mainMenuKeyboard($actor, $telegramAuthService),
                $messageId
            );
        }

        // Must be a linked admin/editor/writer User.
        if (! $actor instanceof User || ! $actor->isAdmin()) {
            // Start WhatsApp linking inline, remembering that they wanted to suggest
            TelegramBotState::startFor($chatId, 'link_admin', 'ask_phone', [
                'pending_action' => str_starts_with($action, 'suggest') ? 'suggest' : $action,
            ]);

            $text = '🔗 '.__('app.telegram_link_heading')."\n\n"
                .__('app.telegram_link_intro')."\n\n"
                .__('app.telegram_link_enter_phone');

            return $this->replyOrEdit($telegramService, $chatId, $text, [], $messageId);
        }

        // ── My Suggestions ────────────────────────────────────────────────
        if ($action === 'my_suggestions') {
            return $this->handleMySuggestions($chatId, $messageId, $actor, $telegramAuthService, $telegramService);
        }

        // ── Entry: start wizard ───────────────────────────────────────────
        if ($action === 'suggest') {
            return $this->startSuggestWizard($chatId, $messageId, $telegramService);
        }

        if (str_starts_with($action, 'suggest_lang_')) {
            $lang = str_replace('suggest_lang_', '', $action); // 'en' or 'am'
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            $state->advance('choose_area', ['language' => $lang]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_area'),
                $this->structuredSuggestAreaKeyboard(),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_area_')) {
            $area = str_replace('suggest_area_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('choose_month', ['content_area' => $area]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_month'),
                $this->structuredSuggestMonthKeyboard(),
                $messageId
            );
        }

        if ($action === 'suggest_today' || $action === 'suggest_tomorrow') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $gcDate = $action === 'suggest_tomorrow'
                ? Carbon::today()->addDay()
                : Carbon::today();
            $eth = $this->ethiopianCalendar->gregorianToEthiopian($gcDate);

            $state->advance('confirm_date', [
                'ethiopian_month' => $eth['month'],
                'ethiopian_day' => $eth['day'],
                'gregorian_date' => $gcDate->format('Y-m-d'),
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestConfirmDatePrompt($state->data ?? []),
                $this->structuredSuggestConfirmDateKeyboard(),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_month_')) {
            $month = (int) str_replace('suggest_month_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('choose_day', ['ethiopian_month' => $month]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_day'),
                $this->structuredSuggestDayKeyboard($month),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_day_')) {
            $day = (int) str_replace('suggest_day_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('confirm_date', ['ethiopian_day' => $day]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestConfirmDatePrompt($state->data ?? []),
                $this->structuredSuggestConfirmDateKeyboard(),
                $messageId
            );
        }

        if ($action === 'suggest_confirm_date_yes') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $nextStep = match ((string) $state->get('content_area', '')) {
                'synaxarium' => 'choose_scope',
                'lectionary' => 'choose_lectionary_section',
                'bible_reading' => 'enter_reference',
                'mezmur', 'spiritual_book' => 'enter_title',
                'reference_resource' => 'choose_resource_type',
                'daily_message' => 'enter_title',
                default => 'choose_area',
            };

            $state->advance($nextStep);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($nextStep, $state),
                $messageId
            );
        }

        if ($action === 'suggest_confirm_date_change') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('choose_month');

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_month'),
                $this->structuredSuggestMonthKeyboard(),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_scope_')) {
            $scope = str_replace('suggest_scope_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('enter_title', ['entry_scope' => $scope]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt('enter_title', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('enter_title', $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_lectionary_section_')) {
            $section = str_replace('suggest_lectionary_section_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $nextStep = match ($section) {
                'pauline', 'catholic', 'gospel' => 'choose_book',
                'acts', 'mesbak' => 'enter_chapter',
                'qiddase', 'title_description' => 'enter_detail',
                default => 'enter_reference',
            };
            $state->advance($nextStep, ['lectionary_section' => $section]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($nextStep, $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_book_')) {
            $bookKey = str_replace('suggest_book_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $books = $this->lectionaryBooksForSection((string) $state->get('lectionary_section', ''));
            $bookLabel = $books[$bookKey] ?? $bookKey;
            $state->advance('enter_chapter', [
                'lectionary_book' => $bookKey,
                'lectionary_book_label' => $bookLabel,
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt('enter_chapter', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('enter_chapter', $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_geez_line_')) {
            $line = str_replace('suggest_geez_line_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('enter_detail', ['lectionary_geez_line' => $line]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt('enter_detail', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('enter_detail', $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_resource_type_')) {
            $resourceType = str_replace('suggest_resource_type_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('enter_title', ['resource_type' => $resourceType]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt('enter_title', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('enter_title', $state),
                $messageId
            );
        }

        if ($action === 'suggest_main_yes' || $action === 'suggest_main_no') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('preview', ['is_main' => $action === 'suggest_main_yes']);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_skip') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->advanceSuggestStep($chatId, $messageId, '', $state, $telegramService);
        }

        if ($action === 'suggest_confirm') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->confirmSuggestion($chatId, $messageId, $actor, $state, $telegramAuthService, $telegramService);
        }

        if ($action === 'suggest_back') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            return $this->handleSuggestBack($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_cancel') {
            TelegramBotState::query()->where('chat_id', $chatId)->where('action', 'suggest')->delete();

            $keyboard = $this->mainMenuKeyboard($actor, $telegramAuthService);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_cancelled'),
                $keyboard,
                $messageId
            );
        }

        return response()->json(['success' => true]);
    }

    private function startSuggestWizard(
        string $chatId,
        int $messageId,
        TelegramService $telegramService
    ): JsonResponse {
        TelegramBotState::startFor($chatId, 'suggest', 'choose_language');

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '💡 '.__('app.telegram_suggest')."\n\n".__('app.telegram_suggest_choose_language'),
            $this->suggestLanguageKeyboard(),
            $messageId
        );
    }

    private function suggestLanguageKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🇬🇧 English', 'callback_data' => 'suggest_lang_en'],
                ['text' => '🇪🇹 አማርኛ', 'callback_data' => 'suggest_lang_am'],
            ],
            [['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel']],
        ]];
    }

    /**
     * Handles plain-text input during an active suggestion wizard step.
     */
    private function handleSuggestTextInput(
        string $chatId,
        string $text,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        return $this->advanceSuggestStep($chatId, 0, $text, $state, $telegramService);
    }

    /**
     * Advance the suggestion wizard by one step, saving input and prompting
     * for the next field, or showing the preview when all fields are collected.
     */
    private function advanceSuggestStep(
        string $chatId,
        int $messageId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $currentStep = $state->step;
        $input = trim($input);

        $fieldForStep = [
            'enter_reference' => 'reference',
            'enter_chapter' => 'lectionary_chapter',
            'enter_verse_range' => 'lectionary_verse_range',
            'enter_title' => 'title',
            'enter_url' => 'url',
            'enter_detail' => 'content_detail',
        ];

        $mergeData = [];
        if (isset($fieldForStep[$currentStep])) {
            if ($currentStep === 'enter_chapter' && $input !== '' && ! ctype_digit(trim($input))) {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_invalid_chapter'),
                    $this->structuredSuggestStepKeyboard($currentStep)
                );
            }
            if ($currentStep === 'enter_verse_range' && $input !== '' && ! $this->suggestStepInputLooksVerseRange($input)) {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_invalid_verse_range'),
                    $this->structuredSuggestStepKeyboard($currentStep)
                );
            }
            if ($input === '') {
                if ($this->structuredSuggestStepIsOptional($state, $currentStep)) {
                    $nextStep = $this->structuredSuggestNextStep($state, $currentStep);

                    if ($nextStep === 'preview') {
                        $state->advance('preview');

                        return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                    }

                    $state->advance($nextStep);

                    return $this->reply(
                        $telegramService,
                        $chatId,
                        $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                        $this->structuredSuggestKeyboardForStep($nextStep, $state)
                    );
                }

                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_value_required'),
                    $this->structuredSuggestStepKeyboard($currentStep)
                );
            }

            if ($currentStep === 'enter_url' && ! $this->suggestStepInputLooksUrl($input)) {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_invalid_url'),
                    $this->structuredSuggestStepKeyboard($currentStep)
                );
            }

            $mergeData[$fieldForStep[$currentStep]] = $input;
        }

        $nextStep = $this->structuredSuggestNextStep($state, $currentStep);

        if ($nextStep === 'preview') {
            $state->advance('preview', $mergeData);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        $state->advance($nextStep, $mergeData);

        return $this->reply(
            $telegramService,
            $chatId,
            $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
            $this->structuredSuggestKeyboardForStep($nextStep, $state)
        );
    }

    private function showSuggestPreview(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $data = $state->data ?? [];
        $lang = strtoupper((string) ($data['language'] ?? '?'));
        $contentArea = (string) ($data['content_area'] ?? '');
        $typeLabel = $this->structuredSuggestAreaLabel($contentArea);

        $lines = [
            '<b>📋 '.__('app.telegram_suggest_preview').'</b>',
            '',
            "<b>Type:</b> {$typeLabel} [{$lang}]",
            '<b>Date:</b> '.htmlspecialchars($this->structuredSuggestDateLabel($data), ENT_QUOTES, 'UTF-8'),
        ];

        if ($contentArea === 'synaxarium' && ! empty($data['entry_scope'])) {
            $lines[] = '<b>Scope:</b> '.htmlspecialchars(
                $data['entry_scope'] === 'yearly'
                    ? __('app.telegram_suggest_scope_yearly')
                    : __('app.telegram_suggest_scope_monthly'),
                ENT_QUOTES,
                'UTF-8'
            );
        }
        if ($contentArea === 'lectionary' && ! empty($data['lectionary_section'])) {
            $lines[] = '<b>Section:</b> '.htmlspecialchars(
                $this->structuredSuggestLectionarySectionLabel((string) $data['lectionary_section']),
                ENT_QUOTES,
                'UTF-8'
            );
        }
        $ref = ($contentArea === 'lectionary')
            ? $this->structuredSuggestBuildLectionaryReference($data)
            : ($data['reference'] ?? null);
        if (! empty($ref)) {
            $lines[] = '<b>Reference:</b> '.htmlspecialchars((string) $ref, ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['title'])) {
            $lines[] = '<b>Title:</b> '.htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['resource_type'])) {
            $lines[] = '<b>Resource Type:</b> '.htmlspecialchars(
                $this->structuredSuggestResourceTypeLabel((string) $data['resource_type']),
                ENT_QUOTES,
                'UTF-8'
            );
        }
        if (! empty($data['image_path'])) {
            $lines[] = '<b>Image:</b> '.htmlspecialchars(__('app.telegram_suggest_image_attached'), ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['url'])) {
            $lines[] = '<b>Link:</b> '.htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');
        }
        if ($contentArea === 'synaxarium' && array_key_exists('is_main', $data)) {
            $lines[] = '<b>Main celebration:</b> '.htmlspecialchars(
                $data['is_main'] ? __('app.yes') : __('app.no'),
                ENT_QUOTES,
                'UTF-8'
            );
        }
        if (! empty($data['content_detail'])) {
            $lines[] = '<b>Details:</b> '.htmlspecialchars($data['content_detail'], ENT_QUOTES, 'UTF-8');
        }

        $keyboard = ['inline_keyboard' => [
            [['text' => '✅ '.__('app.telegram_suggest_confirm'), 'callback_data' => 'suggest_confirm']],
            [
                ['text' => '✏️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            implode("\n", $lines),
            $keyboard,
            $messageId,
            'HTML'
        );
    }

    private function confirmSuggestion(
        string $chatId,
        int $messageId,
        User $user,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $data = $state->data ?? [];
        $contentArea = (string) ($data['content_area'] ?? 'daily_message');
        $language = (string) ($data['language'] ?? 'en');

        try {
            ContentSuggestion::create([
                'user_id' => $user->id,
                'source' => 'telegram',
                'type' => $this->structuredSuggestLegacyType($contentArea),
                'content_area' => $contentArea,
                'language' => $language,
                'ethiopian_month' => $data['ethiopian_month'] ?? null,
                'ethiopian_day' => $data['ethiopian_day'] ?? null,
                'entry_scope' => $data['entry_scope'] ?? null,
                'title' => $this->structuredSuggestStoredTitle($data),
                'reference' => $this->structuredSuggestStoredReference($data),
                'url' => $data['url'] ?? null,
                'content_detail' => $data['content_detail'] ?? null,
                'image_path' => $data['image_path'] ?? null,
                'structured_payload' => $this->structuredSuggestPayload($data),
                'submitter_name' => $user->name,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('[TelegramBot] confirmSuggestion DB error.', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                '❌ '.__('app.telegram_suggest_error'),
                [],
                $messageId
            );
        }

        $state->clear();

        $keyboard = $this->mainMenuKeyboard($user, $telegramAuthService);

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '✅ '.__('app.telegram_suggest_submitted'),
            $keyboard,
            $messageId
        );
    }

    private function handleMySuggestions(
        string $chatId,
        int $messageId,
        User $user,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $suggestions = ContentSuggestion::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        if ($suggestions->isEmpty()) {
            $keyboard = ['inline_keyboard' => [
                [['text' => '💡 '.__('app.telegram_suggest'), 'callback_data' => 'suggest']],
                [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']],
            ]];

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                '📋 '.__('app.telegram_my_suggestions')."\n\n".__('app.telegram_suggest_no_suggestions'),
                $keyboard,
                $messageId
            );
        }

        $statusIcon = [
            'pending' => '⏳',
            'reviewed' => '👀',
            'approved' => '✅',
            'rejected' => '❌',
            'used' => '⭐',
        ];

        $typeIcon = [
            'bible' => '📖',
            'mezmur' => '🎵',
            'sinksar' => '📖',
            'book' => '📚',
            'reference' => '🔗',
        ];

        $lines = ['<b>📋 '.__('app.telegram_my_suggestions').'</b>', ''];

        foreach ($suggestions as $s) {
            $icon = $statusIcon[$s->status] ?? '•';
            $tIcon = $typeIcon[$s->type] ?? '•';
            $label = $s->title ?? $s->reference ?? $s->type;
            $label = mb_strlen((string) $label) > 40
                ? mb_substr((string) $label, 0, 37).'…'
                : (string) $label;
            $lines[] = "{$icon} {$tIcon} ".htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                .' <i>'.ucfirst($s->status).'</i>';
        }

        $keyboard = ['inline_keyboard' => [
            [['text' => '💡 '.__('app.telegram_suggest'), 'callback_data' => 'suggest']],
            [['text' => '◀️ '.__('app.menu'), 'callback_data' => 'menu']],
        ]];

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            implode("\n", $lines),
            $keyboard,
            $messageId,
            'HTML'
        );
    }

    // ---- Suggestion wizard step helpers ─────────────────────────────────────

    /** Go back one step in the wizard, re-prompting with existing value pre-filled. */
    private function handleSuggestBack(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $prevStep = $this->structuredSuggestPreviousStep($state, $state->step);
        $state->advance($prevStep);

        $fieldForStep = [
            'enter_reference' => 'reference',
            'enter_chapter' => 'lectionary_chapter',
            'enter_verse_range' => 'lectionary_verse_range',
            'enter_title' => 'title',
            'enter_detail' => 'content_detail',
        ];

        $existing = isset($fieldForStep[$prevStep]) ? ((string) $state->get($fieldForStep[$prevStep], '')) : '';
        $prompt = $this->structuredSuggestPrompt($prevStep, $state->data ?? []);
        if ($existing !== '') {
            $prompt .= "\n\n<i>".__('app.telegram_suggest_current')." ".htmlspecialchars($existing, ENT_QUOTES, 'UTF-8')."</i>";
            $prompt .= "\n".__('app.telegram_suggest_type_to_replace');
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $prompt,
            $this->structuredSuggestKeyboardForStep($prevStep, $state),
            $messageId,
            'HTML'
        );
    }

    /** Returns the step before the current one, or 'choose_type' if at the start. */
    private function suggestPreviousStep(string $type, string $currentStep): string
    {
        $flow = match ($type) {
            'bible' => ['enter_reference', 'enter_url', 'enter_detail'],
            'sinksar' => ['enter_title', 'enter_url', 'enter_detail'],
            'mezmur', 'book' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail'],
            'reference' => ['enter_title', 'enter_url', 'enter_detail'],
            default => ['enter_title', 'enter_url', 'enter_detail'],
        };

        // When on the preview step, the previous step is the last text-input step
        if ($currentStep === 'preview') {
            return end($flow) ?: 'choose_type';
        }

        $idx = array_search($currentStep, $flow, true);
        if ($idx === false || $idx === 0) {
            return 'choose_type';
        }

        return $flow[$idx - 1];
    }

    /**
     * Determines whether user input looks like a verse range (e.g. 1-5, 1,3,5, 1).
     */
    private function suggestStepInputLooksVerseRange(string $input): bool
    {
        $value = trim($input);
        if ($value === '') {
            return false;
        }

        return (bool) preg_match('/^[\d\s,\-]+$/', $value);
    }

    /**
     * Determines whether user input on the URL step looks like a real link.
     */
    private function suggestStepInputLooksUrl(string $input): bool
    {
        $value = trim($input);
        if ($value === '') {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        return (bool) preg_match(
            '/^(?:https?:\/\/)?(?:www\.)?[\w.-]+\.[a-z]{2,}(?:\/[^\s]*)?$/i',
            $value
        );
    }

    /** Builds the keyboard for a text-input step (Skip for optional, Back, Cancel). */
    private function suggestStepKeyboard(string $step, string $previousStep): array
    {
        $rows = [];

        if (in_array($step, ['enter_author', 'enter_url', 'enter_detail'], true)) {
            $rows[] = [['text' => '⏭ '.__('app.telegram_suggest_skip'), 'callback_data' => 'suggest_skip']];
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /** Returns the first text-input step for a given suggestion type. */
    private function suggestFirstStep(string $type): string
    {
        return match ($type) {
            'bible' => 'enter_reference',
            'reference' => 'enter_title',
            default => 'enter_title', // mezmur, sinksar, book
        };
    }

    /**
     * Returns the next wizard step after the current one for the given type.
     * Returns 'preview' when there are no more fields.
     */
    private function suggestNextStep(string $type, string $currentStep): string
    {
        $flow = match ($type) {
            'bible' => ['enter_reference', 'enter_url', 'enter_detail', 'preview'],
            'sinksar' => ['enter_title', 'enter_url', 'enter_detail', 'preview'],
            'mezmur', 'book' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail', 'preview'],
            'reference' => ['enter_title', 'enter_url', 'enter_detail', 'preview'],
            default => ['enter_title', 'enter_url', 'enter_detail', 'preview'],
        };

        $idx = array_search($currentStep, $flow, true);
        if ($idx === false) {
            return 'preview';
        }

        return $flow[$idx + 1] ?? 'preview';
    }

    /** Returns the prompt text for a given wizard step. */
    private function suggestStepPrompt(string $step, string $type): string
    {
        return match ($step) {
            'enter_reference' => __('app.telegram_suggest_enter_reference'),
            'enter_title' => __('app.telegram_suggest_enter_title'),
            'enter_author' => __('app.telegram_suggest_enter_author'),
            'enter_url' => __('app.telegram_suggest_enter_url'),
            'enter_detail' => __('app.telegram_suggest_enter_detail'),
            default => __('app.telegram_suggest_enter_detail'),
        };
    }

    private function suggestTypeKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '📖 Bible', 'callback_data' => 'suggest_type_bible'],
                ['text' => '🎵 Mezmur', 'callback_data' => 'suggest_type_mezmur'],
            ],
            [
                ['text' => '📖 Sinksar', 'callback_data' => 'suggest_type_sinksar'],
                ['text' => '📚 Book', 'callback_data' => 'suggest_type_book'],
            ],
            [
                ['text' => '🔗 Reference', 'callback_data' => 'suggest_type_reference'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function handleSuggestPhotoInput(
        string $chatId,
        array $photos,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        if ($state->step !== 'await_image') {
            return response()->json(['success' => true, 'message' => 'Photo ignored.']);
        }

        $photo = collect($photos)
            ->filter(fn ($item) => is_array($item) && filled($item['file_id'] ?? null))
            ->sortBy(fn ($item) => (int) ($item['file_size'] ?? 0))
            ->last();

        if (! is_array($photo) || blank($photo['file_id'] ?? null)) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image')
            );
        }

        $download = $telegramService->downloadFile((string) $photo['file_id']);
        if (! is_array($download)) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image')
            );
        }

        $extension = strtolower((string) ($download['extension'] ?? 'jpg'));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $path = 'telegram-suggestions/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->put($path, $download['contents'])) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image')
            );
        }

        $state->advance('enter_detail', ['image_path' => $path]);

        return $this->reply(
            $telegramService,
            $chatId,
            $this->structuredSuggestPrompt('enter_detail', $state->data ?? []),
            $this->structuredSuggestKeyboardForStep('enter_detail', $state)
        );
    }

    private function structuredSuggestAreaKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => '📖 '.__('app.telegram_suggest_area_bible_reading'), 'callback_data' => 'suggest_area_bible_reading']],
            [['text' => '📜 '.__('app.telegram_suggest_area_lectionary'), 'callback_data' => 'suggest_area_lectionary']],
            [['text' => '🎵 '.__('app.telegram_suggest_area_mezmur'), 'callback_data' => 'suggest_area_mezmur']],
            [['text' => '🕊️ '.__('app.telegram_suggest_area_synaxarium'), 'callback_data' => 'suggest_area_synaxarium']],
            [['text' => '📚 '.__('app.telegram_suggest_area_spiritual_book'), 'callback_data' => 'suggest_area_spiritual_book']],
            [['text' => '🔗 '.__('app.telegram_suggest_area_reference_resource'), 'callback_data' => 'suggest_area_reference_resource']],
            [['text' => '💬 '.__('app.telegram_suggest_area_daily_message'), 'callback_data' => 'suggest_area_daily_message']],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestMonthKeyboard(): array
    {
        $rows = [
            [
                ['text' => '📅 '.__('app.telegram_suggest_today'), 'callback_data' => 'suggest_today'],
                ['text' => '📆 '.__('app.telegram_suggest_tomorrow'), 'callback_data' => 'suggest_tomorrow'],
            ],
        ];

        $months = [
            1 => 'Meskerem / መስከረም',
            2 => 'Tikimt / ጥቅምት',
            3 => 'Hidar / ኅዳር',
            4 => 'Tahsas / ታኅሣሥ',
            5 => 'Tir / ጥር',
            6 => 'Yekatit / የካቲት',
            7 => 'Megabit / መጋቢት',
            8 => 'Miyazia / ሚያዝያ',
            9 => 'Ginbot / ግንቦት',
            10 => 'Sene / ሰኔ',
            11 => 'Hamle / ሐምሌ',
            12 => 'Nehase / ነሐሴ',
            13 => 'Pagumen / ጳጉሜን',
        ];

        foreach ($months as $month => $label) {
            $rows[] = [[
                'text' => $label,
                'callback_data' => 'suggest_month_'.$month,
            ]];
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function structuredSuggestDayKeyboard(int $month): array
    {
        $maxDay = $month === 13 ? 6 : 30;
        $rows = [];

        for ($day = 1; $day <= $maxDay; $day += 5) {
            $row = [];
            for ($offset = 0; $offset < 5; $offset++) {
                $value = $day + $offset;
                if ($value > $maxDay) {
                    break;
                }

                $row[] = [
                    'text' => (string) $value,
                    'callback_data' => 'suggest_day_'.$value,
                ];
            }

            $rows[] = $row;
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function structuredSuggestScopeKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => __('app.telegram_suggest_scope_yearly'), 'callback_data' => 'suggest_scope_yearly']],
            [['text' => __('app.telegram_suggest_scope_monthly'), 'callback_data' => 'suggest_scope_monthly']],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    /** @return array<string, string> Amharic => English */
    private function lectionaryBooksForSection(string $section): array
    {
        return match ($section) {
            'pauline' => self::PAULINE_BOOKS,
            'catholic' => self::CATHOLIC_BOOKS,
            'gospel' => self::GOSPEL_BOOKS,
            default => [],
        };
    }

    private function structuredSuggestLectionaryBookKeyboard(TelegramBotState $state): array
    {
        $section = (string) $state->get('lectionary_section', '');
        $books = $this->lectionaryBooksForSection($section);
        $rows = [];

        foreach ($books as $amharic => $english) {
            $rows[] = [[
                'text' => $amharic,
                'callback_data' => 'suggest_book_'.$amharic,
            ]];
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function structuredSuggestGeezLineKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => __('app.telegram_suggest_geez_line_1'), 'callback_data' => 'suggest_geez_line_1']],
            [['text' => __('app.telegram_suggest_geez_line_2'), 'callback_data' => 'suggest_geez_line_2']],
            [['text' => __('app.telegram_suggest_geez_line_3'), 'callback_data' => 'suggest_geez_line_3']],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestConfirmDatePrompt(array $data): string
    {
        $month = (int) ($data['ethiopian_month'] ?? 0);
        $day = (int) ($data['ethiopian_day'] ?? 0);
        $gcDate = (string) ($data['gregorian_date'] ?? '');

        $monthNames = [
            1 => 'Meskerem', 2 => 'Tikimt', 3 => 'Hidar', 4 => 'Tahsas', 5 => 'Tir', 6 => 'Yekatit',
            7 => 'Megabit', 8 => 'Miyazia', 9 => 'Ginbot', 10 => 'Sene', 11 => 'Hamle', 12 => 'Nehase',
            13 => 'Pagumen',
        ];
        $ethLabel = ($monthNames[$month] ?? '?').' '.$day;

        if ($gcDate !== '') {
            try {
                $carbon = Carbon::parse($gcDate);
                $gcLabel = $carbon->format('M j, Y');
            } catch (\Throwable) {
                $gcLabel = $gcDate;
            }
        } elseif ($month > 0 && $day > 0) {
            try {
                $carbon = $this->ethiopianCalendar->ethiopianToGregorian($month, $day);
                $gcLabel = $carbon->format('M j, Y');
            } catch (\Throwable) {
                $gcLabel = __('app.telegram_suggest_unknown_date');
            }
        } else {
            $gcLabel = __('app.telegram_suggest_unknown_date');
        }

        return __('app.telegram_suggest_confirm_date', [
            'ethiopian' => $ethLabel,
            'gregorian' => $gcLabel,
        ]);
    }

    private function structuredSuggestConfirmDateKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '✅ '.__('app.telegram_suggest_confirm_date_yes'), 'callback_data' => 'suggest_confirm_date_yes'],
                ['text' => '✏️ '.__('app.telegram_suggest_confirm_date_change'), 'callback_data' => 'suggest_confirm_date_change'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestLectionarySectionKeyboard(): array
    {
        $sections = [
            'title_description',
            'pauline',
            'catholic',
            'acts',
            'mesbak',
            'gospel',
            'qiddase',
        ];

        $rows = [];
        foreach ($sections as $section) {
            $rows[] = [[
                'text' => $this->structuredSuggestLectionarySectionLabel($section),
                'callback_data' => 'suggest_lectionary_section_'.$section,
            ]];
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function structuredSuggestResourceTypeKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => __('app.telegram_suggest_resource_type_video'), 'callback_data' => 'suggest_resource_type_video']],
            [['text' => __('app.telegram_suggest_resource_type_website'), 'callback_data' => 'suggest_resource_type_website']],
            [['text' => __('app.telegram_suggest_resource_type_file'), 'callback_data' => 'suggest_resource_type_file']],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestMainChoiceKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => __('app.yes'), 'callback_data' => 'suggest_main_yes'],
                ['text' => __('app.no'), 'callback_data' => 'suggest_main_no'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestKeyboardForStep(string $step, TelegramBotState $state): array
    {
        return match ($step) {
            'choose_language' => $this->suggestLanguageKeyboard(),
            'choose_area' => $this->structuredSuggestAreaKeyboard(),
            'choose_month' => $this->structuredSuggestMonthKeyboard(),
            'choose_day' => $this->structuredSuggestDayKeyboard((int) $state->get('ethiopian_month', 1)),
            'choose_scope' => $this->structuredSuggestScopeKeyboard(),
            'choose_lectionary_section' => $this->structuredSuggestLectionarySectionKeyboard(),
            'confirm_date' => $this->structuredSuggestConfirmDateKeyboard(),
            'choose_book' => $this->structuredSuggestLectionaryBookKeyboard($state),
            'choose_geez_line' => $this->structuredSuggestGeezLineKeyboard(),
            'choose_resource_type' => $this->structuredSuggestResourceTypeKeyboard(),
            'choose_main' => $this->structuredSuggestMainChoiceKeyboard(),
            default => $this->structuredSuggestStepKeyboard($step),
        };
    }

    private function structuredSuggestStepKeyboard(string $step): array
    {
        $rows = [];

        if (in_array($step, ['await_image', 'enter_url', 'enter_detail'], true)) {
            $rows[] = [['text' => '⏭ '.__('app.telegram_suggest_skip'), 'callback_data' => 'suggest_skip']];
        }

        $rows[] = [
            ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    private function structuredSuggestPrompt(string $step, array $data): string
    {
        $contentArea = (string) ($data['content_area'] ?? '');

        return match ($step) {
            'choose_language' => __('app.telegram_suggest_choose_language'),
            'choose_area' => __('app.telegram_suggest_choose_area'),
            'choose_month' => __('app.telegram_suggest_choose_month'),
            'choose_day' => __('app.telegram_suggest_choose_day'),
            'choose_scope' => __('app.telegram_suggest_choose_scope'),
            'choose_lectionary_section' => __('app.telegram_suggest_choose_lectionary_section'),
            'confirm_date' => $this->structuredSuggestConfirmDatePrompt($data),
            'choose_book' => __('app.telegram_suggest_choose_book'),
            'choose_geez_line' => __('app.telegram_suggest_choose_geez_line'),
            'choose_resource_type' => __('app.telegram_suggest_choose_resource_type'),
            'enter_chapter' => match ((string) ($data['lectionary_section'] ?? '')) {
                'mesbak' => __('app.telegram_suggest_enter_psalm_number'),
                default => __('app.telegram_suggest_enter_chapter'),
            },
            'enter_verse_range' => __('app.telegram_suggest_enter_verse_range'),
            'enter_title' => match ($contentArea) {
                'synaxarium' => __('app.telegram_suggest_enter_saint_name'),
                'daily_message' => __('app.telegram_suggest_enter_daily_message_title'),
                'mezmur' => __('app.telegram_suggest_enter_mezmur_title'),
                'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_title'),
                'reference_resource' => __('app.telegram_suggest_enter_resource_title'),
                default => __('app.telegram_suggest_enter_title'),
            },
            'enter_reference' => match ($contentArea) {
                'bible_reading', 'lectionary' => __('app.telegram_suggest_enter_bible_reading_reference'),
                default => match ((string) ($data['lectionary_section'] ?? '')) {
                'title_description' => __('app.telegram_suggest_enter_lectionary_summary'),
                'qiddase' => __('app.telegram_suggest_enter_qiddase_name'),
                default => __('app.telegram_suggest_enter_lectionary_reference'),
                },
            },
            'enter_url' => match ($contentArea) {
                'mezmur' => __('app.telegram_suggest_enter_mezmur_link'),
                'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_link'),
                'reference_resource' => __('app.telegram_suggest_enter_resource_link'),
                default => __('app.telegram_suggest_enter_url'),
            },
            'await_image' => __('app.telegram_suggest_send_photo'),
            'enter_detail' => match ($contentArea) {
                'synaxarium' => __('app.telegram_suggest_enter_saint_description'),
                'daily_message' => __('app.telegram_suggest_enter_daily_message_body'),
                'mezmur' => __('app.telegram_suggest_enter_mezmur_notes'),
                'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_notes'),
                'reference_resource' => __('app.telegram_suggest_enter_resource_notes'),
                'bible_reading', 'lectionary' => __('app.telegram_suggest_enter_bible_reading_notes'),
                default => __('app.telegram_suggest_enter_detail'),
            },
            'choose_main' => __('app.telegram_suggest_choose_main_celebration'),
            default => __('app.telegram_suggest_enter_detail'),
        };
    }

    private function structuredSuggestFlow(TelegramBotState $state): array
    {
        $contentArea = (string) $state->get('content_area', '');
        $lectionarySection = (string) $state->get('lectionary_section', '');

        if ($contentArea === 'lectionary') {
            $base = ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'choose_lectionary_section'];
            $refSteps = match ($lectionarySection) {
                'pauline', 'catholic', 'gospel' => ['choose_book', 'enter_chapter', 'enter_verse_range'],
                'acts' => ['enter_chapter', 'enter_verse_range'],
                'mesbak' => ['enter_chapter', 'enter_verse_range', 'choose_geez_line'],
                'qiddase', 'title_description' => [],
                default => ['enter_reference'],
            };

            return array_merge($base, ['confirm_date'], $refSteps, ['enter_detail', 'preview']);
        }

        return match ($contentArea) {
            'bible_reading' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'enter_reference', 'enter_detail', 'preview'],
            'mezmur' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'enter_title', 'enter_url', 'enter_detail', 'preview'],
            'synaxarium' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'choose_scope', 'enter_title', 'await_image', 'enter_detail', 'choose_main', 'preview'],
            'spiritual_book' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'enter_title', 'enter_url', 'enter_detail', 'preview'],
            'reference_resource' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'choose_resource_type', 'enter_title', 'enter_url', 'enter_detail', 'preview'],
            'daily_message' => ['choose_language', 'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'enter_title', 'enter_detail', 'preview'],
            default => ['choose_language', 'choose_area'],
        };
    }

    private function structuredSuggestStepIsOptional(TelegramBotState $state, string $step): bool
    {
        $contentArea = (string) $state->get('content_area', '');

        return match ($step) {
            'await_image' => true,
            'enter_url' => in_array($contentArea, ['mezmur', 'spiritual_book'], true),
            'enter_detail' => in_array($contentArea, ['mezmur', 'spiritual_book', 'reference_resource', 'bible_reading', 'lectionary'], true),
            default => false,
        };
    }

    private function structuredSuggestNextStep(TelegramBotState $state, string $currentStep): string
    {
        if ($currentStep === 'await_image') {
            return 'enter_detail';
        }

        $flow = $this->structuredSuggestFlow($state);
        $index = array_search($currentStep, $flow, true);

        if ($index === false) {
            return 'preview';
        }

        return $flow[$index + 1] ?? 'preview';
    }

    private function structuredSuggestPreviousStep(TelegramBotState $state, string $currentStep): string
    {
        $flow = $this->structuredSuggestFlow($state);
        if ($currentStep === 'preview') {
            $steps = array_values(array_filter($flow, fn ($step) => $step !== 'preview'));

            return end($steps) ?: 'choose_language';
        }

        $index = array_search($currentStep, $flow, true);
        if ($index === false || $index === 0) {
            return 'choose_language';
        }

        return $flow[$index - 1];
    }

    private function structuredSuggestAreaLabel(string $contentArea): string
    {
        return match ($contentArea) {
            'lectionary' => '📜 '.__('app.telegram_suggest_area_lectionary'),
            'bible_reading' => '📖 '.__('app.telegram_suggest_area_bible_reading'),
            'mezmur' => '🎵 '.__('app.telegram_suggest_area_mezmur'),
            'synaxarium' => '🕊️ '.__('app.telegram_suggest_area_synaxarium'),
            'spiritual_book' => '📚 '.__('app.telegram_suggest_area_spiritual_book'),
            'reference_resource' => '🔗 '.__('app.telegram_suggest_area_reference_resource'),
            'daily_message' => '💬 '.__('app.telegram_suggest_area_daily_message'),
            default => ucfirst($contentArea),
        };
    }

    private function structuredSuggestResourceTypeLabel(string $resourceType): string
    {
        return match ($resourceType) {
            'video' => __('app.telegram_suggest_resource_type_video'),
            'website' => __('app.telegram_suggest_resource_type_website'),
            'file' => __('app.telegram_suggest_resource_type_file'),
            default => ucfirst($resourceType),
        };
    }

    private function structuredSuggestDateLabel(array $data): string
    {
        $month = (int) ($data['ethiopian_month'] ?? 0);
        $day = (int) ($data['ethiopian_day'] ?? 0);

        $monthLabel = match ($month) {
            1 => 'Meskerem',
            2 => 'Tikimt',
            3 => 'Hidar',
            4 => 'Tahsas',
            5 => 'Tir',
            6 => 'Yekatit',
            7 => 'Megabit',
            8 => 'Miyazia',
            9 => 'Ginbot',
            10 => 'Sene',
            11 => 'Hamle',
            12 => 'Nehase',
            13 => 'Pagumen',
            default => __('app.telegram_suggest_unknown_date'),
        };

        return $day > 0 ? $monthLabel.' '.$day : $monthLabel;
    }

    private function structuredSuggestLectionarySectionLabel(string $section): string
    {
        return match ($section) {
            'title_description' => __('app.telegram_suggest_lectionary_section_title_description'),
            'pauline' => __('app.telegram_suggest_lectionary_section_pauline'),
            'catholic' => __('app.telegram_suggest_lectionary_section_catholic'),
            'acts' => __('app.telegram_suggest_lectionary_section_acts'),
            'mesbak' => __('app.telegram_suggest_lectionary_section_mesbak'),
            'gospel' => __('app.telegram_suggest_lectionary_section_gospel'),
            'qiddase' => __('app.telegram_suggest_lectionary_section_qiddase'),
            default => ucfirst(str_replace('_', ' ', $section)),
        };
    }

    private function structuredSuggestLegacyType(string $contentArea): string
    {
        return match ($contentArea) {
            'lectionary', 'bible_reading' => 'bible',
            'mezmur' => 'mezmur',
            'synaxarium' => 'sinksar',
            'spiritual_book' => 'book',
            'reference_resource' => 'reference',
            'daily_message' => 'reference',
            default => 'reference',
        };
    }

    private function structuredSuggestStoredTitle(array $data): ?string
    {
        return match ((string) ($data['content_area'] ?? '')) {
            'lectionary' => __('app.telegram_suggest_area_lectionary').': '.$this->structuredSuggestLectionarySectionLabel((string) ($data['lectionary_section'] ?? '')),
            'bible_reading' => __('app.telegram_suggest_area_bible_reading'),
            default => $data['title'] ?? null,
        };
    }

    /**
     * Builds a lectionary reference from structured fields (book, chapter, verse range, geez line).
     */
    private function structuredSuggestBuildLectionaryReference(array $data): ?string
    {
        $section = (string) ($data['lectionary_section'] ?? '');
        $chapter = trim((string) ($data['lectionary_chapter'] ?? ''));
        $verseRange = trim((string) ($data['lectionary_verse_range'] ?? ''));

        if ($chapter === '' && $verseRange === '' && empty($data['reference'])) {
            return null;
        }

        $book = match ($section) {
            'pauline', 'catholic', 'gospel' => (string) ($data['lectionary_book_label'] ?? $data['lectionary_book'] ?? ''),
            'acts' => 'Acts',
            'mesbak' => 'Psalm',
            default => null,
        };

        if ($book !== null && $book !== '') {
            $ref = $book.' '.$chapter;
            if ($verseRange !== '') {
                $ref .= ':'.$verseRange;
            }
            if (! empty($data['lectionary_geez_line'])) {
                $lineKey = 'telegram_suggest_geez_line_'.((string) $data['lectionary_geez_line']);
                $ref .= ' ('.__('app.'.$lineKey).')';
            }

            return $ref;
        }

        return ! empty($data['reference']) ? (string) $data['reference'] : null;
    }

    private function structuredSuggestStoredReference(array $data): ?string
    {
        $parts = [$this->structuredSuggestDateLabel($data)];

        if (($data['content_area'] ?? null) === 'synaxarium' && ! empty($data['entry_scope'])) {
            $parts[] = $data['entry_scope'] === 'yearly'
                ? __('app.telegram_suggest_scope_yearly')
                : __('app.telegram_suggest_scope_monthly');
        }

        if (($data['content_area'] ?? null) === 'lectionary' && ! empty($data['lectionary_section'])) {
            $parts[] = $this->structuredSuggestLectionarySectionLabel((string) $data['lectionary_section']);
        }

        $ref = $this->structuredSuggestBuildLectionaryReference($data);
        if ($ref !== null) {
            $parts[] = $ref;
        } elseif (! empty($data['reference'])) {
            $parts[] = (string) $data['reference'];
        }

        if (($data['content_area'] ?? null) === 'reference_resource' && ! empty($data['resource_type'])) {
            $parts[] = $this->structuredSuggestResourceTypeLabel((string) $data['resource_type']);
        }

        $parts = array_values(array_filter($parts, fn ($value) => filled($value)));

        return $parts === [] ? null : implode(' • ', $parts);
    }

    private function structuredSuggestPayload(array $data): array
    {
        $payload = [
            'content_area' => $data['content_area'] ?? null,
            'ethiopian_month' => $data['ethiopian_month'] ?? null,
            'ethiopian_day' => $data['ethiopian_day'] ?? null,
            'entry_scope' => $data['entry_scope'] ?? null,
            'lectionary_section' => $data['lectionary_section'] ?? null,
            'lectionary_section_label' => ! empty($data['lectionary_section'])
                ? $this->structuredSuggestLectionarySectionLabel((string) $data['lectionary_section'])
                : null,
            'lectionary_book' => $data['lectionary_book'] ?? null,
            'lectionary_book_label' => $data['lectionary_book_label'] ?? null,
            'lectionary_chapter' => $data['lectionary_chapter'] ?? null,
            'lectionary_verse_range' => $data['lectionary_verse_range'] ?? null,
            'lectionary_geez_line' => $data['lectionary_geez_line'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_type_label' => ! empty($data['resource_type'])
                ? $this->structuredSuggestResourceTypeLabel((string) $data['resource_type'])
                : null,
            'reference' => $this->structuredSuggestBuildLectionaryReference($data) ?? $data['reference'] ?? null,
            'url' => $data['url'] ?? null,
            'is_main' => $data['is_main'] ?? null,
        ];

        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    // =========================================================================
    // Main menu keyboard
    // =========================================================================

    private function mainMenuKeyboard(Member|User $actor, TelegramAuthService $telegramAuthService): array
    {
        $rows = [];

        if ($actor instanceof Member) {
            $firstRow = [];
            if ($this->telegramBotBuilder->commandEnabled('home')) {
                $firstRow[] = ['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', __('app.nav_home')), 'callback_data' => 'home'];
            }
            $firstRow[] = ['text' => $this->telegramBotBuilder->buttonLabel('today', 'member', __('app.today')), 'callback_data' => 'today'];
            $firstRow[] = ['text' => __('app.progress'), 'callback_data' => 'progress'];
            $firstRow[] = ['text' => __('app.checklist'), 'callback_data' => 'checklist'];
            $rows[] = $firstRow;
            if ($this->telegramBotBuilder->buttonEnabled('help', 'member')) {
                $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('help', 'member', __('app.help')), 'callback_data' => 'help']];
            }
            $rows[] = [['text' => __('app.telegram_bot_unlink'), 'callback_data' => 'unlink']];
            $rows[] = [$this->languageToggleButton($actor)];

            return ['inline_keyboard' => $rows];
        }

        // Staff/admin dual-mode menu: Main Page (member view) | Portal (staff tools)
        $rows[] = [['text' => '📱 '.__('app.telegram_staff_main_page'), 'callback_data' => 'staff_main_page']];

        if ($actor->isAdmin()) {
            $rows[] = [['text' => '⚙️ '.__('app.telegram_staff_portal'), 'callback_data' => 'staff_portal']];
        }

        if ($this->telegramBotBuilder->buttonEnabled('help', 'admin')) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('help', 'admin', __('app.help')), 'callback_data' => 'help']];
        }

        $rows[] = [['text' => __('app.telegram_bot_unlink'), 'callback_data' => 'unlink']];
        $rows[] = [$this->languageToggleButton($actor)];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Staff "Main Page" sub-menu keyboard: Today, Home, and back to dual-mode menu.
     */
    private function staffMainPageKeyboard(): array
    {
        $rows = [];

        if ($this->telegramBotBuilder->commandEnabled('home')) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('home', 'member', __('app.nav_home')), 'callback_data' => 'home']];
        }

        $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('today', 'member', __('app.today')), 'callback_data' => 'today']];
        $rows[] = [['text' => '⬅️ '.__('app.telegram_staff_back_to_menu'), 'callback_data' => 'menu']];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Staff "Portal" sub-menu keyboard: Admin panel, Suggest, My Suggestions, back.
     */
    private function staffPortalKeyboard(User $actor, TelegramAuthService $telegramAuthService): array
    {
        $rows = [];

        if ($this->telegramBotBuilder->buttonEnabled('admin', 'admin')) {
            $adminLink = $this->adminSecureLink($actor, $telegramAuthService);
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('admin', 'admin', __('app.telegram_builder_admin_panel')), 'web_app' => ['url' => $adminLink]]];
        }

        $rows[] = [
            ['text' => '💡 '.__('app.telegram_suggest'), 'callback_data' => 'suggest'],
            ['text' => '📋 '.__('app.telegram_my_suggestions'), 'callback_data' => 'my_suggestions'],
        ];

        $rows[] = [['text' => '⬅️ '.__('app.telegram_staff_back_to_menu'), 'callback_data' => 'menu']];

        return ['inline_keyboard' => $rows];
    }

    private function handleStaffMainPage(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->staffActorFromChatId($chatId);
        if (! $actor instanceof User) {
            $linkedActor = $this->actorFromChatId($chatId);

            return $this->replyAfterDelete(
                $telegramService,
                $chatId,
                $messageId,
                $linkedActor instanceof Member ? __('app.telegram_staff_portal_access_denied') : $this->notLinkedMessage(),
                $linkedActor instanceof Member ? $this->mainMenuKeyboard($linkedActor, $telegramAuthService) : $this->startChoiceKeyboard()
            );
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '📱 '.__('app.telegram_staff_main_page'),
            $this->staffMainPageKeyboard(),
            $messageId
        );
    }

    private function handleStaffPortal(
        string $chatId,
        int $messageId,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        $actor = $this->staffActorFromChatId($chatId);
        if (! $actor instanceof User) {
            $linkedActor = $this->actorFromChatId($chatId);

            return $this->replyAfterDelete(
                $telegramService,
                $chatId,
                $messageId,
                $linkedActor instanceof Member ? __('app.telegram_staff_portal_access_denied') : $this->notLinkedMessage(),
                $linkedActor instanceof Member ? $this->mainMenuKeyboard($linkedActor, $telegramAuthService) : $this->startChoiceKeyboard()
            );
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '⚙️ '.__('app.telegram_staff_portal'),
            $this->staffPortalKeyboard($actor, $telegramAuthService),
            $messageId
        );
    }
}

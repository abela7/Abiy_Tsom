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
use Carbon\Carbon;
use Carbon\CarbonImmutable;
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

    /** Lectionary section order for all-in-one flow */
    private const LECTIONARY_SECTIONS = ['title_description', 'pauline', 'catholic', 'acts', 'mesbak', 'gospel', 'qiddase'];

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

        $userMessageId = (int) data_get($message, 'message_id', 0);
        $activeState = TelegramBotState::getAnyActive($chatId);
        $photos = data_get($message, 'photo', []);
        if (
            is_array($photos)
            && $photos !== []
            && $activeState?->action === 'suggest'
        ) {
            return $this->handleSuggestPhotoInput($chatId, $userMessageId, $photos, $activeState, $telegramService);
        }

        $text = trim((string) data_get($message, 'text', ''));
        if (! $text) {
            if ($activeState?->action === 'suggest' && $activeState->step === 'await_image') {
                return $this->reply(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_send_photo_or_skip'),
                    $this->structuredSuggestStepKeyboard('await_image', $activeState)
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
            default => $this->handlePlainText($chatId, $userMessageId, $text, $telegramAuthService, $telegramService),
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

        if (str_starts_with($action, 'suggest_') || str_starts_with($action, 'lect_') || $action === 'suggest' || $action === 'my_suggestions') {
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
        int $userMessageId,
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

                // Clean up user message and last bot message
                if ($action === 'suggest') {
                    $this->suggestDeleteStaleMessages($chatId, $userMessageId, $activeState, $telegramService);
                }

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
                return $this->handleSuggestTextInput($chatId, $userMessageId, $text, $activeState, $telegramAuthService, $telegramService);
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
                'lang_en' => 'en',
                'lang_am' => 'am',
                'lang_toggle' => $currentLocale === 'en' ? 'am' : 'en',
                default => null,
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
            'lang_en' => 'en',
            'lang_am' => 'am',
            'lang_toggle' => $currentLocale === 'en' ? 'am' : 'en',
            default => null,
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
        $label = $locale === 'en' ? __('app.telegram_lang_switch_am') : __('app.telegram_lang_switch_en');
        $callback = $targetLocale === 'en' ? 'lang_en' : 'lang_am';

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
        $user = User::query()->where('whatsapp_phone', $normalized)->first();

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
                'user_id' => $user->id,
                'phone' => $normalized,
                'role' => $user->role,
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
            'link_type' => $linkType,
            $idKey => $actorId,
            'code' => $code,
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
        $expectedCode = (string) $state->get('code', '');
        $codeExpiresAt = $state->get('code_expires_at');
        $linkType = (string) $state->get('link_type', 'member');

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
            $user = User::query()->find($userId);

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
        $member = Member::query()->find($memberId);

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
            $member = Member::query()->find($memberId);

            if (! $member instanceof Member) {
                $state->clear();

                return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
            }

            return $this->sendLinkCode($chatId, $member->whatsapp_phone, 'member', $member->id, $state, $telegramService);
        }

        // Admin / editor / writer
        $userId = (int) $state->get('user_id', 0);
        $user = User::query()->find($userId);

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
            'editor' => 'Editor',
            'writer' => 'Writer',
            default => ucfirst($role),
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
        $phone = $linkState ? (string) $linkState->get('phone', '') : '';

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
            default => response()->json(['success' => true]),
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

        $phone = (string) $state->get('phone', '');
        $name = (string) $state->get('name', 'Member');
        $locale = app()->getLocale();

        $member = Member::create([
            'baptism_name' => $name,
            'token' => Str::random(64),
            'whatsapp_phone' => $phone,
            'whatsapp_reminder_time' => $input,
            'whatsapp_reminder_enabled' => false,
            'whatsapp_confirmation_status' => 'pending',
            'whatsapp_confirmation_requested_at' => now(),
            'whatsapp_language' => $locale,
            'locale' => $locale,
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
        $keyboard = $this->mainMenuKeyboard($member, app(TelegramAuthService::class));

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
            $daily->memberDayUrl(),
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

    /**
     * Delete the user's input message and the bot's previous prompt message
     * during the suggestion wizard, keeping only the latest bot message visible.
     */
    private function suggestDeleteStaleMessages(
        string $chatId,
        int $userMessageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): void {
        // Delete the user's text/photo message
        if ($userMessageId > 0) {
            $telegramService->deleteMessage($chatId, $userMessageId);
        }

        // Delete the bot's previous prompt message
        $lastBotMsgId = (int) $state->get('last_bot_message_id', 0);
        if ($lastBotMsgId > 0) {
            $telegramService->deleteMessage($chatId, $lastBotMsgId);
        }
    }

    /**
     * Send a reply and track the bot's message ID in the wizard state
     * so it can be deleted on the next user input.
     */
    private function suggestReplyAndTrack(
        TelegramService $telegramService,
        TelegramBotState $state,
        string $chatId,
        string $text,
        array $replyMarkup = [],
        ?string $parseMode = null
    ): JsonResponse {
        // Delete previous bot message to prevent double messages (e.g. after callback-triggered advances)
        $prevBotMsgId = (int) $state->get('last_bot_message_id', 0);
        if ($prevBotMsgId > 0) {
            $telegramService->deleteMessage($chatId, $prevBotMsgId);
        }

        $options = [];
        if (! empty($replyMarkup)) {
            $options['reply_markup'] = $replyMarkup;
        }
        if ($parseMode !== null) {
            $options['parse_mode'] = $parseMode;
        }

        $sentId = $telegramService->sendAndGetMessageId($chatId, $text, $options);

        if ($sentId !== null) {
            $state->set('last_bot_message_id', $sentId);
        }

        return response()->json([
            'success' => $sentId !== null,
            'delivered' => $sentId !== null,
            'sent' => $sentId !== null,
        ]);
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

        // Track the bot message ID for cleanup when user types text next
        if ($messageId > 0) {
            $existingState = TelegramBotState::getActive($chatId, 'suggest');
            if ($existingState) {
                $existingState->set('last_bot_message_id', $messageId);
            }
        }

        // ── My Suggestions ────────────────────────────────────────────────
        if ($action === 'my_suggestions') {
            return $this->handleMySuggestions($chatId, $messageId, $actor, $telegramAuthService, $telegramService);
        }

        // ── Entry: start wizard ───────────────────────────────────────────
        if ($action === 'suggest') {
            return $this->startSuggestWizard($chatId, $messageId, $telegramService);
        }

        if (str_starts_with($action, 'suggest_area_')) {
            $area = str_replace('suggest_area_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $existingData = $state->data ?? [];

            // If continuing from a previous submission, skip date selection
            if (
                ! empty($existingData['skip_date_selection'])
                && (
                    ! empty($existingData['ethiopian_month'])
                    || ($area === 'synaxarium_celebration' && ! empty($existingData['ethiopian_day']))
                )
            ) {
                $hasExtraSteps = in_array($area, ['lectionary', 'synaxarium', 'synaxarium_celebration', 'reference_resource'], true);

                if ($hasExtraSteps) {
                    $nextStep = match ($area) {
                        'lectionary' => 'choose_lectionary_section',
                        'synaxarium' => 'choose_scope',
                        'synaxarium_celebration' => 'choose_scope',
                        'reference_resource' => 'choose_resource_type',
                    };

                    $state->advance($nextStep, [
                        'content_area' => $area,
                        'skip_date_selection' => null,
                    ]);

                    return $this->replyOrEdit(
                        $telegramService,
                        $chatId,
                        $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                        $this->structuredSuggestKeyboardForStep($nextStep, $state),
                        $messageId
                    );
                }

                // Direct to content entry with Amharic
                $state->advance('choose_first_language', [
                    'content_area' => $area,
                    'skip_date_selection' => null,
                ]);

                return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
            }

            if ($area === 'synaxarium_celebration') {
                $state->advance('choose_scope', ['content_area' => $area]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt('choose_scope', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('choose_scope', $state),
                    $messageId
                );
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

        if (str_starts_with($action, 'suggest_first_lang_')) {
            $lang = str_replace('suggest_first_lang_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $firstFieldStep = $this->structuredSuggestFirstFieldStep($state);
            $state->advance($firstFieldStep, [
                'first_language' => $lang,
                'current_language' => $lang,
                'lang_phase' => 1,
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt($firstFieldStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($firstFieldStep, $state),
                $messageId
            );
        }

        if ($action === 'suggest_other_lang_yes') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $otherLang = ((string) $state->get('first_language', 'en')) === 'en' ? 'am' : 'en';
            $firstFieldStep = $this->structuredSuggestFirstFieldStep($state);
            $state->advance($firstFieldStep, [
                'current_language' => $otherLang,
                'lang_phase' => 2,
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->structuredSuggestPrompt($firstFieldStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($firstFieldStep, $state),
                $messageId
            );
        }

        if ($action === 'suggest_other_lang_skip') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('preview');

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_add_more_images_yes' || $action === 'suggest_add_more_images_no') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $images = (array) $state->get('sinksar_images', []);
            if ($action === 'suggest_add_more_images_yes' && count($images) < 5) {
                $state->advance('await_image');

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt('await_image', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('await_image', $state),
                    $messageId
                );
            }

            $state->advance('preview');

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_review_summary') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('preview');

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_manage_images') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_manage_images_back_summary') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('preview');

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        // Edit menu from preview
        if ($action === 'suggest_edit') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->replyOrEdit(
                $telegramService, $chatId,
                '<b>✏️ '.__('app.telegram_suggest_edit_which').'</b>',
                $this->suggestEditFieldsKeyboard($state),
                $messageId, 'HTML'
            );
        }

        // Back to preview from edit menu
        if ($action === 'suggest_back_to_preview') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            $state->advance('preview', ['editing_from_preview' => null]);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        // Edit a specific field from preview (suggest_edit_{step}_{lang} or suggest_edit_lect_{section})
        if (str_starts_with($action, 'suggest_edit_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->handleSuggestEditField($chatId, $messageId, $action, $state, $telegramService);
        }

        if ($action === 'suggest_image_back_list') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
        }

        if (str_starts_with($action, 'suggest_manage_image_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $index = (int) str_replace('suggest_manage_image_', '', $action);

            return $this->showSuggestImageActions($chatId, $messageId, $state, $telegramService, $index);
        }

        if (str_starts_with($action, 'suggest_image_edit_am_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $index = (int) str_replace('suggest_image_edit_am_', '', $action);
            $state->advance('edit_image_caption_am', ['edit_image_index' => $index]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->suggestImageCaptionEditPrompt($state, $index, 'caption_am'),
                $this->structuredSuggestKeyboardForStep('edit_image_caption_am', $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_image_edit_en_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $index = (int) str_replace('suggest_image_edit_en_', '', $action);
            $state->advance('edit_image_caption_en', ['edit_image_index' => $index]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->suggestImageCaptionEditPrompt($state, $index, 'caption_en'),
                $this->structuredSuggestKeyboardForStep('edit_image_caption_en', $state),
                $messageId
            );
        }

        if (str_starts_with($action, 'suggest_image_remove_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $index = (int) str_replace('suggest_image_remove_', '', $action);
            $images = (array) $state->get('sinksar_images', []);
            if (isset($images[$index]) && is_array($images[$index])) {
                $path = trim((string) ($images[$index]['path'] ?? ''));
                if ($path !== '' && str_starts_with($path, 'telegram-suggestions/')) {
                    Storage::disk('public')->delete($path);
                }
                array_splice($images, $index, 1);
                $this->syncSuggestSinksarImagesState($state, $images);
            }

            return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
        }

        if (str_starts_with($action, 'suggest_image_up_') || str_starts_with($action, 'suggest_image_down_')) {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $moveUp = str_starts_with($action, 'suggest_image_up_');
            $index = (int) str_replace($moveUp ? 'suggest_image_up_' : 'suggest_image_down_', '', $action);
            $images = array_values((array) $state->get('sinksar_images', []));
            $swapIndex = $moveUp ? $index - 1 : $index + 1;

            if (isset($images[$index], $images[$swapIndex])) {
                [$images[$index], $images[$swapIndex]] = [$images[$swapIndex], $images[$index]];
                $this->syncSuggestSinksarImagesState($state, $images);
            }

            return $this->showSuggestImageActions($chatId, $messageId, $state, $telegramService, max(0, min($swapIndex, max(0, count($images) - 1))));
        }

        if ($action === 'suggest_remove_image') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $path = trim((string) $state->get('image_path', ''));
            if ($path !== '' && str_starts_with($path, 'telegram-suggestions/')) {
                Storage::disk('public')->delete($path);
            }

            $state->advance('preview', [
                'image_path' => null,
                'editing_from_preview' => null,
            ]);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
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

            if ((string) $state->get('content_area', '') === 'synaxarium_celebration') {
                $state->advance('choose_day', ['ethiopian_month' => $month]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_choose_day'),
                    $this->structuredSuggestKeyboardForStep('choose_day', $state),
                    $messageId
                );
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

            if ((string) $state->get('content_area', '') === 'synaxarium_celebration') {
                $mergeData = ['ethiopian_day' => $day];
                if ((string) $state->get('entry_scope', '') === 'monthly') {
                    $mergeData['ethiopian_month'] = null;
                }

                if ($state->get('editing_from_preview')) {
                    $state->advance('preview', array_merge($mergeData, ['editing_from_preview' => null]));

                    return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                }

                $state->advance('choose_first_language', $mergeData);

                return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
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

            $contentArea = (string) $state->get('content_area', '');

            // Areas with extra steps before content entry
            if ($contentArea === 'lectionary') {
                // All-in-one lectionary: start with first section intro
                $state->advance('lect_section_intro', [
                    'lect_current_section' => self::LECTIONARY_SECTIONS[0],
                    'lect_sections' => [],
                    'lect_filled_order' => [],
                ]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt('lect_section_intro', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('lect_section_intro', $state),
                    $messageId,
                    'HTML'
                );
            }

            if (in_array($contentArea, ['synaxarium', 'reference_resource'], true)) {
                $nextStep = match ($contentArea) {
                    'synaxarium' => 'choose_scope',
                    'reference_resource' => 'choose_resource_type',
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

            // All other areas: auto-start with Amharic
            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
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

            if ((string) $state->get('content_area', '') === 'synaxarium_celebration') {
                $mergeData = [
                    'entry_scope' => $scope,
                    'ethiopian_month' => $scope === 'monthly' ? null : $state->get('ethiopian_month'),
                ];

                if ($scope === 'monthly') {
                    $state->advance('choose_day', $mergeData);

                    return $this->replyOrEdit(
                        $telegramService,
                        $chatId,
                        __('app.telegram_suggest_choose_day'),
                        $this->structuredSuggestKeyboardForStep('choose_day', $state),
                        $messageId
                    );
                }

                $state->advance('choose_month', $mergeData);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    __('app.telegram_suggest_choose_month'),
                    $this->structuredSuggestMonthKeyboard(),
                    $messageId
                );
            }

            $state->advance('choose_first_language', ['entry_scope' => $scope]);

            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
        }

        if (str_starts_with($action, 'suggest_lectionary_section_')) {
            $section = str_replace('suggest_lectionary_section_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $hasRefSteps = in_array($section, ['pauline', 'catholic', 'gospel', 'acts', 'mesbak'], true);

            if ($hasRefSteps) {
                // Sections with ref steps: go to ref step first, language set later via flow
                $nextStep = match ($section) {
                    'pauline', 'catholic', 'gospel' => 'choose_book',
                    'acts', 'mesbak' => 'enter_chapter',
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

            // title_description / qiddase: go directly to bilingual content with Amharic
            $state->advance('choose_first_language', ['lectionary_section' => $section]);

            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
        }

        // All-in-one lectionary: Fill current section
        if ($action === 'lect_fill') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $section = (string) $state->get('lect_current_section', '');
            $hasRefSteps = in_array($section, ['pauline', 'catholic', 'gospel', 'acts', 'mesbak'], true);

            if ($hasRefSteps) {
                $nextStep = match ($section) {
                    'pauline', 'catholic', 'gospel' => 'choose_book',
                    default => 'enter_chapter',
                };
                $state->advance($nextStep, [
                    'lectionary_section' => $section,
                    'first_language' => 'am',
                    'current_language' => 'am',
                    'lang_phase' => 1,
                ]);

                return $this->replyOrEdit(
                    $telegramService, $chatId,
                    $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep($nextStep, $state),
                    $messageId
                );
            }

            // title_description / qiddase: start bilingual with Amharic
            $state->advance('choose_first_language', ['lectionary_section' => $section]);

            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
        }

        // All-in-one lectionary: Skip current section
        if ($action === 'lect_skip') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->lectAdvanceToNextSection($chatId, $messageId, $state, $telegramService);
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

        if (str_starts_with($action, 'suggest_resource_type_')) {
            $resourceType = str_replace('suggest_resource_type_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $state->advance('choose_first_language', ['resource_type' => $resourceType]);

            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
        }

        if ($action === 'suggest_main_yes' || $action === 'suggest_main_no') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            if ((string) $state->get('content_area', '') === 'synaxarium_celebration') {
                $nextStep = $state->get('editing_from_preview') ? 'preview' : 'enter_sort_order';
                $mergeData = ['is_main' => $action === 'suggest_main_yes'];
                if ($state->get('editing_from_preview')) {
                    $mergeData['editing_from_preview'] = null;
                }

                $state->advance($nextStep, $mergeData);

                if ($nextStep === 'preview') {
                    return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                }

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep($nextStep, $state),
                    $messageId
                );
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

        // Continue flow after submission — pick another lectionary section
        if (str_starts_with($action, 'suggest_continue_lect_')) {
            $section = str_replace('suggest_continue_lect_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            $preserved = $state->data ?? [];
            $filledSections = (array) ($preserved['filled_lectionary_sections'] ?? []);
            $hasRefSteps = in_array($section, ['pauline', 'catholic', 'gospel', 'acts', 'mesbak'], true);

            if ($hasRefSteps) {
                $firstStep = match ($section) {
                    'pauline', 'catholic', 'gospel' => 'choose_book',
                    default => 'enter_chapter',
                };

                $state->update([
                    'step' => $firstStep,
                    'data' => [
                        'content_area' => 'lectionary',
                        'lectionary_section' => $section,
                        'ethiopian_month' => $preserved['ethiopian_month'] ?? null,
                        'ethiopian_day' => $preserved['ethiopian_day'] ?? null,
                        'filled_lectionary_sections' => $filledSections,
                    ],
                ]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt($firstStep, $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep($firstStep, $state),
                    $messageId
                );
            }

            // title_description / qiddase: auto-start with Amharic
            $state->update([
                'step' => 'choose_first_language',
                'data' => [
                    'content_area' => 'lectionary',
                    'lectionary_section' => $section,
                    'ethiopian_month' => $preserved['ethiopian_month'] ?? null,
                    'ethiopian_day' => $preserved['ethiopian_day'] ?? null,
                    'filled_lectionary_sections' => $filledSections,
                ],
            ]);

            return $this->suggestAutoStartAmharic($chatId, $messageId, $state, $telegramService);
        }

        // Continue flow — pick a different content area for same date
        if ($action === 'suggest_continue_area') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            $preserved = $state->data ?? [];

            $state->update([
                'step' => 'choose_area',
                'data' => [
                    'ethiopian_month' => $preserved['ethiopian_month'] ?? null,
                    'ethiopian_day' => $preserved['ethiopian_day'] ?? null,
                    'skip_date_selection' => true,
                ],
            ]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                '💡 '.__('app.telegram_suggest_choose_area'),
                $this->structuredSuggestAreaKeyboard(),
                $messageId
            );
        }

        // Continue flow — done, go back to main menu
        if ($action === 'suggest_continue_done') {
            TelegramBotState::query()->where('chat_id', $chatId)->where('action', 'suggest')->delete();

            $keyboard = $this->mainMenuKeyboard($actor, $telegramAuthService);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_continue_finished'),
                $keyboard,
                $messageId
            );
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
        TelegramBotState::startFor($chatId, 'suggest', 'choose_area');

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '💡 '.__('app.telegram_suggest')."\n\n".__('app.telegram_suggest_choose_area'),
            $this->structuredSuggestAreaKeyboard(),
            $messageId
        );
    }

    private function suggestFirstLanguageKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🇪🇹 አማርኛ', 'callback_data' => 'suggest_first_lang_am'],
                ['text' => '🇬🇧 English', 'callback_data' => 'suggest_first_lang_en'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function suggestOfferOtherLanguageKeyboard(string $otherLang): array
    {
        $langLabel = $otherLang === 'am' ? '🇪🇹 አማርኛ' : '🇬🇧 English';

        return ['inline_keyboard' => [
            [['text' => '✅ '.__('app.telegram_suggest_add_other_lang', ['lang' => $langLabel]), 'callback_data' => 'suggest_other_lang_yes']],
            [['text' => '⏭ '.__('app.telegram_suggest_skip_other_lang'), 'callback_data' => 'suggest_other_lang_skip']],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function suggestAddMoreImagesKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '✅ '.__('app.telegram_suggest_add_another_image_yes'), 'callback_data' => 'suggest_add_more_images_yes'],
                ['text' => '📋 '.__('app.telegram_suggest_review_now'), 'callback_data' => 'suggest_review_summary'],
            ],
            [
                ['text' => '➡️ '.__('app.telegram_suggest_add_another_image_no'), 'callback_data' => 'suggest_add_more_images_no'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function suggestPreviewKeyboard(TelegramBotState $state): array
    {
        $rows = [
            [['text' => '✅ '.__('app.telegram_suggest_confirm'), 'callback_data' => 'suggest_confirm']],
        ];

        if ((string) $state->get('content_area', '') === 'synaxarium') {
            $rows[] = [['text' => '🖼️ '.__('app.telegram_suggest_manage_images'), 'callback_data' => 'suggest_manage_images']];
        }

        $rows[] = [
            ['text' => '✏️ '.__('app.telegram_suggest_edit'), 'callback_data' => 'suggest_edit'],
            ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
        ];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Build inline keyboard for selecting which field to edit from preview.
     */
    private function suggestEditFieldsKeyboard(TelegramBotState $state): array
    {
        $data = $state->data ?? [];
        $contentArea = (string) ($data['content_area'] ?? '');
        $rows = [];

        if ($contentArea === 'lectionary' && ! empty($data['lect_sections'])) {
            // For lectionary, show each filled section as an edit target
            $sections = (array) $data['lect_sections'];
            $filledOrder = (array) ($data['lect_filled_order'] ?? array_keys($sections));
            foreach ($filledOrder as $section) {
                if (empty($sections[$section])) {
                    continue;
                }
                $label = $this->structuredSuggestLectionarySectionLabel((string) $section);
                $rows[] = [['text' => "📖 {$label}", 'callback_data' => "suggest_edit_lect_{$section}"]];
            }
        } else {
            if ($contentArea === 'synaxarium_celebration') {
                $rows[] = [[
                    'text' => '🔁 '.__('app.telegram_suggest_edit_scope'),
                    'callback_data' => 'suggest_edit_scope',
                ]];
                $rows[] = [[
                    'text' => '📅 '.__('app.telegram_suggest_edit_date'),
                    'callback_data' => 'suggest_edit_date',
                ]];
            }

            // For other content areas, show editable bilingual fields
            $lectionarySection = (string) ($data['lectionary_section'] ?? '');
            $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);

            $stepLabels = [
                'enter_title' => __('app.telegram_suggest_edit_title'),
                'enter_url' => __('app.telegram_suggest_edit_url'),
                'enter_text' => __('app.telegram_suggest_edit_text'),
                'enter_detail' => __('app.telegram_suggest_edit_detail'),
                'enter_reference' => __('app.telegram_suggest_edit_reference'),
                'enter_summary' => __('app.telegram_suggest_edit_summary'),
            ];

            foreach ($bilingualSteps as $step) {
                $label = $stepLabels[$step] ?? ucfirst(str_replace('enter_', '', $step));
                // Show both languages in a row
                $row = [];
                $row[] = ['text' => "🇪🇹 {$label}", 'callback_data' => "suggest_edit_{$step}_am"];
                $row[] = ['text' => "🇬🇧 {$label}", 'callback_data' => "suggest_edit_{$step}_en"];
                $rows[] = $row;
            }

            if ($contentArea === 'synaxarium_celebration') {
                $rows[] = [[
                    'text' => __('app.telegram_suggest_edit_image'),
                    'callback_data' => 'suggest_edit_image',
                ]];
                $rows[] = [[
                    'text' => __('app.telegram_suggest_edit_main'),
                    'callback_data' => 'suggest_edit_main',
                ]];
                $rows[] = [[
                    'text' => __('app.telegram_suggest_edit_sort_order'),
                    'callback_data' => 'suggest_edit_sort_order',
                ]];
            }
        }

        $rows[] = [['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back_to_preview']];

        return ['inline_keyboard' => $rows];
    }

    private function suggestImageManagerKeyboard(array $images): array
    {
        $rows = [];

        foreach ($images as $index => $_image) {
            $rows[] = [[
                'text' => __('app.telegram_suggest_image_item', ['n' => $index + 1]),
                'callback_data' => 'suggest_manage_image_'.$index,
            ]];
        }

        if (count($images) < 5) {
            $rows[] = [[
                'text' => '➕ '.__('app.telegram_suggest_add_another_image_yes'),
                'callback_data' => 'suggest_add_more_images_yes',
            ]];
        }

        $rows[] = [[
            'text' => '📋 '.__('app.telegram_suggest_review_now'),
            'callback_data' => 'suggest_review_summary',
        ]];
        $rows[] = [[
            'text' => '⬅️ '.__('app.telegram_suggest_back_to_summary'),
            'callback_data' => 'suggest_manage_images_back_summary',
        ]];
        $rows[] = [[
            'text' => '❌ '.__('app.telegram_suggest_cancel'),
            'callback_data' => 'suggest_cancel',
        ]];

        return ['inline_keyboard' => $rows];
    }

    private function suggestImageActionsKeyboard(int $index, int $count): array
    {
        $rows = [
            [[
                'text' => '🇪🇹 '.__('app.telegram_suggest_edit_caption_am'),
                'callback_data' => 'suggest_image_edit_am_'.$index,
            ]],
            [[
                'text' => '🇬🇧 '.__('app.telegram_suggest_edit_caption_en'),
                'callback_data' => 'suggest_image_edit_en_'.$index,
            ]],
        ];

        if ($index > 0) {
            $rows[] = [[
                'text' => '⬆️ '.__('app.telegram_suggest_move_up'),
                'callback_data' => 'suggest_image_up_'.$index,
            ]];
        }

        if ($index < ($count - 1)) {
            $rows[] = [[
                'text' => '⬇️ '.__('app.telegram_suggest_move_down'),
                'callback_data' => 'suggest_image_down_'.$index,
            ]];
        }

        $rows[] = [[
            'text' => '🗑️ '.__('app.telegram_suggest_remove_image'),
            'callback_data' => 'suggest_image_remove_'.$index,
        ]];
        $rows[] = [[
            'text' => '⬅️ '.__('app.telegram_suggest_back_to_images'),
            'callback_data' => 'suggest_image_back_list',
        ]];
        $rows[] = [[
            'text' => '❌ '.__('app.telegram_suggest_cancel'),
            'callback_data' => 'suggest_cancel',
        ]];

        return ['inline_keyboard' => $rows];
    }

    /**
     * Auto-set Amharic as first language, advance to first field step, and render its prompt.
     */
    private function suggestAutoStartAmharic(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService,
        array $extraData = []
    ): JsonResponse {
        $firstFieldStep = $this->structuredSuggestFirstFieldStep($state);
        $state->advance($firstFieldStep, array_merge($extraData, [
            'first_language' => 'am',
            'current_language' => 'am',
            'lang_phase' => 1,
        ]));

        $prompt = $this->structuredSuggestPrompt($firstFieldStep, $state->data ?? []);
        $keyboard = $this->structuredSuggestKeyboardForStep($firstFieldStep, $state);

        if ($messageId === 0) {
            return $this->suggestReplyAndTrack($telegramService, $state, $chatId, $prompt, $keyboard);
        }

        return $this->replyOrEdit($telegramService, $chatId, $prompt, $keyboard, $messageId);
    }

    private function suggestStartSynaxariumEnglishPhase(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService,
        array $extraData = []
    ): JsonResponse {
        $firstFieldStep = $this->structuredSuggestFirstFieldStep($state);
        $state->advance($firstFieldStep, array_merge($extraData, [
            'current_language' => 'en',
            'lang_phase' => 2,
        ]));

        $prompt = $this->structuredSuggestPrompt($firstFieldStep, $state->data ?? []);
        $keyboard = $this->structuredSuggestKeyboardForStep($firstFieldStep, $state);

        if ($messageId === 0) {
            return $this->suggestReplyAndTrack($telegramService, $state, $chatId, $prompt, $keyboard);
        }

        return $this->replyOrEdit($telegramService, $chatId, $prompt, $keyboard, $messageId);
    }

    /**
     * Handle lect_section_done: if Amharic phase done, switch to English for same section;
     * if English phase done, save section and advance to next.
     */
    private function lectHandleSectionDone(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $langPhase = (int) $state->get('lang_phase', 1);

        if ($langPhase === 1) {
            // Amharic done — switch to English for the same section
            $lectionarySection = (string) $state->get('lectionary_section', '');
            $bilingualSteps = $this->structuredSuggestBilingualFieldSteps('lectionary', $lectionarySection);
            $firstStep = $bilingualSteps[0] ?? 'enter_detail';

            $state->advance($firstStep, [
                'current_language' => 'en',
                'lang_phase' => 2,
            ]);

            $sectionLabel = $this->structuredSuggestLectionarySectionLabel($lectionarySection);
            $header = "📖 <b>{$sectionLabel}</b> [🇬🇧 English]\n\n";

            return $this->suggestReplyAndTrack(
                $telegramService, $state, $chatId,
                $header.$this->structuredSuggestPrompt($firstStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($firstStep, $state),
                'HTML'
            );
        }

        // English done — save both languages and advance to next section
        return $this->lectSaveSectionAndAdvance($chatId, $messageId, $state, $telegramService);
    }

    /**
     * Save current lectionary section's data and advance to the next section intro or preview.
     */
    private function lectSaveSectionAndAdvance(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $data = $state->data ?? [];
        $section = (string) ($data['lect_current_section'] ?? '');

        // Extract section data from flat fields into lect_sections
        $sectionData = [];
        $refKeys = ['lectionary_chapter', 'lectionary_verse_range', 'lectionary_book', 'lectionary_book_label', 'mesbak_geez_1', 'mesbak_geez_2', 'mesbak_geez_3'];
        foreach ($refKeys as $key) {
            if (! empty($data[$key])) {
                $sectionData[$key] = $data[$key];
            }
        }
        $bilingualKeys = ['title_am', 'title_en', 'content_detail_am', 'content_detail_en'];
        foreach ($bilingualKeys as $key) {
            if (! empty($data[$key])) {
                $sectionData[$key] = $data[$key];
            }
        }

        $sections = $data['lect_sections'] ?? [];
        $sections[$section] = $sectionData;

        $filledOrder = $data['lect_filled_order'] ?? [];
        if (! in_array($section, $filledOrder, true)) {
            $filledOrder[] = $section;
        }

        // Clear flat fields and update sections in data
        foreach (array_merge($refKeys, $bilingualKeys) as $key) {
            unset($data[$key]);
        }
        unset($data['lectionary_section']);
        $data['lect_sections'] = $sections;
        $data['lect_filled_order'] = $filledOrder;

        // If editing a specific section from preview, save and return to preview
        if (! empty($data['editing_from_preview'])) {
            $data['editing_from_preview'] = null;
            $data['editing_lect_section'] = null;
            $state->step = 'preview';
            $state->data = $data;
            $state->expires_at = now()->addHour();
            $state->save();

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        // Compute next section
        $idx = array_search($section, self::LECTIONARY_SECTIONS, true);
        $nextSection = ($idx !== false && isset(self::LECTIONARY_SECTIONS[$idx + 1]))
            ? self::LECTIONARY_SECTIONS[$idx + 1]
            : null;

        if ($nextSection !== null) {
            $data['lect_current_section'] = $nextSection;
            $state->step = 'lect_section_intro';
            $state->data = $data;
            $state->expires_at = now()->addHour();
            $state->save();

            $prompt = $this->structuredSuggestPrompt('lect_section_intro', $data);
            $keyboard = $this->structuredSuggestKeyboardForStep('lect_section_intro', $state);

            if ($messageId === 0) {
                return $this->suggestReplyAndTrack($telegramService, $state, $chatId, $prompt, $keyboard, 'HTML');
            }

            return $this->replyOrEdit($telegramService, $chatId, $prompt, $keyboard, $messageId, 'HTML');
        }

        return $this->lectFinishAllSections($chatId, $messageId, $state, $telegramService, $data);
    }

    /**
     * Advance to the next lectionary section intro, or to preview if all done.
     * Called from lect_skip callback (no section data to save).
     */
    private function lectAdvanceToNextSection(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $data = $state->data ?? [];
        $current = (string) ($data['lect_current_section'] ?? '');
        $idx = array_search($current, self::LECTIONARY_SECTIONS, true);
        $nextSection = ($idx !== false && isset(self::LECTIONARY_SECTIONS[$idx + 1]))
            ? self::LECTIONARY_SECTIONS[$idx + 1]
            : null;

        if ($nextSection !== null) {
            $state->advance('lect_section_intro', ['lect_current_section' => $nextSection]);

            $prompt = $this->structuredSuggestPrompt('lect_section_intro', $state->data ?? []);
            $keyboard = $this->structuredSuggestKeyboardForStep('lect_section_intro', $state);

            if ($messageId === 0) {
                return $this->suggestReplyAndTrack($telegramService, $state, $chatId, $prompt, $keyboard, 'HTML');
            }

            return $this->replyOrEdit($telegramService, $chatId, $prompt, $keyboard, $messageId, 'HTML');
        }

        return $this->lectFinishAllSections($chatId, $messageId, $state, $telegramService, $data);
    }

    /**
     * All sections processed — go to preview.
     */
    private function lectFinishAllSections(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService,
        array $data
    ): JsonResponse {
        $state->advance('preview');

        return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
    }

    private function suggestContinueKeyboard(string $contentArea, array $data): array
    {
        $rows = [];

        if ($contentArea === 'lectionary') {
            // Show remaining lectionary sections
            $allSections = ['title_description', 'pauline', 'catholic', 'acts', 'mesbak', 'gospel', 'qiddase'];
            $filled = array_merge(
                (array) ($data['filled_lectionary_sections'] ?? []),
                ! empty($data['lectionary_section']) ? [$data['lectionary_section']] : []
            );
            $remaining = array_diff($allSections, $filled);

            foreach ($remaining as $section) {
                $label = $this->structuredSuggestLectionarySectionLabel($section);
                $rows[] = [['text' => $label, 'callback_data' => 'suggest_continue_lect_'.$section]];
            }
        }

        // Always offer to suggest a different content area for same date
        $rows[] = [['text' => '📝 '.__('app.telegram_suggest_continue_other_area'), 'callback_data' => 'suggest_continue_area']];
        $rows[] = [['text' => '✅ '.__('app.telegram_suggest_continue_done'), 'callback_data' => 'suggest_continue_done']];

        return ['inline_keyboard' => $rows];
    }

    private function showSuggestImageManager(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $images = array_values((array) $state->get('sinksar_images', []));
        $this->syncSuggestSinksarImagesState($state, $images);

        $state->advance('manage_images');

        $lines = ['<b>🖼️ '.__('app.telegram_suggest_manage_images').'</b>', ''];
        if ($images === []) {
            $lines[] = __('app.telegram_suggest_no_images_yet');
        } else {
            foreach ($images as $index => $image) {
                if (! is_array($image)) {
                    continue;
                }

                $captionAm = trim((string) ($image['caption_am'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');
                $captionEn = trim((string) ($image['caption_en'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');
                $lines[] = '<b>'.__('app.telegram_suggest_image_item', ['n' => $index + 1]).'</b>';
                $lines[] = 'AM: '.htmlspecialchars($captionAm, ENT_QUOTES, 'UTF-8');
                $lines[] = 'EN: '.htmlspecialchars($captionEn, ENT_QUOTES, 'UTF-8');
                $lines[] = '';
            }
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            implode("\n", $lines),
            $this->suggestImageManagerKeyboard($images),
            $messageId,
            'HTML'
        );
    }

    private function showSuggestImageActions(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService,
        int $index
    ): JsonResponse {
        $images = array_values((array) $state->get('sinksar_images', []));
        if (! isset($images[$index]) || ! is_array($images[$index])) {
            return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
        }

        $this->syncSuggestSinksarImagesState($state, $images);
        $state->advance('manage_image', ['edit_image_index' => $index]);

        $image = $images[$index];
        $captionAm = trim((string) ($image['caption_am'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');
        $captionEn = trim((string) ($image['caption_en'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');

        $lines = [
            '<b>'.__('app.telegram_suggest_image_item', ['n' => $index + 1]).'</b>',
            '',
            'AM: '.htmlspecialchars($captionAm, ENT_QUOTES, 'UTF-8'),
            'EN: '.htmlspecialchars($captionEn, ENT_QUOTES, 'UTF-8'),
        ];

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            implode("\n", $lines),
            $this->suggestImageActionsKeyboard($index, count($images)),
            $messageId,
            'HTML'
        );
    }

    private function syncSuggestSinksarImagesState(TelegramBotState $state, array $images): void
    {
        $images = array_values(array_filter($images, fn ($image) => is_array($image) && trim((string) ($image['path'] ?? '')) !== ''));
        $firstPath = null;
        foreach ($images as $image) {
            $path = trim((string) ($image['path'] ?? ''));
            if ($path !== '') {
                $firstPath = $path;
                break;
            }
        }

        $state->data = array_merge($state->data ?? [], [
            'sinksar_images' => $images,
            'image_path' => $firstPath,
        ]);
        $state->expires_at = now()->addHour();
        $state->save();
    }

    private function suggestImageCaptionEditPrompt(TelegramBotState $state, int $index, string $field): string
    {
        $prompt = $this->structuredSuggestPrompt(
            $field === 'caption_am' ? 'edit_image_caption_am' : 'edit_image_caption_en',
            $state->data ?? []
        );

        $images = array_values((array) $state->get('sinksar_images', []));
        $existing = isset($images[$index]) && is_array($images[$index])
            ? trim((string) ($images[$index][$field] ?? ''))
            : '';

        if ($existing !== '') {
            $prompt .= "\n\n<i>".__('app.telegram_suggest_current').' '.htmlspecialchars($existing, ENT_QUOTES, 'UTF-8').'</i>';
            $prompt .= "\n".__('app.telegram_suggest_type_to_replace');
        }

        return $prompt;
    }

    /**
     * Handles plain-text input during an active suggestion wizard step.
     */
    private function handleSuggestTextInput(
        string $chatId,
        int $userMessageId,
        string $text,
        TelegramBotState $state,
        TelegramAuthService $telegramAuthService,
        TelegramService $telegramService
    ): JsonResponse {
        // Delete user's text message and bot's previous prompt to keep chat clean
        $this->suggestDeleteStaleMessages($chatId, $userMessageId, $state, $telegramService);

        // Ignore text input during button-only steps — re-show the step prompt
        if (in_array($state->step, [
            'choose_area',
            'choose_month',
            'choose_day',
            'confirm_date',
            'choose_scope',
            'choose_first_language',
            'choose_lectionary_section',
            'choose_book',
            'choose_resource_type',
            'choose_main',
            'offer_other_language',
            'awaiting_continue',
            'lect_section_intro',
            'ask_more_images',
            'manage_images',
            'manage_image',
        ], true)) {
            $useHtml = $state->step === 'lect_section_intro' ? 'HTML' : null;

            return $this->suggestReplyAndTrack(
                $telegramService, $state, $chatId,
                $this->structuredSuggestPrompt($state->step, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($state->step, $state),
                $useHtml
            );
        }

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
        $contentArea = (string) $state->get('content_area', '');
        $langPhase = (int) $state->get('lang_phase', 1);

        if (in_array($currentStep, ['edit_image_caption_am', 'edit_image_caption_en'], true)) {
            $images = array_values((array) $state->get('sinksar_images', []));
            $index = (int) $state->get('edit_image_index', -1);
            if (! isset($images[$index]) || ! is_array($images[$index])) {
                return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
            }

            $field = $currentStep === 'edit_image_caption_am' ? 'caption_am' : 'caption_en';
            $images[$index][$field] = $input !== '' ? $input : null;
            $this->syncSuggestSinksarImagesState($state, $images);

            return $this->showSuggestImageActions($chatId, $messageId, $state, $telegramService, $index);
        }

        $lang = (string) $state->get('current_language', 'en');
        $bilingualSteps = ['enter_reference', 'enter_summary', 'enter_text', 'enter_title', 'enter_url', 'enter_detail', 'enter_lyrics'];
        $isBilingual = in_array($currentStep, $bilingualSteps, true);

        // Non-bilingual fields stay as-is; bilingual fields get _en/_am suffix
        $fieldForStep = [
            'enter_chapter' => 'lectionary_chapter',
            'enter_verse_range' => 'lectionary_verse_range',
            'enter_geez_1' => 'mesbak_geez_1',
            'enter_geez_2' => 'mesbak_geez_2',
            'enter_geez_3' => 'mesbak_geez_3',
            'enter_image_caption_am' => 'pending_image_caption_am',
            'enter_image_caption_en' => 'pending_image_caption_en',
            'enter_sort_order' => 'sort_order',
        ];
        if ($isBilingual) {
            $fieldForStep[$currentStep] = match ($currentStep) {
                'enter_reference' => "reference_{$lang}",
                'enter_summary' => "summary_{$lang}",
                'enter_text' => "text_{$lang}",
                'enter_title' => "title_{$lang}",
                'enter_url' => "url_{$lang}",
                'enter_detail' => "content_detail_{$lang}",
                'enter_lyrics' => "lyrics_{$lang}",
                default => $currentStep,
            };
        }

        $mergeData = [];
        if (isset($fieldForStep[$currentStep])) {
            if ($currentStep === 'enter_chapter' && $input !== '' && ! ctype_digit(trim($input))) {
                return $this->suggestReplyAndTrack(
                    $telegramService, $state, $chatId,
                    __('app.telegram_suggest_invalid_chapter'),
                    $this->structuredSuggestStepKeyboard($currentStep, $state)
                );
            }
            if ($currentStep === 'enter_verse_range' && $input !== '' && ! $this->suggestStepInputLooksVerseRange($input)) {
                return $this->suggestReplyAndTrack(
                    $telegramService, $state, $chatId,
                    __('app.telegram_suggest_invalid_verse_range'),
                    $this->structuredSuggestStepKeyboard($currentStep, $state)
                );
            }
            if ($currentStep === 'enter_sort_order' && $input !== '') {
                if (! ctype_digit($input) || (int) $input > 255) {
                    return $this->suggestReplyAndTrack(
                        $telegramService,
                        $state,
                        $chatId,
                        __('app.telegram_suggest_invalid_sort_order'),
                        $this->structuredSuggestStepKeyboard($currentStep, $state)
                    );
                }

                $input = (string) (int) $input;
            }
            if ($input === '') {
                if ($currentStep === 'enter_sort_order') {
                    $mergeData['sort_order'] = 0;
                }

                // Skip during edit-from-preview: clear the field and return to preview
                if ($state->get('editing_from_preview') && $this->structuredSuggestStepIsOptional($state, $currentStep)) {
                    $clearData = ['editing_from_preview' => null];
                    if (isset($fieldForStep[$currentStep])) {
                        $clearData[$fieldForStep[$currentStep]] = $currentStep === 'enter_sort_order' ? 0 : null;
                    }
                    $state->advance('preview', $clearData);

                    return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                }

                if ($this->structuredSuggestStepIsOptional($state, $currentStep)) {
                    $nextStep = $this->structuredSuggestNextStep($state, $currentStep);

                    if (in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true) && $langPhase === 1 && $nextStep === 'offer_other_language') {
                        return $this->suggestStartSynaxariumEnglishPhase($chatId, $messageId, $state, $telegramService);
                    }

                    if ($nextStep === 'lect_section_done') {
                        return $this->lectHandleSectionDone($chatId, $messageId, $state, $telegramService);
                    }

                    if ($currentStep === 'enter_image_caption_en') {
                        $images = (array) $state->get('sinksar_images', []);
                        $pendingPath = trim((string) $state->get('pending_image_path', ''));
                        if ($pendingPath !== '') {
                            $images[] = [
                                'path' => $pendingPath,
                                'caption_am' => trim((string) $state->get('pending_image_caption_am', '')) ?: null,
                                'caption_en' => null,
                            ];
                        }

                        $state->advance('ask_more_images', [
                            'sinksar_images' => $images,
                            'pending_image_path' => null,
                            'pending_image_caption_am' => null,
                            'pending_image_caption_en' => null,
                        ]);

                        if (count($images) >= 5) {
                            $state->advance('preview');

                            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                        }

                        return $this->suggestReplyAndTrack(
                            $telegramService,
                            $state,
                            $chatId,
                            $this->structuredSuggestPrompt('ask_more_images', $state->data ?? []),
                            $this->structuredSuggestKeyboardForStep('ask_more_images', $state)
                        );
                    }

                    if ($nextStep === 'preview') {
                        $state->advance('preview');

                        return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
                    }

                    if ($nextStep === 'choose_first_language') {
                        $state->advance('choose_first_language');

                        return $this->suggestAutoStartAmharic($chatId, 0, $state, $telegramService);
                    }

                    $state->advance($nextStep);

                    return $this->suggestReplyAndTrack(
                        $telegramService, $state, $chatId,
                        $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                        $this->structuredSuggestKeyboardForStep($nextStep, $state)
                    );
                }

                return $this->suggestReplyAndTrack(
                    $telegramService, $state, $chatId,
                    __('app.telegram_suggest_value_required'),
                    $this->structuredSuggestStepKeyboard($currentStep, $state)
                );
            }

            if ($currentStep === 'enter_url' && ! $this->suggestStepInputLooksUrl($input)) {
                return $this->suggestReplyAndTrack(
                    $telegramService, $state, $chatId,
                    __('app.telegram_suggest_invalid_url'),
                    $this->structuredSuggestStepKeyboard($currentStep, $state)
                );
            }

            $mergeData[$fieldForStep[$currentStep]] = $input;
        }

        // If editing a single field from preview, save and return to preview
        if ($state->get('editing_from_preview') && $mergeData !== []) {
            $mergeData['editing_from_preview'] = null;
            $state->advance('preview', $mergeData);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        $nextStep = $this->structuredSuggestNextStep($state, $currentStep);

        if (in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true) && $langPhase === 1 && $nextStep === 'offer_other_language') {
            $state->data = array_merge($state->data ?? [], $mergeData);

            return $this->suggestStartSynaxariumEnglishPhase($chatId, $messageId, $state, $telegramService);
        }

        if ($currentStep === 'enter_image_caption_en') {
            $images = (array) $state->get('sinksar_images', []);
            $pendingPath = trim((string) $state->get('pending_image_path', ''));
            if ($pendingPath !== '') {
                $images[] = [
                    'path' => $pendingPath,
                    'caption_am' => trim((string) ($mergeData['pending_image_caption_am'] ?? $state->get('pending_image_caption_am', ''))) ?: null,
                    'caption_en' => trim((string) ($mergeData['pending_image_caption_en'] ?? $state->get('pending_image_caption_en', ''))) ?: null,
                ];
            }

            $state->advance('ask_more_images', [
                'sinksar_images' => $images,
                'pending_image_path' => null,
                'pending_image_caption_am' => null,
                'pending_image_caption_en' => null,
            ]);

            if (count($images) >= 5) {
                $state->advance('preview');

                return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
            }

            return $this->suggestReplyAndTrack(
                $telegramService,
                $state,
                $chatId,
                $this->structuredSuggestPrompt('ask_more_images', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('ask_more_images', $state)
            );
        }

        // Lectionary all-in-one: section phase completed
        if ($nextStep === 'lect_section_done') {
            $state->data = array_merge($state->data ?? [], $mergeData);

            return $this->lectHandleSectionDone($chatId, $messageId, $state, $telegramService);
        }

        if ($nextStep === 'preview') {
            $state->advance('preview', $mergeData);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        // Auto-skip choose_first_language — always use Amharic
        if ($nextStep === 'choose_first_language') {
            $state->advance('choose_first_language', $mergeData);

            return $this->suggestAutoStartAmharic($chatId, 0, $state, $telegramService);
        }

        $state->advance($nextStep, $mergeData);

        return $this->suggestReplyAndTrack(
            $telegramService, $state, $chatId,
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
        $contentArea = (string) ($data['content_area'] ?? '');
        $typeLabel = $this->structuredSuggestAreaLabel($contentArea);

        $lines = [
            '<b>📋 '.__('app.telegram_suggest_preview').'</b>',
            '',
            "<b>Type:</b> {$typeLabel}",
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

        if ($contentArea === 'synaxarium') {
            $lines = array_merge($lines, $this->renderSynaxariumSummaryLines($data));
        } elseif ($contentArea === 'synaxarium_celebration') {
            $lines = array_merge($lines, $this->renderSynaxariumCelebrationSummaryLines($data));
        }
        // Lectionary all-in-one: show all filled sections
        if ($contentArea === 'lectionary' && ! empty($data['lect_sections'])) {
            $sections = (array) $data['lect_sections'];
            $filledOrder = (array) ($data['lect_filled_order'] ?? array_keys($sections));
            $maxLen = count($filledOrder) > 4 ? 50 : 80;
            foreach ($filledOrder as $section) {
                if (empty($sections[$section])) {
                    continue;
                }
                $sData = (array) $sections[$section];
                $sData['lectionary_section'] = $section;
                $label = $this->structuredSuggestLectionarySectionLabel((string) $section);
                $ref = $this->structuredSuggestBuildLectionaryReference($sData);
                $refStr = $ref !== null ? ' — '.htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') : '';
                $lines[] = '';
                $lines[] = "📖 <b>{$label}</b>{$refStr}";

                // Geez lines compact
                $geezParts = [];
                for ($g = 1; $g <= 3; $g++) {
                    $geez = trim((string) ($sData["mesbak_geez_{$g}"] ?? ''));
                    if ($geez !== '') {
                        $geezParts[] = htmlspecialchars($geez, ENT_QUOTES, 'UTF-8');
                    }
                }
                if ($geezParts !== []) {
                    $lines[] = '<b>Geez:</b> '.implode(' | ', $geezParts);
                }

                // Bilingual content compact — one line per field
                foreach (['title', 'content_detail'] as $field) {
                    $am = trim((string) ($sData["{$field}_am"] ?? ''));
                    $en = trim((string) ($sData["{$field}_en"] ?? ''));
                    if ($am === '' && $en === '') {
                        continue;
                    }
                    $fieldLabel = $field === 'title' ? 'Title' : 'Text';
                    $parts = [];
                    if ($am !== '') {
                        $parts[] = '🇪🇹 '.(mb_strlen($am) > $maxLen ? mb_substr($am, 0, $maxLen - 1).'…' : $am);
                    }
                    if ($en !== '') {
                        $parts[] = '🇬🇧 '.(mb_strlen($en) > $maxLen ? mb_substr($en, 0, $maxLen - 1).'…' : $en);
                    }
                    $lines[] = "<b>{$fieldLabel}:</b> ".htmlspecialchars(implode(' / ', $parts), ENT_QUOTES, 'UTF-8');
                }
            }
        } elseif ($contentArea === 'lectionary' && ! empty($data['lectionary_section'])) {
            $lines[] = '<b>Section:</b> '.htmlspecialchars(
                $this->structuredSuggestLectionarySectionLabel((string) $data['lectionary_section']),
                ENT_QUOTES,
                'UTF-8'
            );
            $ref = $this->structuredSuggestBuildLectionaryReference($data);
            if ($ref !== null) {
                $lines[] = '<b>Reference:</b> '.htmlspecialchars($ref, ENT_QUOTES, 'UTF-8');
            }
        }
        if (! empty($data['resource_type'])) {
            $lines[] = '<b>Resource Type:</b> '.htmlspecialchars(
                $this->structuredSuggestResourceTypeLabel((string) $data['resource_type']),
                ENT_QUOTES,
                'UTF-8'
            );
        }
        if (! in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true) && ! empty($data['image_path'])) {
            $lines[] = '<b>Image:</b> '.htmlspecialchars(__('app.telegram_suggest_image_attached'), ENT_QUOTES, 'UTF-8');
        }
        if ($contentArea === 'synaxarium' && array_key_exists('is_main', $data)) {
            $lines[] = '<b>Main celebration:</b> '.htmlspecialchars(
                $data['is_main'] ? __('app.yes') : __('app.no'),
                ENT_QUOTES,
                'UTF-8'
            );
        }

        // Show bilingual content grouped by language (non-lectionary or legacy single-section)
        if (($contentArea !== 'lectionary' && ! in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true))
            || ($contentArea === 'lectionary' && empty($data['lect_sections']))) {
            $this->appendBilingualPreviewLines($lines, $data, $contentArea);
        }

        $keyboard = $this->suggestPreviewKeyboard($state);

        $text = implode("\n", $lines);

        // Telegram message limit is 4096 chars — truncate if needed
        if (mb_strlen($text) > 4000) {
            $text = mb_substr($text, 0, 3990).'…';
        }

        // When called from text input (messageId=0), track the bot message for cleanup
        if ($messageId === 0) {
            return $this->suggestReplyAndTrack($telegramService, $state, $chatId, $text, $keyboard, 'HTML');
        }

        return $this->replyOrEdit($telegramService, $chatId, $text, $keyboard, $messageId, 'HTML');
    }

    private function renderSynaxariumSummaryLines(array $data): array
    {
        $lines = [''];
        $sections = [
            'title' => __('app.title_label'),
            'url' => __('app.url_video_label'),
            'text' => __('app.sinksar_text_label'),
            'content_detail' => __('app.sinksar_description_label'),
        ];

        foreach ($sections as $field => $label) {
            $lines[] = '<b>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</b>';
            foreach (['am' => __('app.amharic'), 'en' => __('app.english')] as $lang => $langLabel) {
                $value = trim((string) ($data["{$field}_{$lang}"] ?? ''));
                if ($value === '') {
                    continue;
                }

                $display = mb_strlen($value) > 240
                    ? mb_substr($value, 0, 237).'...'
                    : $value;
                $lines[] = htmlspecialchars($langLabel, ENT_QUOTES, 'UTF-8').': '.htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
            }
            $lines[] = '';
        }

        $images = array_values((array) ($data['sinksar_images'] ?? []));
        $lines[] = '<b>'.__('app.sinksar_images_label').'</b>';
        if ($images === []) {
            $lines[] = __('app.no_content');
        } else {
            foreach ($images as $index => $image) {
                if (! is_array($image)) {
                    continue;
                }

                $lines[] = htmlspecialchars(__('app.telegram_suggest_image_item', ['n' => $index + 1]), ENT_QUOTES, 'UTF-8');
                $captionAm = trim((string) ($image['caption_am'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');
                $captionEn = trim((string) ($image['caption_en'] ?? '')) ?: __('app.telegram_suggest_no_image_caption');
                $lines[] = '  '.htmlspecialchars(__('app.amharic'), ENT_QUOTES, 'UTF-8').': '.htmlspecialchars($captionAm, ENT_QUOTES, 'UTF-8');
                $lines[] = '  '.htmlspecialchars(__('app.english'), ENT_QUOTES, 'UTF-8').': '.htmlspecialchars($captionEn, ENT_QUOTES, 'UTF-8');
            }
        }

        return $lines;
    }

    private function renderSynaxariumCelebrationSummaryLines(array $data): array
    {
        $lines = [''];
        $sections = [
            'title' => __('app.synaxarium_celebration'),
            'content_detail' => __('app.synaxarium_description'),
        ];

        foreach ($sections as $field => $label) {
            $lines[] = '<b>'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</b>';
            foreach (['am' => __('app.amharic'), 'en' => __('app.english')] as $lang => $langLabel) {
                $value = trim((string) ($data["{$field}_{$lang}"] ?? ''));
                if ($value === '') {
                    continue;
                }

                $display = mb_strlen($value) > 240
                    ? mb_substr($value, 0, 237).'...'
                    : $value;
                $lines[] = htmlspecialchars($langLabel, ENT_QUOTES, 'UTF-8').': '.htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
            }
            $lines[] = '';
        }

        $lines[] = '<b>'.htmlspecialchars(__('app.synaxarium_image'), ENT_QUOTES, 'UTF-8').'</b>';
        $lines[] = ! empty($data['image_path'])
            ? __('app.telegram_suggest_image_attached')
            : __('app.no_content');
        $lines[] = '';
        $lines[] = '<b>'.htmlspecialchars(__('app.synaxarium_is_main'), ENT_QUOTES, 'UTF-8').'</b>';
        $lines[] = ! empty($data['is_main']) ? __('app.yes') : __('app.no');
        $lines[] = '';
        $lines[] = '<b>'.htmlspecialchars(__('app.synaxarium_sort_order'), ENT_QUOTES, 'UTF-8').'</b>';
        $lines[] = (string) ((int) ($data['sort_order'] ?? 0));

        return $lines;
    }

    private function appendBilingualPreviewLines(array &$lines, array $data, string $contentArea): void
    {
        $fieldLabels = match ($contentArea) {
            'bible_reading' => [
                'reference' => 'Reference',
                'summary' => 'Summary',
                'text' => 'Bible Text',
            ],
            'synaxarium' => [
                'title' => 'Celebration',
                'url' => 'Link',
                'text' => 'Full Text',
                'content_detail' => 'Description',
            ],
            'synaxarium_celebration' => [
                'title' => 'Celebration',
                'content_detail' => 'Description',
            ],
            'mezmur' => [
                'title' => 'Title',
                'url' => 'Link',
                'content_detail' => 'Notes',
                'lyrics' => 'Lyrics',
            ],
            'spiritual_book' => [
                'title' => 'Title',
                'url' => 'Link',
                'content_detail' => 'Notes',
            ],
            'reference_resource' => [
                'title' => 'Title',
                'url' => 'Link',
                'content_detail' => 'Notes',
            ],
            'daily_message' => [
                'title' => 'Title',
                'content_detail' => 'Message',
            ],
            'lectionary' => [
                'content_detail' => 'Details',
            ],
            default => [
                'title' => 'Title',
                'content_detail' => 'Details',
            ],
        };

        foreach (['en' => '🇬🇧 English', 'am' => '🇪🇹 አማርኛ'] as $lang => $langLabel) {
            $hasContent = false;
            foreach ($fieldLabels as $field => $_label) {
                if (! empty($data["{$field}_{$lang}"])) {
                    $hasContent = true;
                    break;
                }
            }
            if (! $hasContent) {
                continue;
            }

            $lines[] = '';
            $lines[] = "<b>── {$langLabel} ──</b>";
            foreach ($fieldLabels as $field => $label) {
                $value = (string) ($data["{$field}_{$lang}"] ?? '');
                if ($value !== '') {
                    $display = mb_strlen($value) > 200
                        ? mb_substr($value, 0, 197).'…'
                        : $value;
                    $lines[] = "<b>{$label}:</b> ".htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
                }
            }
        }
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
        $firstLang = (string) ($data['first_language'] ?? 'en');

        // Lectionary all-in-one: determine language from sections
        $hasEn = false;
        $hasAm = false;
        if ($contentArea === 'lectionary' && ! empty($data['lect_sections'])) {
            foreach ((array) $data['lect_sections'] as $sData) {
                foreach (['title', 'content_detail'] as $f) {
                    if (! empty($sData["{$f}_en"])) {
                        $hasEn = true;
                    }
                    if (! empty($sData["{$f}_am"])) {
                        $hasAm = true;
                    }
                }
            }
        } else {
            $hasEn = ! empty($data['reference_en']) || ! empty($data['title_en']) || ! empty($data['url_en']) || ! empty($data['text_en']) || ! empty($data['content_detail_en']) || ! empty($data['lyrics_en']);
            $hasAm = ! empty($data['reference_am']) || ! empty($data['title_am']) || ! empty($data['url_am']) || ! empty($data['text_am']) || ! empty($data['content_detail_am']) || ! empty($data['lyrics_am']);
        }
        $language = ($hasEn && $hasAm) ? 'both' : ($hasAm ? 'am' : 'en');

        // Use first available title/reference for the legacy columns
        $legacyTitle = $this->structuredSuggestStoredTitle($data);
        $legacyUrl = $data['url_en'] ?? $data['url_am'] ?? null;
        $legacyDetail = $data['content_detail_en'] ?? $data['content_detail_am'] ?? null;

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
                'title' => $legacyTitle,
                'reference' => $this->structuredSuggestStoredReference($data),
                'url' => $legacyUrl,
                'content_detail' => $legacyDetail,
                'image_path' => $this->structuredSuggestPrimaryImagePath($data),
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

        $dateLabel = $this->structuredSuggestDateLabel($data);

        // Offer to continue suggesting for the same date
        $continueKeyboard = $this->suggestContinueKeyboard($contentArea, $data);

        // Preserve date info for potential continuation
        $filledSections = $contentArea === 'lectionary' && ! empty($data['lect_filled_order'])
            ? (array) $data['lect_filled_order']
            : array_values(array_unique(array_merge(
                (array) ($data['filled_lectionary_sections'] ?? []),
                $contentArea === 'lectionary' && ! empty($data['lectionary_section'])
                    ? [$data['lectionary_section']]
                    : []
            )));
        $state->update([
            'step' => 'awaiting_continue',
            'data' => [
                'ethiopian_month' => $data['ethiopian_month'] ?? null,
                'ethiopian_day' => $data['ethiopian_day'] ?? null,
                'content_area' => $contentArea,
                'filled_lectionary_sections' => $filledSections,
            ],
        ]);

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '✅ '.__('app.telegram_suggest_submitted')."\n\n".__('app.telegram_suggest_continue_prompt', ['date' => $dateLabel]),
            $continueKeyboard,
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

    /**
     * Handle edit of a specific field from the preview screen.
     * Sets editing_from_preview so we return to preview after input.
     */
    private function handleSuggestEditField(
        string $chatId,
        int $messageId,
        string $action,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $contentArea = (string) $state->get('content_area', '');
        $editPart = substr($action, strlen('suggest_edit_'));

        // Lectionary section edit: suggest_edit_lect_{section}
        if (str_starts_with($editPart, 'lect_')) {
            $section = substr($editPart, strlen('lect_'));
            $sections = (array) $state->get('lect_sections', []);
            $sectionData = (array) ($sections[$section] ?? []);

            // Restore section data into flat state for editing
            $restoreKeys = ['lectionary_chapter', 'lectionary_verse_range', 'lectionary_book',
                'lectionary_book_label', 'mesbak_geez_1', 'mesbak_geez_2', 'mesbak_geez_3'];
            $restore = ['editing_from_preview' => true, 'editing_lect_section' => $section];
            foreach ($restoreKeys as $key) {
                $restore[$key] = $sectionData[$key] ?? null;
            }
            foreach (['title', 'content_detail'] as $field) {
                foreach (['am', 'en'] as $lang) {
                    $restore["{$field}_{$lang}"] = $sectionData["{$field}_{$lang}"] ?? null;
                }
            }

            $state->advance('lect_section_intro', array_merge($restore, [
                'lect_current_section' => $section,
                'lectionary_section' => $section,
                'lang_phase' => 1,
                'current_language' => (string) $state->get('first_language', 'am'),
            ]));

            return $this->replyOrEdit(
                $telegramService, $chatId,
                $this->structuredSuggestPrompt('lect_section_intro', $state->data ?? []),
                $this->structuredSuggestKeyboardForStep('lect_section_intro', $state),
                $messageId, 'HTML'
            );
        }

        if ($contentArea === 'synaxarium_celebration') {
            if ($editPart === 'scope') {
                $state->advance('choose_scope', ['editing_from_preview' => true]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt('choose_scope', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('choose_scope', $state),
                    $messageId
                );
            }

            if ($editPart === 'date') {
                $step = (string) $state->get('entry_scope', '') === 'yearly' ? 'choose_month' : 'choose_day';
                $state->advance($step, ['editing_from_preview' => true]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt($step, $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep($step, $state),
                    $messageId
                );
            }

            if ($editPart === 'image') {
                $state->advance('await_image', ['editing_from_preview' => true]);
                $prompt = $this->structuredSuggestPrompt('await_image', $state->data ?? []);
                if (! empty($state->get('image_path'))) {
                    $prompt .= "\n\n".__('app.telegram_suggest_image_attached');
                }

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $prompt,
                    $this->structuredSuggestKeyboardForStep('await_image', $state),
                    $messageId
                );
            }

            if ($editPart === 'main') {
                $state->advance('choose_main', ['editing_from_preview' => true]);

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $this->structuredSuggestPrompt('choose_main', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('choose_main', $state),
                    $messageId
                );
            }

            if ($editPart === 'sort_order') {
                $state->advance('enter_sort_order', [
                    'editing_from_preview' => true,
                    'lang_phase' => 2,
                ]);

                $existing = (string) ((int) $state->get('sort_order', 0));
                $prompt = $this->structuredSuggestPrompt('enter_sort_order', $state->data ?? []);
                $prompt .= "\n\n<i>".__('app.telegram_suggest_current').' '.htmlspecialchars($existing, ENT_QUOTES, 'UTF-8').'</i>';
                $prompt .= "\n".__('app.telegram_suggest_type_to_replace');

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    $prompt,
                    $this->structuredSuggestKeyboardForStep('enter_sort_order', $state),
                    $messageId,
                    'HTML'
                );
            }
        }

        // Field edit: suggest_edit_{step}_{lang}
        if (preg_match('/^(enter_\w+)_(am|en)$/', $editPart, $m)) {
            $step = $m[1];
            $lang = $m[2];
            $langPhase = $lang === 'en' ? 2 : 1;

            $state->advance($step, [
                'editing_from_preview' => true,
                'current_language' => $lang,
                'lang_phase' => $langPhase,
            ]);

            $fieldForStep = match ($step) {
                'enter_reference' => "reference_{$lang}",
                'enter_summary' => "summary_{$lang}",
                'enter_text' => "text_{$lang}",
                'enter_title' => "title_{$lang}",
                'enter_url' => "url_{$lang}",
                'enter_detail' => "content_detail_{$lang}",
                'enter_lyrics' => "lyrics_{$lang}",
                default => null,
            };

            $existing = $fieldForStep ? ((string) $state->get($fieldForStep, '')) : '';
            $prompt = $this->structuredSuggestPrompt($step, $state->data ?? []);
            if ($existing !== '') {
                $prompt .= "\n\n<i>".__('app.telegram_suggest_current').' '.htmlspecialchars($existing, ENT_QUOTES, 'UTF-8').'</i>';
                $prompt .= "\n".__('app.telegram_suggest_type_to_replace');
            }

            return $this->replyOrEdit(
                $telegramService, $chatId,
                $prompt,
                $this->structuredSuggestKeyboardForStep($step, $state),
                $messageId, 'HTML'
            );
        }

        // Fallback: return to preview
        return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
    }

    /** Go back one step in the wizard, re-prompting with existing value pre-filled. */
    private function handleSuggestBack(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        if ($state->step === 'manage_images') {
            $state->advance('preview');

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        if ($state->step === 'manage_image') {
            return $this->showSuggestImageManager($chatId, $messageId, $state, $telegramService);
        }

        if (in_array($state->step, ['edit_image_caption_am', 'edit_image_caption_en'], true)) {
            return $this->showSuggestImageActions(
                $chatId,
                $messageId,
                $state,
                $telegramService,
                (int) $state->get('edit_image_index', 0)
            );
        }

        $currentStep = $state->step;
        $contentArea = (string) $state->get('content_area', '');
        $langPhase = (int) $state->get('lang_phase', 1);
        $lectionarySection = (string) $state->get('lectionary_section', '');

        // If editing from preview, cancel the edit and return to preview
        if ($state->get('editing_from_preview')) {
            $state->advance('preview', ['editing_from_preview' => null]);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        $prevStep = $this->structuredSuggestPreviousStep($state, $currentStep);

        // Skip choose_first_language when going back — go to the step before it
        if ($prevStep === 'choose_first_language') {
            $state->advance($prevStep);
            $prevStep = $this->structuredSuggestPreviousStep($state, $prevStep);
        }

        // Determine correct lang_phase and current_language for the target step
        $bilingualFieldSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
        $targetLangPhase = $langPhase;
        $targetLang = (string) $state->get('current_language', 'am');

        if ($langPhase === 2 && in_array($prevStep, $bilingualFieldSteps, true)) {
            $targetLang = 'en';
        } elseif ($langPhase === 2 && ! in_array($prevStep, $bilingualFieldSteps, true)
            && ! in_array($prevStep, ['await_image', 'enter_image_caption_am', 'enter_image_caption_en', 'ask_more_images', 'choose_main', 'enter_sort_order'], true)) {
            $targetLangPhase = 1;
            $targetLang = (string) $state->get('first_language', 'am');
        }

        // Lectionary: back from section intro goes to previous section's intro or date confirm
        if ($currentStep === 'lect_section_intro') {
            $allSections = ['title_description', 'pauline', 'catholic', 'acts', 'mesbak', 'gospel', 'qiddase'];
            $currentSection = (string) $state->get('lect_current_section', '');
            $currentIdx = array_search($currentSection, $allSections, true);

            if ($currentIdx !== false && $currentIdx > 0) {
                $prevSection = $allSections[$currentIdx - 1];
                $state->advance('lect_section_intro', [
                    'lect_current_section' => $prevSection,
                    'lectionary_section' => $prevSection,
                    'lang_phase' => 1,
                    'current_language' => (string) $state->get('first_language', 'am'),
                ]);

                return $this->replyOrEdit(
                    $telegramService, $chatId,
                    $this->structuredSuggestPrompt('lect_section_intro', $state->data ?? []),
                    $this->structuredSuggestKeyboardForStep('lect_section_intro', $state),
                    $messageId, 'HTML'
                );
            }

            $prevStep = 'confirm_date';
            $targetLangPhase = 1;
            $targetLang = (string) $state->get('first_language', 'am');
        }

        $state->advance($prevStep, [
            'lang_phase' => $targetLangPhase,
            'current_language' => $targetLang,
        ]);

        // For non-text-input steps, just show the keyboard
        $callbackSteps = [
            'choose_area', 'choose_month', 'choose_day', 'confirm_date', 'choose_scope',
            'choose_first_language', 'choose_book', 'choose_resource_type', 'choose_main',
            'offer_other_language', 'lect_section_intro', 'ask_more_images', 'await_image',
        ];
        if (in_array($prevStep, $callbackSteps, true)) {
            return $this->replyOrEdit(
                $telegramService, $chatId,
                $this->structuredSuggestPrompt($prevStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($prevStep, $state),
                $messageId, 'HTML'
            );
        }

        // For text-input steps, show current value if any
        $lang = $targetLang;
        $bilingualSteps = ['enter_reference', 'enter_summary', 'enter_text', 'enter_title', 'enter_url', 'enter_detail', 'enter_lyrics'];

        $fieldForStep = [
            'enter_chapter' => 'lectionary_chapter',
            'enter_verse_range' => 'lectionary_verse_range',
            'enter_geez_1' => 'mesbak_geez_1',
            'enter_geez_2' => 'mesbak_geez_2',
            'enter_geez_3' => 'mesbak_geez_3',
            'enter_image_caption_am' => 'pending_image_caption_am',
            'enter_image_caption_en' => 'pending_image_caption_en',
            'enter_sort_order' => 'sort_order',
        ];
        if (in_array($prevStep, $bilingualSteps, true)) {
            $fieldForStep[$prevStep] = match ($prevStep) {
                'enter_reference' => "reference_{$lang}",
                'enter_summary' => "summary_{$lang}",
                'enter_text' => "text_{$lang}",
                'enter_title' => "title_{$lang}",
                'enter_url' => "url_{$lang}",
                'enter_detail' => "content_detail_{$lang}",
                default => $prevStep,
            };
        }

        $existing = isset($fieldForStep[$prevStep]) ? ((string) $state->get($fieldForStep[$prevStep], '')) : '';
        $prompt = $this->structuredSuggestPrompt($prevStep, $state->data ?? []);
        if ($existing !== '') {
            $prompt .= "\n\n<i>".__('app.telegram_suggest_current').' '.htmlspecialchars($existing, ENT_QUOTES, 'UTF-8').'</i>';
            $prompt .= "\n".__('app.telegram_suggest_type_to_replace');
        }

        return $this->replyOrEdit(
            $telegramService, $chatId,
            $prompt,
            $this->structuredSuggestKeyboardForStep($prevStep, $state),
            $messageId, 'HTML'
        );
    }

    /** Returns the step before the current one, or 'choose_type' if at the start. */
    private function suggestPreviousStep(string $type, string $currentStep): string
    {
        $flow = match ($type) {
            'bible' => ['enter_reference', 'enter_url', 'enter_detail'],
            'sinksar' => ['enter_title', 'enter_url', 'enter_detail'],
            'mezmur' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail', 'enter_lyrics'],
            'book' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail'],
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
            'mezmur' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail', 'enter_lyrics', 'preview'],
            'book' => ['enter_title', 'enter_author', 'enter_url', 'enter_detail', 'preview'],
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
        int $userMessageId,
        array $photos,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        if ($state->step !== 'await_image') {
            return response()->json(['success' => true, 'message' => 'Photo ignored.']);
        }

        // Delete user's photo message and bot's previous prompt to keep chat clean
        $this->suggestDeleteStaleMessages($chatId, $userMessageId, $state, $telegramService);

        $photo = collect($photos)
            ->filter(fn ($item) => is_array($item) && filled($item['file_id'] ?? null))
            ->sortBy(fn ($item) => (int) ($item['file_size'] ?? 0))
            ->last();

        if (! is_array($photo) || blank($photo['file_id'] ?? null)) {
            return $this->suggestReplyAndTrack(
                $telegramService, $state, $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image', $state)
            );
        }

        $download = $telegramService->downloadFile((string) $photo['file_id']);
        if (! is_array($download)) {
            return $this->suggestReplyAndTrack(
                $telegramService, $state, $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image', $state)
            );
        }

        $extension = strtolower((string) ($download['extension'] ?? 'jpg'));
        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $pendingPath = trim((string) $state->get('pending_image_path', ''));
        if ($pendingPath !== '' && str_starts_with($pendingPath, 'telegram-suggestions/')) {
            Storage::disk('public')->delete($pendingPath);
        }

        $path = 'telegram-suggestions/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;
        if (! Storage::disk('public')->put($path, $download['contents'])) {
            return $this->suggestReplyAndTrack(
                $telegramService, $state, $chatId,
                __('app.telegram_suggest_photo_upload_failed'),
                $this->structuredSuggestStepKeyboard('await_image', $state)
            );
        }

        if ((string) $state->get('content_area', '') === 'synaxarium_celebration') {
            $existingPath = trim((string) $state->get('image_path', ''));
            if (
                $existingPath !== ''
                && $existingPath !== $path
                && str_starts_with($existingPath, 'telegram-suggestions/')
            ) {
                Storage::disk('public')->delete($existingPath);
            }

            $nextStep = $state->get('editing_from_preview') ? 'preview' : 'choose_main';
            $mergeData = ['image_path' => $path];
            if ($state->get('editing_from_preview')) {
                $mergeData['editing_from_preview'] = null;
            }

            $state->advance($nextStep, $mergeData);

            if ($nextStep === 'preview') {
                return $this->showSuggestPreview($chatId, 0, $state, $telegramService);
            }

            return $this->suggestReplyAndTrack(
                $telegramService,
                $state,
                $chatId,
                $this->structuredSuggestPrompt($nextStep, $state->data ?? []),
                $this->structuredSuggestKeyboardForStep($nextStep, $state)
            );
        }

        $state->advance('enter_image_caption_am', [
            'pending_image_path' => $path,
            'pending_image_caption_am' => null,
            'pending_image_caption_en' => null,
            'image_path' => $path,
        ]);

        return $this->suggestReplyAndTrack(
            $telegramService, $state, $chatId,
            $this->structuredSuggestPrompt('enter_image_caption_am', $state->data ?? []),
            $this->structuredSuggestKeyboardForStep('enter_image_caption_am', $state)
        );
    }

    private function structuredSuggestAreaKeyboard(): array
    {
        return ['inline_keyboard' => [
            [['text' => __('app.telegram_suggest_area_synaxarium_celebration'), 'callback_data' => 'suggest_area_synaxarium_celebration']],
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

    private function structuredSuggestDayKeyboard(int $month, ?int $forcedMaxDay = null): array
    {
        $maxDay = $forcedMaxDay ?? ($month === 13 ? 6 : 30);
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

    private function structuredSuggestDayKeyboardForState(TelegramBotState $state): array
    {
        if (
            (string) $state->get('content_area', '') === 'synaxarium_celebration'
            && (string) $state->get('entry_scope', '') === 'monthly'
        ) {
            return $this->structuredSuggestDayKeyboard(1, 30);
        }

        return $this->structuredSuggestDayKeyboard((int) $state->get('ethiopian_month', 1));
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
            'choose_area' => $this->structuredSuggestAreaKeyboard(),
            'choose_month' => $this->structuredSuggestMonthKeyboard(),
            'choose_day' => $this->structuredSuggestDayKeyboardForState($state),
            'choose_scope' => $this->structuredSuggestScopeKeyboard(),
            'choose_first_language' => $this->suggestFirstLanguageKeyboard(),
            'choose_lectionary_section' => $this->structuredSuggestLectionarySectionKeyboard(),
            'lect_section_intro' => $this->lectSectionIntroKeyboard(),
            'awaiting_continue' => $this->suggestContinueKeyboard(
                (string) $state->get('content_area', ''),
                $state->data ?? []
            ),
            'confirm_date' => $this->structuredSuggestConfirmDateKeyboard(),
            'choose_book' => $this->structuredSuggestLectionaryBookKeyboard($state),
            'choose_resource_type' => $this->structuredSuggestResourceTypeKeyboard(),
            'choose_main' => $this->structuredSuggestMainChoiceKeyboard(),
            'ask_more_images' => $this->suggestAddMoreImagesKeyboard(),
            'offer_other_language' => $this->suggestOfferOtherLanguageKeyboard(
                ((string) $state->get('first_language', 'en')) === 'en' ? 'am' : 'en'
            ),
            default => $this->structuredSuggestStepKeyboard($step, $state),
        };
    }

    private function structuredSuggestStepKeyboard(string $step, ?TelegramBotState $state = null): array
    {
        $rows = [];
        if ($state && $this->structuredSuggestStepIsOptional($state, $step)) {
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
        $currentLang = (string) ($data['current_language'] ?? 'en');
        $langLabel = $currentLang === 'am' ? '🇪🇹 አማርኛ' : '🇬🇧 English';
        $langTag = " [{$langLabel}]";

        $basePrompt = match ($step) {
            'choose_area' => __('app.telegram_suggest_choose_area'),
            'choose_month' => __('app.telegram_suggest_choose_month'),
            'choose_day' => __('app.telegram_suggest_choose_day'),
            'choose_scope' => __('app.telegram_suggest_choose_scope'),
            'choose_first_language' => __('app.telegram_suggest_choose_first_language'),
            'choose_lectionary_section' => __('app.telegram_suggest_choose_lectionary_section'),
            'lect_section_intro' => $this->lectSectionIntroPrompt($data),
            'awaiting_continue' => __('app.telegram_suggest_continue_prompt', ['date' => $this->structuredSuggestDateLabel($data)]),
            'confirm_date' => $this->structuredSuggestConfirmDatePrompt($data),
            'choose_book' => __('app.telegram_suggest_choose_book'),
            'enter_geez_1' => __('app.telegram_suggest_enter_geez_line', ['n' => 1]),
            'enter_geez_2' => __('app.telegram_suggest_enter_geez_line', ['n' => 2]),
            'enter_geez_3' => __('app.telegram_suggest_enter_geez_line', ['n' => 3]),
            'choose_resource_type' => __('app.telegram_suggest_choose_resource_type'),
            'enter_chapter' => match ((string) ($data['lectionary_section'] ?? '')) {
                'mesbak' => __('app.telegram_suggest_enter_psalm_number'),
                default => __('app.telegram_suggest_enter_chapter'),
            },
            'enter_verse_range' => __('app.telegram_suggest_enter_verse_range'),
            'enter_title' => match (true) {
                $contentArea === 'synaxarium' => __('app.telegram_suggest_enter_sinksar_title'),
                $contentArea === 'synaxarium_celebration' => __('app.telegram_suggest_enter_saint_name'),
                $contentArea === 'daily_message' => __('app.telegram_suggest_enter_daily_message_title'),
                $contentArea === 'mezmur' => __('app.telegram_suggest_enter_mezmur_title'),
                $contentArea === 'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_title'),
                $contentArea === 'reference_resource' => __('app.telegram_suggest_enter_resource_title'),
                $contentArea === 'lectionary' && ($data['lectionary_section'] ?? '') === 'qiddase' => __('app.telegram_suggest_enter_qiddase'),
                $contentArea === 'lectionary' => __('app.telegram_suggest_enter_lectionary_title'),
                default => __('app.telegram_suggest_enter_title'),
            },
            'enter_reference' => match ($contentArea) {
                'bible_reading' => __('app.telegram_suggest_enter_bible_reference'),
                'lectionary' => __('app.telegram_suggest_enter_bible_reading_reference'),
                default => __('app.telegram_suggest_enter_lectionary_reference'),
            },
            'enter_summary' => __('app.telegram_suggest_enter_bible_summary'),
            'enter_text' => __('app.telegram_suggest_enter_bible_text'),
            'enter_url' => match ($contentArea) {
                'synaxarium' => __('app.telegram_suggest_enter_sinksar_link'),
                'mezmur' => __('app.telegram_suggest_enter_mezmur_link'),
                'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_link'),
                'reference_resource' => __('app.telegram_suggest_enter_resource_link'),
                default => __('app.telegram_suggest_enter_url'),
            },
            'await_image' => __('app.telegram_suggest_send_photo'),
            'enter_sort_order' => __('app.telegram_suggest_enter_sort_order'),
            'enter_image_caption_am' => __('app.telegram_suggest_enter_sinksar_image_caption', ['lang' => __('app.amharic')]),
            'enter_image_caption_en' => __('app.telegram_suggest_enter_sinksar_image_caption', ['lang' => __('app.english')]),
            'edit_image_caption_am' => __('app.telegram_suggest_enter_sinksar_image_caption', ['lang' => __('app.amharic')]),
            'edit_image_caption_en' => __('app.telegram_suggest_enter_sinksar_image_caption', ['lang' => __('app.english')]),
            'ask_more_images' => __('app.telegram_suggest_add_another_image_prompt'),
            'enter_lyrics' => __('app.telegram_suggest_enter_mezmur_lyrics'),
            'enter_detail' => match (true) {
                $contentArea === 'synaxarium' => __('app.telegram_suggest_enter_saint_description'),
                $contentArea === 'synaxarium_celebration' => __('app.telegram_suggest_enter_celebration_description'),
                $contentArea === 'daily_message' => __('app.telegram_suggest_enter_daily_message_body'),
                $contentArea === 'mezmur' => __('app.telegram_suggest_enter_mezmur_notes'),
                $contentArea === 'spiritual_book' => __('app.telegram_suggest_enter_spiritual_book_notes'),
                $contentArea === 'reference_resource' => __('app.telegram_suggest_enter_resource_notes'),
                $contentArea === 'lectionary' && ($data['lectionary_section'] ?? '') === 'title_description' => __('app.telegram_suggest_enter_lectionary_description'),
                $contentArea === 'lectionary' => __('app.telegram_suggest_enter_lectionary_text'),
                $contentArea === 'bible_reading' => __('app.telegram_suggest_enter_bible_reading_notes'),
                default => __('app.telegram_suggest_enter_detail'),
            },
            'offer_other_language' => $this->structuredSuggestOfferOtherLangPrompt($data),
            'choose_main' => __('app.telegram_suggest_choose_main_celebration'),
            default => __('app.telegram_suggest_enter_detail'),
        };

        // Append language tag to bilingual field steps
        $bilingualSteps = ['enter_reference', 'enter_summary', 'enter_text', 'enter_title', 'enter_url', 'enter_detail', 'enter_lyrics'];
        if (in_array($step, $bilingualSteps, true) && ! empty($data['current_language'])) {
            if ($contentArea === 'synaxarium' && $step === 'enter_text') {
                $basePrompt = __('app.telegram_suggest_enter_sinksar_text');
            }

            return $basePrompt.$langTag;
        }

        return $basePrompt;
    }

    private function structuredSuggestOfferOtherLangPrompt(array $data): string
    {
        $firstLang = (string) ($data['first_language'] ?? 'en');
        $otherLang = $firstLang === 'en' ? 'am' : 'en';
        $otherLabel = $otherLang === 'am' ? '🇪🇹 አማርኛ' : '🇬🇧 English';

        return __('app.telegram_suggest_offer_other_lang', ['lang' => $otherLabel]);
    }

    private function structuredSuggestFlow(TelegramBotState $state): array
    {
        $contentArea = (string) $state->get('content_area', '');
        $lectionarySection = (string) $state->get('lectionary_section', '');
        $base = ['choose_area', 'choose_month', 'choose_day', 'confirm_date'];

        if ($contentArea === 'lectionary') {
            // All-in-one flow: section steps end at lect_section_done (no per-section offer_other_language)
            $refSteps = match ($lectionarySection) {
                'pauline', 'catholic', 'gospel' => ['choose_book', 'enter_chapter', 'enter_verse_range'],
                'acts' => ['enter_chapter', 'enter_verse_range'],
                'mesbak' => ['enter_chapter', 'enter_verse_range', 'enter_geez_1', 'enter_geez_2', 'enter_geez_3'],
                'qiddase', 'title_description' => [],
                default => [],
            };

            $contentSteps = match ($lectionarySection) {
                'title_description' => ['enter_title', 'enter_detail'],
                'qiddase' => ['enter_title'],
                default => ['enter_detail'],
            };

            return array_merge($base, ['lect_section_intro'], $refSteps, $contentSteps, ['lect_section_done']);
        }

        return match ($contentArea) {
            'bible_reading' => [...$base, 'choose_first_language', 'enter_reference', 'enter_summary', 'enter_text', 'offer_other_language', 'preview'],
            'mezmur' => [...$base, 'choose_first_language', 'enter_title', 'enter_url', 'enter_detail', 'enter_lyrics', 'offer_other_language', 'preview'],
            'synaxarium' => [...$base, 'choose_scope', 'choose_first_language', 'enter_title', 'enter_url', 'enter_text', 'enter_detail', 'offer_other_language', 'await_image', 'preview'],
            'synaxarium_celebration' => array_values(array_filter([
                'choose_area',
                'choose_scope',
                (string) $state->get('entry_scope', '') === 'yearly' ? 'choose_month' : null,
                'choose_day',
                'choose_first_language',
                'enter_title',
                'enter_detail',
                'offer_other_language',
                'await_image',
                'choose_main',
                'enter_sort_order',
                'preview',
            ])),
            'spiritual_book' => [...$base, 'choose_first_language', 'enter_title', 'enter_url', 'enter_detail', 'offer_other_language', 'preview'],
            'reference_resource' => [...$base, 'choose_resource_type', 'choose_first_language', 'enter_title', 'enter_url', 'enter_detail', 'offer_other_language', 'preview'],
            'daily_message' => [...$base, 'choose_first_language', 'enter_title', 'enter_detail', 'offer_other_language', 'preview'],
            default => ['choose_area'],
        };
    }

    private function structuredSuggestStepIsOptional(TelegramBotState $state, string $step): bool
    {
        $contentArea = (string) $state->get('content_area', '');
        $langPhase = (int) $state->get('lang_phase', 1);
        $lectionarySection = (string) $state->get('lectionary_section', '');

        // In the second language phase, all fields are optional (user can skip any)
        if ($langPhase === 2 && in_array($step, $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection), true)) {
            return ! (in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true) && $step === 'enter_title');
        }

        return match ($step) {
            'await_image' => ! (
                $contentArea === 'synaxarium_celebration'
                && $state->get('editing_from_preview')
            ),
            'enter_summary', 'enter_text' => true,
            'enter_geez_1', 'enter_geez_2', 'enter_geez_3' => true,
            'enter_image_caption_am', 'enter_image_caption_en' => true,
            'enter_sort_order' => $contentArea === 'synaxarium_celebration',
            'enter_url' => in_array($contentArea, ['synaxarium', 'mezmur', 'spiritual_book'], true),
            'enter_lyrics' => $contentArea === 'mezmur',
            'enter_detail' => in_array($contentArea, ['synaxarium', 'synaxarium_celebration', 'mezmur', 'spiritual_book', 'reference_resource', 'bible_reading', 'lectionary'], true),
            default => false,
        };
    }

    private function structuredSuggestNextStep(TelegramBotState $state, string $currentStep): string
    {
        $langPhase = (int) $state->get('lang_phase', 1);
        $contentArea = (string) $state->get('content_area', '');
        $lectionarySection = (string) $state->get('lectionary_section', '');

        // In lang_phase 2, only navigate through bilingual field steps
        if ($langPhase === 2) {
            if ($contentArea === 'synaxarium') {
                $imageStep = match ($currentStep) {
                    'await_image' => 'preview',
                    'enter_image_caption_am' => 'enter_image_caption_en',
                    'enter_image_caption_en' => 'ask_more_images',
                    'ask_more_images' => 'preview',
                    default => null,
                };

                if ($imageStep !== null) {
                    return $imageStep;
                }

                $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
                $index = array_search($currentStep, $bilingualSteps, true);

                if ($index !== false && isset($bilingualSteps[$index + 1])) {
                    return $bilingualSteps[$index + 1];
                }

                return 'await_image';
            }

            if ($contentArea === 'synaxarium_celebration') {
                $postLanguageStep = match ($currentStep) {
                    'await_image' => 'choose_main',
                    'enter_sort_order' => 'preview',
                    default => null,
                };

                if ($postLanguageStep !== null) {
                    return $postLanguageStep;
                }

                $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
                $index = array_search($currentStep, $bilingualSteps, true);

                if ($index !== false && isset($bilingualSteps[$index + 1])) {
                    return $bilingualSteps[$index + 1];
                }

                return 'await_image';
            }

            $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
            $index = array_search($currentStep, $bilingualSteps, true);

            if ($index !== false && isset($bilingualSteps[$index + 1])) {
                return $bilingualSteps[$index + 1];
            }

            // Lectionary all-in-one: English phase for this section done
            if ($contentArea === 'lectionary' && $state->get('lect_sections') !== null) {
                return 'lect_section_done';
            }

            return 'preview';
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
        $langPhase = (int) $state->get('lang_phase', 1);
        $contentArea = (string) $state->get('content_area', '');
        $lectionarySection = (string) $state->get('lectionary_section', '');

        // In lang_phase 2, only navigate through bilingual field steps
        if ($langPhase === 2) {
            if ($contentArea === 'synaxarium') {
                $imageStep = match ($currentStep) {
                    'preview' => ((array) $state->get('sinksar_images', [])) === [] ? 'await_image' : 'manage_images',
                    'await_image' => 'enter_detail',
                    'enter_image_caption_am' => 'await_image',
                    'enter_image_caption_en' => 'enter_image_caption_am',
                    'ask_more_images' => 'await_image',
                    default => null,
                };

                if ($imageStep !== null) {
                    return $imageStep;
                }

                $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
                $index = array_search($currentStep, $bilingualSteps, true);

                if ($index !== false && $index > 0) {
                    return $bilingualSteps[$index - 1];
                }

                return end($bilingualSteps) ?: 'enter_detail';
            }

            if ($contentArea === 'synaxarium_celebration') {
                $postLanguageStep = match ($currentStep) {
                    'preview' => 'enter_sort_order',
                    'enter_sort_order' => 'choose_main',
                    'choose_main' => 'await_image',
                    'await_image' => 'enter_detail',
                    default => null,
                };

                if ($postLanguageStep !== null) {
                    return $postLanguageStep;
                }

                $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);
                $index = array_search($currentStep, $bilingualSteps, true);

                if ($index !== false && $index > 0) {
                    return $bilingualSteps[$index - 1];
                }

                return end($bilingualSteps) ?: 'enter_detail';
            }

            $bilingualSteps = $this->structuredSuggestBilingualFieldSteps($contentArea, $lectionarySection);

            if ($currentStep === 'preview') {
                // Lectionary: back from preview goes to last section intro
                if ($contentArea === 'lectionary' && $state->get('lect_sections') !== null) {
                    return 'lect_section_intro';
                }

                return end($bilingualSteps) ?: 'offer_other_language';
            }

            $index = array_search($currentStep, $bilingualSteps, true);

            if ($index !== false && $index > 0) {
                return $bilingualSteps[$index - 1];
            }

            // Lectionary: at first English step, back goes to last Amharic content step
            if ($contentArea === 'lectionary' && $state->get('lect_sections') !== null) {
                $flow = $this->structuredSuggestFlow($state);
                $filtered = array_values(array_filter($flow, fn ($s) => $s !== 'lect_section_done'));

                return end($filtered) ?: 'lect_section_intro';
            }

            if (in_array($contentArea, ['synaxarium', 'synaxarium_celebration'], true)) {
                return end($bilingualSteps) ?: 'enter_detail';
            }

            // At the first bilingual step in phase 2, go back to offer_other_language
            return 'offer_other_language';
        }

        $flow = $this->structuredSuggestFlow($state);

        if ($currentStep === 'preview') {
            // Lectionary: back from preview goes to last section intro
            if ($contentArea === 'lectionary' && ! empty($state->get('lect_sections'))) {
                return 'lect_section_intro';
            }
            $steps = array_values(array_filter($flow, fn ($step) => $step !== 'preview' && $step !== 'lect_section_done'));

            return end($steps) ?: 'choose_area';
        }

        $index = array_search($currentStep, $flow, true);
        if ($index === false || $index === 0) {
            return 'choose_area';
        }

        return $flow[$index - 1];
    }

    /**
     * Returns the first content-entry step (after language selection) for a content area.
     */
    private function structuredSuggestFirstFieldStep(TelegramBotState $state): string
    {
        $contentArea = (string) $state->get('content_area', '');

        return match ($contentArea) {
            'bible_reading' => 'enter_reference',
            'synaxarium' => 'enter_title',
            'synaxarium_celebration' => 'enter_title',
            'mezmur', 'spiritual_book', 'daily_message', 'reference_resource' => 'enter_title',
            'lectionary' => match ((string) $state->get('lectionary_section', '')) {
                'title_description', 'qiddase' => 'enter_title',
                default => 'enter_detail',
            },
            default => 'enter_title',
        };
    }

    /**
     * Returns the bilingual field steps for a content area (steps that get language suffix).
     */
    private function structuredSuggestBilingualFieldSteps(string $contentArea, string $lectionarySection = ''): array
    {
        if ($contentArea === 'lectionary' && $lectionarySection === 'title_description') {
            return ['enter_title', 'enter_detail'];
        }
        if ($contentArea === 'lectionary' && $lectionarySection === 'qiddase') {
            return ['enter_title'];
        }

        return match ($contentArea) {
            'bible_reading' => ['enter_reference', 'enter_summary', 'enter_text'],
            'synaxarium' => ['enter_title', 'enter_url', 'enter_text', 'enter_detail'],
            'synaxarium_celebration' => ['enter_title', 'enter_detail'],
            'mezmur' => ['enter_title', 'enter_url', 'enter_detail', 'enter_lyrics'],
            'spiritual_book' => ['enter_title', 'enter_url', 'enter_detail'],
            'reference_resource' => ['enter_title', 'enter_url', 'enter_detail'],
            'daily_message' => ['enter_title', 'enter_detail'],
            'lectionary' => ['enter_detail'],
            default => ['enter_title', 'enter_detail'],
        };
    }

    private function structuredSuggestAreaLabel(string $contentArea): string
    {
        return match ($contentArea) {
            'lectionary' => '📜 '.__('app.telegram_suggest_area_lectionary'),
            'bible_reading' => '📖 '.__('app.telegram_suggest_area_bible_reading'),
            'mezmur' => '🎵 '.__('app.telegram_suggest_area_mezmur'),
            'synaxarium' => __('app.telegram_suggest_area_sinksar'),
            'spiritual_book' => '📚 '.__('app.telegram_suggest_area_spiritual_book'),
            'reference_resource' => '🔗 '.__('app.telegram_suggest_area_reference_resource'),
            'daily_message' => '💬 '.__('app.telegram_suggest_area_daily_message'),
            'synaxarium_celebration' => __('app.telegram_suggest_area_synaxarium_celebration'),
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

        if (($data['content_area'] ?? null) === 'synaxarium_celebration' && ($data['entry_scope'] ?? null) === 'monthly') {
            return $day > 0 ? 'Day '.$day : __('app.telegram_suggest_unknown_date');
        }

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

    private function lectSectionIntroPrompt(array $data): string
    {
        $section = (string) ($data['lect_current_section'] ?? '');
        $label = $this->structuredSuggestLectionarySectionLabel($section);
        $idx = array_search($section, self::LECTIONARY_SECTIONS, true);
        $num = ($idx !== false) ? ($idx + 1) : 0;
        $total = count(self::LECTIONARY_SECTIONS);

        return "📖 <b>{$label}</b> ({$num}/{$total})\n\n".__('app.telegram_suggest_lect_section_fill_or_skip');
    }

    private function lectSectionIntroKeyboard(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '📝 '.__('app.telegram_suggest_lect_fill'), 'callback_data' => 'lect_fill'],
                ['text' => '⏭ '.__('app.telegram_suggest_skip'), 'callback_data' => 'lect_skip'],
            ],
            [
                ['text' => '⬅️ '.__('app.telegram_suggest_back'), 'callback_data' => 'suggest_back'],
                ['text' => '❌ '.__('app.telegram_suggest_cancel'), 'callback_data' => 'suggest_cancel'],
            ],
        ]];
    }

    private function structuredSuggestLegacyType(string $contentArea): string
    {
        return match ($contentArea) {
            'lectionary', 'bible_reading' => 'bible',
            'mezmur' => 'mezmur',
            'synaxarium' => 'sinksar',
            'synaxarium_celebration' => 'sinksar',
            'spiritual_book' => 'book',
            'reference_resource' => 'reference',
            'daily_message' => 'reference',
            default => 'reference',
        };
    }

    private function structuredSuggestStoredTitle(array $data): ?string
    {
        return match ((string) ($data['content_area'] ?? '')) {
            'lectionary' => ! empty($data['lect_sections'])
                ? __('app.telegram_suggest_area_lectionary').' ('.count((array) $data['lect_sections']).' sections)'
                : __('app.telegram_suggest_area_lectionary').': '.$this->structuredSuggestLectionarySectionLabel((string) ($data['lectionary_section'] ?? '')),
            'bible_reading' => $data['reference_en'] ?? $data['reference_am'] ?? __('app.telegram_suggest_area_bible_reading'),
            default => $data['title_en'] ?? $data['title_am'] ?? null,
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

            return $ref;
        }

        return ! empty($data['reference']) ? (string) $data['reference'] : null;
    }

    private function structuredSuggestStoredReference(array $data): ?string
    {
        $parts = [$this->structuredSuggestDateLabel($data)];

        if (in_array($data['content_area'] ?? null, ['synaxarium', 'synaxarium_celebration'], true) && ! empty($data['entry_scope'])) {
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
        } elseif (! empty($data['reference_en'])) {
            $parts[] = (string) $data['reference_en'];
        } elseif (! empty($data['reference_am'])) {
            $parts[] = (string) $data['reference_am'];
        }

        if (($data['content_area'] ?? null) === 'reference_resource' && ! empty($data['resource_type'])) {
            $parts[] = $this->structuredSuggestResourceTypeLabel((string) $data['resource_type']);
        }

        $parts = array_values(array_filter($parts, fn ($value) => filled($value)));

        return $parts === [] ? null : implode(' • ', $parts);
    }

    private function structuredSuggestPrimaryImagePath(array $data): ?string
    {
        foreach ((array) ($data['sinksar_images'] ?? []) as $image) {
            if (! is_array($image)) {
                continue;
            }

            $path = trim((string) ($image['path'] ?? ''));
            if ($path !== '') {
                return $path;
            }
        }

        $legacyPath = trim((string) ($data['image_path'] ?? ''));

        return $legacyPath !== '' ? $legacyPath : null;
    }

    private function structuredSuggestPayload(array $data): array
    {
        $payload = [
            'content_area' => $data['content_area'] ?? null,
            'ethiopian_month' => $data['ethiopian_month'] ?? null,
            'ethiopian_day' => $data['ethiopian_day'] ?? null,
            'entry_scope' => $data['entry_scope'] ?? null,
            'first_language' => $data['first_language'] ?? null,
            'lectionary_section' => $data['lectionary_section'] ?? null,
            'lectionary_section_label' => ! empty($data['lectionary_section'])
                ? $this->structuredSuggestLectionarySectionLabel((string) $data['lectionary_section'])
                : null,
            'lectionary_book' => $data['lectionary_book'] ?? null,
            'lectionary_book_label' => $data['lectionary_book_label'] ?? null,
            'lectionary_chapter' => $data['lectionary_chapter'] ?? null,
            'lectionary_verse_range' => $data['lectionary_verse_range'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_type_label' => ! empty($data['resource_type'])
                ? $this->structuredSuggestResourceTypeLabel((string) $data['resource_type'])
                : null,
            'reference' => $this->structuredSuggestBuildLectionaryReference($data) ?? $data['reference_en'] ?? $data['reference_am'] ?? null,
            'is_main' => $data['is_main'] ?? null,
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : null,
            'sinksar_images' => $data['sinksar_images'] ?? [],
        ];

        // Lectionary all-in-one: include all sections
        if (! empty($data['lect_sections'])) {
            $sections = [];
            foreach ((array) $data['lect_sections'] as $section => $sData) {
                $sPayload = [];
                foreach (['lectionary_book', 'lectionary_book_label', 'lectionary_chapter', 'lectionary_verse_range', 'mesbak_geez_1', 'mesbak_geez_2', 'mesbak_geez_3'] as $key) {
                    if (! empty($sData[$key])) {
                        $sPayload[$key] = $sData[$key];
                    }
                }
                foreach (['en', 'am'] as $lang) {
                    foreach (['title', 'content_detail'] as $field) {
                        $key = "{$field}_{$lang}";
                        if (! empty($sData[$key])) {
                            $sPayload[$key] = $sData[$key];
                        }
                    }
                }
                $sPayload['section_label'] = $this->structuredSuggestLectionarySectionLabel((string) $section);
                $sData['lectionary_section'] = $section;
                $ref = $this->structuredSuggestBuildLectionaryReference($sData);
                if ($ref !== null) {
                    $sPayload['reference'] = $ref;
                }
                $sections[$section] = $sPayload;
            }
            $payload['sections'] = $sections;
        }

        // Add all bilingual fields
        foreach (['en', 'am'] as $lang) {
            foreach (['reference', 'summary', 'text', 'title', 'url', 'content_detail', 'lyrics'] as $field) {
                $key = "{$field}_{$lang}";
                if (! empty($data[$key])) {
                    $payload[$key] = $data[$key];
                }
            }
        }

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

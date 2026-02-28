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
use App\Services\TelegramAuthService;
use App\Services\TelegramBotBuilderService;
use App\Services\TelegramContentFormatter;
use App\Services\TelegramService;
use App\Services\UltraMsgService;
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
        private readonly TelegramBotBuilderService $telegramBotBuilder,
        private readonly TelegramContentFormatter $contentFormatter,
        private readonly UltraMsgService $ultraMsg
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

        $actor = $this->actorFromChatId($chatId);
        if ($actor instanceof Member || $actor instanceof User) {
            $this->applyLocaleForActor($actor);
        } else {
            $locale = $this->guestLocale($chatId);
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

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
        $normalized = strtolower(trim($text));

        // Check for an active wizard state first — wizard input takes priority
        $activeState = TelegramBotState::getAnyActive($chatId);
        if ($activeState !== null) {
            // Universal cancel keyword
            if ($normalized === 'cancel') {
                $activeState->clear();

                return $this->reply(
                    $telegramService,
                    $chatId,
                    $activeState->action === 'link_admin'
                        ? __('app.telegram_link_cancelled')
                        : __('app.telegram_suggest_cancelled')
                );
            }

            if ($activeState->action === 'link_admin') {
                return $this->handleLinkAdminText($chatId, $text, $activeState, $telegramAuthService, $telegramService);
            }

            if ($activeState->action === 'link_member') {
                return $this->handleLinkMemberText($chatId, $text, $activeState, $telegramService);
            }

            if ($activeState->action === 'suggest') {
                $actor = $this->actorFromChatId($chatId);
                if ($actor) {
                    $this->applyLocaleForActor($actor);
                }
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
                $locale = $this->guestLocale($chatId);
                app()->setLocale($locale);
                Translation::loadFromDb($locale);

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

            $this->applyLocaleForActor($actor);

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

            $this->applyLocaleForActor($actor);

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
        $actor = $this->actorFromChatId($chatId);
        if ($actor) {
            $this->applyLocaleForActor($actor);
        } else {
            $locale = $this->guestLocale($chatId);
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

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
        $actor = $this->actorFromChatId($chatId);
        if ($actor) {
            $this->applyLocaleForActor($actor);
        } else {
            $locale = $this->guestLocale($chatId);
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

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
        $actor = $this->actorFromChatId($chatId);
        if ($actor) {
            $this->applyLocaleForActor($actor);
        } else {
            $locale = $this->guestLocale($chatId);
            app()->setLocale($locale);
            Translation::loadFromDb($locale);
        }

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
        if (! $actor instanceof Member) {
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

        $newLocale = match ($action) {
            'lang_en' => 'en',
            'lang_am' => 'am',
            'lang_toggle' => ($actor->locale ?? 'en') === 'en' ? 'am' : 'en',
            default => null,
        };
        if ($newLocale === null) {
            return response()->json(['success' => true]);
        }

        $actor->update(['locale' => $newLocale]);
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

    private function languageToggleButton(Member $member): array
    {
        $locale = $member->locale ?? 'en';
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

    private function applyLocaleForActor(Member|User $actor): void
    {
        $locale = 'am';
        if ($actor instanceof Member && $this->memberHasLocale($actor)) {
            $locale = $actor->locale;
        } elseif ($actor instanceof User) {
            $locale = request()->attributes->get('telegram_language_code', 'am');
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
            app()->setLocale('en');
            Translation::loadFromDb('en');

            return $this->replyAfterDelete($telegramService, $chatId, $messageId, __('app.telegram_bot_unlink_not_linked'), $this->startChoiceKeyboard());
        }

        $this->applyLocaleForActor($actor);
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
            default => response()->json(['success' => true]),
        };
    }

    /**
     * Validates the member's phone number, sends a WhatsApp verification code,
     * and advances the wizard to the verify_code step.
     */
    private function handleLinkMemberPhone(
        string $chatId,
        string $input,
        TelegramBotState $state,
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

        $member = Member::query()->where('whatsapp_phone', $normalized)->first();

        if (! $member instanceof Member) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_phone_not_found')
            );
        }

        if (! $member->whatsapp_phone) {
            $state->clear();

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_no_whatsapp_member')
            );
        }

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
            'member_id' => $member->id,
            'code' => $code,
            'code_expires_at' => now()->addMinutes(10)->toIso8601String(),
        ]);

        return $this->reply(
            $telegramService,
            $chatId,
            __('app.telegram_link_member_code_sent')
        );
    }

    /**
     * Validates the 6-digit code, links the member's Telegram account, and shows the main menu.
     */
    private function handleLinkMemberCode(
        string $chatId,
        string $input,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $expectedCode = (string) $state->get('code', '');
        $codeExpiresAt = $state->get('code_expires_at');
        $memberId = (int) $state->get('member_id', 0);

        if ($codeExpiresAt && now()->isAfter($codeExpiresAt)) {
            $state->clear();

            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_wrong_code')
            );
        }

        if (trim($input) !== $expectedCode) {
            return $this->reply(
                $telegramService,
                $chatId,
                __('app.telegram_link_wrong_code')
            );
        }

        $member = Member::query()->find($memberId);
        if (! $member instanceof Member) {
            $state->clear();

            return $this->reply($telegramService, $chatId, __('app.telegram_link_phone_not_found'));
        }

        $this->syncTelegramChatId($member, $chatId);
        $state->clear();

        $this->applyLocaleForActor($member);

        $keyboard = $this->mainMenuKeyboard($member, app(TelegramAuthService::class));

        return $this->reply(
            $telegramService,
            $chatId,
            '✅ '.__('app.telegram_link_success')."\n\n".__('app.telegram_menu_heading'),
            $keyboard
        );
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
            app()->setLocale('en');
            Translation::loadFromDb('en');

            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
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

        $this->applyLocaleForActor($actor);

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

        $this->applyLocaleForActor($actor);
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
            app()->setLocale('en');
            Translation::loadFromDb('en');

            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        $this->applyLocaleForActor($actor);

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

        $this->applyLocaleForActor($actor);

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
            app()->setLocale('en');
            Translation::loadFromDb('en');

            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        $this->applyLocaleForActor($actor);

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
            app()->setLocale('en');
            Translation::loadFromDb('en');

            return $this->replyOrEdit($telegramService, $chatId, __('app.telegram_choose_language'), $this->languageChoiceKeyboard(), $messageId);
        }

        $this->applyLocaleForActor($actor);

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

        $this->applyLocaleForActor($actor);

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

        // Must be a linked admin/editor/writer User — not a Member
        if (! $actor instanceof User) {
            // Start WhatsApp linking inline, remembering that they wanted to suggest
            TelegramBotState::startFor($chatId, 'link_admin', 'ask_phone', [
                'pending_action' => str_starts_with($action, 'suggest') ? 'suggest' : $action,
            ]);

            $text = '🔗 '.__('app.telegram_link_heading')."\n\n"
                .__('app.telegram_link_intro')."\n\n"
                .__('app.telegram_link_enter_phone');

            return $this->replyOrEdit($telegramService, $chatId, $text, [], $messageId);
        }

        // Apply locale so all suggest UI (Back, Cancel, prompts) is consistently translated
        $this->applyLocaleForActor($actor);

        // ── My Suggestions ────────────────────────────────────────────────
        if ($action === 'my_suggestions') {
            return $this->handleMySuggestions($chatId, $messageId, $actor, $telegramAuthService, $telegramService);
        }

        // ── Entry: start wizard ───────────────────────────────────────────
        if ($action === 'suggest') {
            return $this->startSuggestWizard($chatId, $messageId, $telegramService);
        }

        // ── Language chosen ───────────────────────────────────────────────
        if (str_starts_with($action, 'suggest_lang_')) {
            $lang = str_replace('suggest_lang_', '', $action); // 'en' or 'am'
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            $state->advance('choose_type', ['language' => $lang]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_type'),
                $this->suggestTypeKeyboard(),
                $messageId
            );
        }

        // ── Type chosen ───────────────────────────────────────────────────
        if (str_starts_with($action, 'suggest_type_')) {
            $type = str_replace('suggest_type_', '', $action);
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            $nextStep = $this->suggestFirstStep($type);
            $state->advance($nextStep, ['type' => $type]);

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                $this->suggestStepPrompt($nextStep, $type),
                $this->suggestStepKeyboard($nextStep, 'choose_type'),
                $messageId
            );
        }

        // ── Skip optional field ───────────────────────────────────────────
        if ($action === 'suggest_skip') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->advanceSuggestStep($chatId, $messageId, '', $state, $telegramService);
        }

        // ── Confirm ───────────────────────────────────────────────────────
        if ($action === 'suggest_confirm') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }

            return $this->confirmSuggestion($chatId, $messageId, $actor, $state, $telegramAuthService, $telegramService);
        }

        // ── Back ──────────────────────────────────────────────────────────
        if ($action === 'suggest_back') {
            $state = TelegramBotState::getActive($chatId, 'suggest');
            if (! $state) {
                return $this->startSuggestWizard($chatId, $messageId, $telegramService);
            }
            return $this->handleSuggestBack($chatId, $messageId, $state, $telegramService);
        }

        // ── Cancel ────────────────────────────────────────────────────────
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
        $type = (string) $state->get('type', '');
        $currentStep = $state->step;

        // Map current step to the data field it fills
        $fieldForStep = [
            'enter_reference' => 'reference',
            'enter_title' => 'title',
            'enter_author' => 'author',
            'enter_url' => 'url',
            'enter_detail' => 'content_detail',
        ];

        $mergeData = [];
        if (isset($fieldForStep[$currentStep]) && $input !== '') {
            $mergeData[$fieldForStep[$currentStep]] = $input;
        }

        $nextStep = $this->suggestNextStep($type, $currentStep);

        if ($nextStep === 'preview') {
            $state->advance('preview', $mergeData);

            return $this->showSuggestPreview($chatId, $messageId, $state, $telegramService);
        }

        $state->advance($nextStep, $mergeData);

        $keyboard = $this->suggestStepKeyboard($nextStep, $currentStep);

        return $this->reply(
            $telegramService,
            $chatId,
            $this->suggestStepPrompt($nextStep, $type),
            $keyboard
        );
    }

    private function showSuggestPreview(
        string $chatId,
        int $messageId,
        TelegramBotState $state,
        TelegramService $telegramService
    ): JsonResponse {
        $data = $state->data ?? [];
        $type = $data['type'] ?? '?';
        $lang = strtoupper($data['language'] ?? '?');

        $typeLabel = match ($type) {
            'bible' => '📖 Bible',
            'mezmur' => '🎵 Mezmur',
            'sinksar' => '📖 Sinksar',
            'book' => '📚 Book',
            'reference' => '🔗 Reference',
            default => ucfirst($type),
        };

        $lines = [
            '<b>📋 '.__('app.telegram_suggest_preview').'</b>',
            '',
            "<b>Type:</b> {$typeLabel} [{$lang}]",
        ];

        if (! empty($data['reference']) && $type === 'bible') {
            $lines[] = '<b>Reference:</b> '.htmlspecialchars($data['reference'], ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['title'])) {
            $lines[] = '<b>Title:</b> '.htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['author'])) {
            $lines[] = '<b>Author:</b> '.htmlspecialchars($data['author'], ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['url'])) {
            $lines[] = '<b>Link:</b> '.htmlspecialchars($data['url'], ENT_QUOTES, 'UTF-8');
        }
        if (! empty($data['content_detail'])) {
            $lines[] = '<b>Notes:</b> '.htmlspecialchars($data['content_detail'], ENT_QUOTES, 'UTF-8');
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
        $type = $data['type'] ?? 'reference';
        $language = $data['language'] ?? 'en';

        ContentSuggestion::create([
            'user_id' => $user->id,
            'type' => $type,
            'language' => $language,
            'title' => $data['title'] ?? null,
            'reference' => $data['reference'] ?? null,
            'author' => $data['author'] ?? null,
            'url' => $data['url'] ?? null,
            'content_detail' => $data['content_detail'] ?? null,
            'submitter_name' => $user->name,
            'status' => 'pending',
        ]);

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
        $type = (string) $state->get('type', '');
        $prevStep = $this->suggestPreviousStep($type, $state->step);

        // If we're at (or before) the first text step, go back to the type selector
        if ($prevStep === 'choose_type' || $state->step === 'choose_type') {
            // If already on choose_type, go back to language selection
            if ($state->step === 'choose_type') {
                $state->advance('choose_language');

                return $this->replyOrEdit(
                    $telegramService,
                    $chatId,
                    '💡 '.__('app.telegram_suggest')."\n\n".__('app.telegram_suggest_choose_language'),
                    $this->suggestLanguageKeyboard(),
                    $messageId
                );
            }

            $state->advance('choose_type');

            return $this->replyOrEdit(
                $telegramService,
                $chatId,
                __('app.telegram_suggest_choose_type'),
                $this->suggestTypeKeyboard(),
                $messageId
            );
        }

        $state->advance($prevStep);

        $fieldForStep = [
            'enter_reference' => 'reference',
            'enter_title' => 'title',
            'enter_author' => 'author',
            'enter_url' => 'url',
            'enter_detail' => 'content_detail',
        ];

        $existing = isset($fieldForStep[$prevStep]) ? ((string) $state->get($fieldForStep[$prevStep], '')) : '';
        $prompt = $this->suggestStepPrompt($prevStep, $type);
        if ($existing !== '') {
            $prompt .= "\n\n<i>".__('app.telegram_suggest_current')." ".htmlspecialchars($existing, ENT_QUOTES, 'UTF-8')."</i>";
            $prompt .= "\n".__('app.telegram_suggest_type_to_replace');
        }

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            $prompt,
            $this->suggestStepKeyboard($prevStep, $this->suggestPreviousStep($type, $prevStep)),
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
        $rows[] = [
            ['text' => '📱 '.__('app.telegram_staff_main_page'), 'callback_data' => 'staff_main_page'],
            ['text' => '⚙️ '.__('app.telegram_staff_portal'), 'callback_data' => 'staff_portal'],
        ];

        if ($this->telegramBotBuilder->buttonEnabled('help', 'admin')) {
            $rows[] = [['text' => $this->telegramBotBuilder->buttonLabel('help', 'admin', __('app.help')), 'callback_data' => 'help']];
        }

        $rows[] = [['text' => __('app.telegram_bot_unlink'), 'callback_data' => 'unlink']];

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
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof User) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $this->applyLocaleForActor($actor);

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
        $actor = $this->actorFromChatId($chatId);
        if (! $actor instanceof User) {
            return $this->replyAfterDelete($telegramService, $chatId, $messageId, $this->notLinkedMessage(), $this->startChoiceKeyboard());
        }

        $this->applyLocaleForActor($actor);

        return $this->replyOrEdit(
            $telegramService,
            $chatId,
            '⚙️ '.__('app.telegram_staff_portal'),
            $this->staffPortalKeyboard($actor, $telegramAuthService),
            $messageId
        );
    }
}

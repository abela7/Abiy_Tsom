<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\TelegramBotBuilderService;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Manage Telegram bot integration settings for admin controls.
 */
class TelegramSettingsController extends Controller
{
    /**
     * Show Telegram settings page.
     */
    public function settings(TelegramBotBuilderService $telegramBotBuilderService): View
    {
        $builderConfig = $telegramBotBuilderService->getConfig();
        $envPath = base_path('.env');
        $envExists = File::exists($envPath);

        $botToken = '';
        $botUsername = '';
        $defaultChatId = '';

        if ($envExists) {
            $botToken = config('services.telegram.bot_token') ?? '';
            $botUsername = config('services.telegram.bot_username') ?? '';
            $defaultChatId = config('services.telegram.default_chat_id') ?? '';
        }

        $publicBotName = $this->publicBotUsername($botUsername);
        $webhookUrl = route('webhooks.telegram');
        $telegramMenuStartLink = $publicBotName ? ('https://t.me/' . $publicBotName . '?start=menu') : '';
        $telegramHomeStartLink = $publicBotName ? ('https://t.me/' . $publicBotName . '?start=home') : '';
        $telegramAdminStartLink = $publicBotName ? ('https://t.me/' . $publicBotName . '?start=admin') : '';
        $telegramTodayStartLink = $publicBotName ? ('https://t.me/' . $publicBotName . '?start=today') : '';
        $telegramMiniWebAppUrl = route('telegram.mini.connect');
        $telegramMiniMemberStartApp = $publicBotName ? ('https://t.me/' . $publicBotName . '?startapp=member') : '';
        $telegramMiniAdminStartApp = $publicBotName ? ('https://t.me/' . $publicBotName . '?startapp=admin') : '';

        return view('admin.telegram.index', compact(
            'botToken',
            'botUsername',
            'defaultChatId',
            'publicBotName',
            'webhookUrl',
            'builderConfig',
            'telegramMenuStartLink',
            'telegramHomeStartLink',
            'telegramAdminStartLink',
            'telegramTodayStartLink',
            'telegramMiniWebAppUrl',
            'telegramMiniMemberStartApp',
            'telegramMiniAdminStartApp'
        ));
    }

    /**
     * Update Telegram settings in .env.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bot_token' => ['nullable', 'string', 'max:255'],
            'bot_username' => ['nullable', 'string', 'max:255'],
            'default_chat_id' => ['nullable', 'string', 'max:255'],
        ]);

        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return redirect()
                ->route('admin.telegram.settings')
                ->with('error', '.env file not found.');
        }

        $envContent = File::get($envPath);

        $botToken = trim((string) ($validated['bot_token'] ?? ''));
        $botUsername = trim((string) ($validated['bot_username'] ?? ''));
        $defaultChatId = trim((string) ($validated['default_chat_id'] ?? ''));

        $envContent = $this->updateEnvVariable($envContent, 'TELEGRAM_BOT_TOKEN', $botToken);
        $envContent = $this->updateEnvVariable($envContent, 'TELEGRAM_BOT_USERNAME', $this->publicBotUsername($botUsername));
        $envContent = $this->updateEnvVariable($envContent, 'TELEGRAM_DEFAULT_CHAT_ID', $defaultChatId);
        File::put($envPath, $envContent);

        config()->set('services.telegram.bot_token', $botToken);
        config()->set('services.telegram.bot_username', $this->publicBotUsername($botUsername));
        config()->set('services.telegram.default_chat_id', $defaultChatId);

        if (is_file(base_path('bootstrap/cache/config.php'))) {
            @unlink(base_path('bootstrap/cache/config.php'));
        }

        return redirect()
            ->route('admin.telegram.settings')
            ->with('success', __('app.telegram_settings_saved'));
    }

    /**
     * Save Telegram bot builder profile (buttons, labels, and command descriptions).
     */
    public function updateBuilder(
        Request $request,
        TelegramBotBuilderService $telegramBotBuilderService
    ): RedirectResponse {
        $payload = [
            'ui' => [
                'menu_button_label' => (string) $request->input('ui.menu_button_label', 'Open Abiy Tsom'),
                'welcome_message' => (string) $request->input('ui.welcome_message', 'Welcome to Abiy Tsom.'),
                'help_message' => (string) $request->input('ui.help_message', 'Use the buttons below. If your account is linked, the app opens in one tap.'),
                'not_linked_message' => (string) $request->input('ui.not_linked_message', 'Your account is not linked yet. Open the app and continue securely.'),
            ],
            'commands' => [
                'menu' => [
                    'label' => (string) $request->input('commands.menu.label', 'Menu'),
                    'description' => (string) $request->input('commands.menu.description', 'Show quick actions'),
                    'enabled' => $request->boolean('commands.menu.enabled'),
                ],
                'home' => [
                    'label' => (string) $request->input('commands.home.label', 'Home'),
                    'description' => (string) $request->input('commands.home.description', 'Open member home'),
                    'enabled' => $request->boolean('commands.home.enabled'),
                ],
                'day' => [
                    'label' => (string) $request->input('commands.day.label', 'Today'),
                    'description' => (string) $request->input('commands.day.description', 'Open today content'),
                    'enabled' => $request->boolean('commands.day.enabled'),
                ],
                'admin' => [
                    'label' => (string) $request->input('commands.admin.label', 'Admin panel'),
                    'description' => (string) $request->input('commands.admin.description', 'Open admin dashboard'),
                    'enabled' => $request->boolean('commands.admin.enabled'),
                ],
                'help' => [
                    'label' => (string) $request->input('commands.help.label', 'Help'),
                    'description' => (string) $request->input('commands.help.description', 'Show command help'),
                    'enabled' => $request->boolean('commands.help.enabled'),
                ],
            ],
            'member_buttons' => [
                'home' => [
                    'label' => (string) $request->input('member_buttons.home.label', 'Home'),
                    'enabled' => $request->boolean('member_buttons.home.enabled'),
                ],
                'today' => [
                    'label' => (string) $request->input('member_buttons.today.label', 'Today'),
                    'enabled' => $request->boolean('member_buttons.today.enabled'),
                ],
                'me' => [
                    'label' => (string) $request->input('member_buttons.me.label', 'My links'),
                    'enabled' => $request->boolean('member_buttons.me.enabled'),
                ],
                'help' => [
                    'label' => (string) $request->input('member_buttons.help.label', 'Help'),
                    'enabled' => $request->boolean('member_buttons.help.enabled'),
                ],
            ],
            'admin_buttons' => [
                'admin' => [
                    'label' => (string) $request->input('admin_buttons.admin.label', 'Admin panel'),
                    'enabled' => $request->boolean('admin_buttons.admin.enabled'),
                ],
                'help' => [
                    'label' => (string) $request->input('admin_buttons.help.label', 'Help'),
                    'enabled' => $request->boolean('admin_buttons.help.enabled'),
                ],
            ],
        ];

        $telegramBotBuilderService->saveConfig($payload);

        return redirect()
            ->route('admin.telegram.settings')
            ->with('success', __('app.telegram_builder_updated'));
    }

    /**
     * Push Telegram command menu to show in the chat UI.
     */
    public function syncMenu(
        TelegramService $telegramService,
        TelegramBotBuilderService $telegramBotBuilderService
    ): RedirectResponse
    {
        if (! $telegramService->isConfigured()) {
            return redirect()
                ->route('admin.telegram.settings')
                ->with('error', __('app.telegram_not_configured_for_menu'));
        }

        $commands = $telegramBotBuilderService->enabledCommands();

        if (! $telegramService->setMyCommands($commands)) {
            return redirect()
                ->route('admin.telegram.settings')
                ->with('error', __('app.telegram_menu_sync_failed'));
        }

        $menuButtonConfigured = $telegramService->setChatMenuButton(
            $telegramBotBuilderService->menuButtonLabel(),
            route('telegram.mini.connect')
        );

        return redirect()
            ->route('admin.telegram.settings')
            ->with(
                'success',
                $menuButtonConfigured
                    ? __('app.telegram_menu_synced')
                    : __('app.telegram_menu_synced')
            );
    }

    /**
     * Send a test Telegram message.
     */
    public function test(Request $request, TelegramService $telegramService): JsonResponse
    {
        $validated = $request->validate([
            'bot_token' => ['required', 'string', 'max:255'],
            'chat_id' => ['required', 'string', 'max:255'],
        ]);

        try {
            config()->set('services.telegram.bot_token', trim($validated['bot_token']));

            if (! $telegramService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.telegram_test_not_configured'),
                ], 400);
            }

            $testMessage = __('app.telegram_test_message', ['app' => config('app.name', 'Abiy Tsom')]);
            $sent = $telegramService->sendTextMessage(trim($validated['chat_id']), $testMessage);

            if (! $sent) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.telegram_test_failed'),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __('app.telegram_test_success'),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Network error: Cannot reach Telegram API.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Telegram test connection error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function updateEnvVariable(string $envContent, string $key, string $value): string
    {
        $escapedValue = $this->escapeEnvValue($value);
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $envContent)) {
            return preg_replace($pattern, "{$key}={$escapedValue}", $envContent);
        }

        if (! str_ends_with($envContent, "\n")) {
            $envContent .= "\n";
        }

        return $envContent."{$key}={$escapedValue}\n";
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }

    private function publicBotUsername(?string $username): string
    {
        $trimmed = trim((string) $username);
        if ($trimmed === '') {
            return '';
        }

        return ltrim($trimmed, '@');
    }
}

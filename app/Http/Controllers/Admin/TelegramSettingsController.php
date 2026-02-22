<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function settings(): View
    {
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

        return view('admin.telegram.index', compact(
            'botToken',
            'botUsername',
            'defaultChatId',
            'publicBotName',
            'webhookUrl',
            'telegramMenuStartLink',
            'telegramHomeStartLink',
            'telegramAdminStartLink',
            'telegramTodayStartLink'
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
     * Push Telegram command menu to show in the chat UI.
     */
    public function syncMenu(TelegramService $telegramService): RedirectResponse
    {
        if (! $telegramService->isConfigured()) {
            return redirect()
                ->route('admin.telegram.settings')
                ->with('error', __('app.telegram_not_configured_for_menu'));
        }

        $commands = [
            ['command' => 'menu', 'description' => __('app.telegram_menu_command_desc')],
            ['command' => 'help', 'description' => __('app.telegram_help_command_desc')],
            ['command' => 'connect', 'description' => __('app.telegram_connect_command_desc')],
        ];

        if (! $telegramService->setMyCommands($commands)) {
            return redirect()
                ->route('admin.telegram.settings')
                ->with('error', __('app.telegram_menu_sync_failed'));
        }

        return redirect()
            ->route('admin.telegram.settings')
            ->with('success', __('app.telegram_menu_synced'));
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

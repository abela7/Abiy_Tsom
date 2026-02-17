<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UltraMsgService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Manage WhatsApp/UltraMsg integration settings.
 */
class WhatsAppSettingsController extends Controller
{
    /**
     * Show WhatsApp settings page (credentials + webhook).
     */
    public function settings(UltraMsgService $ultraMsgService): View
    {
        $envPath = base_path('.env');
        $envExists = File::exists($envPath);

        $instanceId = '';
        $token = '';
        $baseUrl = 'https://api.ultramsg.com';
        $currentSettings = null;

        if ($envExists) {
            $instanceId = config('services.ultramsg.instance_id') ?? '';
            $token = config('services.ultramsg.token') ?? '';
            $baseUrl = config('services.ultramsg.base_url') ?? 'https://api.ultramsg.com';

            // Fetch current webhook settings from UltraMsg
            if ($ultraMsgService->isConfigured()) {
                $currentSettings = $ultraMsgService->getInstanceSettings();
            }
        }

        return view('admin.whatsapp.index', compact(
            'instanceId',
            'token',
            'baseUrl',
            'currentSettings'
        ));
    }

    /**
     * Update WhatsApp credentials in .env file.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instance_id' => ['nullable', 'string', 'max:100'],
            'token' => ['nullable', 'string', 'max:255'],
            'base_url' => ['nullable', 'url', 'max:255'],
        ]);

        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return redirect()
                ->route('admin.whatsapp.settings')
                ->with('error', '.env file not found.');
        }

        $envContent = File::get($envPath);

        $instanceId = trim($validated['instance_id'] ?? '');
        $token = trim($validated['token'] ?? '');
        $baseUrl = trim($validated['base_url'] ?? 'https://api.ultramsg.com');

        $envContent = $this->updateEnvVariable($envContent, 'ULTRAMSG_INSTANCE_ID', $instanceId);
        $envContent = $this->updateEnvVariable($envContent, 'ULTRAMSG_TOKEN', $token);
        $envContent = $this->updateEnvVariable($envContent, 'ULTRAMSG_BASE_URL', $baseUrl);

        File::put($envPath, $envContent);

        return redirect()
            ->route('admin.whatsapp.settings')
            ->with('success', __('app.whatsapp_settings_saved'));
    }

    /**
     * Test UltraMsg connection.
     */
    public function test(Request $request, UltraMsgService $ultraMsgService): JsonResponse
    {
        $validated = $request->validate([
            'instance_id' => ['required', 'string'],
            'token' => ['required', 'string'],
            'test_phone' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ]);

        try {
            config()->set('services.ultramsg.instance_id', trim($validated['instance_id']));
            config()->set('services.ultramsg.token', trim($validated['token']));

            if (! $ultraMsgService->isConfigured()) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.whatsapp_test_not_configured'),
                ], 400);
            }

            $testMessage = __('app.whatsapp_test_message', ['app' => config('app.name', 'Abiy Tsom')]);
            $sent = $ultraMsgService->sendTextMessage($validated['test_phone'], $testMessage);

            if (! $sent) {
                return response()->json([
                    'success' => false,
                    'message' => __('app.whatsapp_test_failed'),
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => __('app.whatsapp_test_success'),
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Network error: Cannot reach UltraMsg API. Check your internet connection.',
            ], 500);
        } catch (\Exception $e) {
            Log::error('WhatsApp test connection error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update webhook settings on UltraMsg instance.
     */
    public function updateWebhook(Request $request, UltraMsgService $ultraMsgService): JsonResponse
    {
        $validated = $request->validate([
            'webhook_url' => ['required', 'url', 'max:500'],
            'webhook_message_received' => ['nullable', 'boolean'],
            'webhook_message_create' => ['nullable', 'boolean'],
            'webhook_message_ack' => ['nullable', 'boolean'],
            'webhook_message_download_media' => ['nullable', 'boolean'],
            'sendDelay' => ['nullable', 'integer', 'min:1', 'max:60'],
            'sendDelayMax' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        if (! $ultraMsgService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('app.whatsapp_not_configured'),
            ], 400);
        }

        $settings = [
            'webhook_url' => $validated['webhook_url'],
            'webhook_message_received' => $validated['webhook_message_received'] ?? false ? 'true' : 'false',
            'webhook_message_create' => $validated['webhook_message_create'] ?? false ? 'true' : 'false',
            'webhook_message_ack' => $validated['webhook_message_ack'] ?? false ? 'true' : 'false',
            'webhook_message_download_media' => $validated['webhook_message_download_media'] ?? false ? 'true' : 'false',
            'sendDelay' => $validated['sendDelay'] ?? 1,
            'sendDelayMax' => $validated['sendDelayMax'] ?? 15,
        ];

        $success = $ultraMsgService->updateInstanceSettings($settings);

        if (! $success) {
            return response()->json([
                'success' => false,
                'message' => __('app.whatsapp_webhook_update_failed'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('app.whatsapp_webhook_update_success'),
        ]);
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
}

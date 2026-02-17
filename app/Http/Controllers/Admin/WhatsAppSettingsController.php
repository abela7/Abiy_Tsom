<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UltraMsgService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Manage WhatsApp/UltraMsg integration settings.
 */
class WhatsAppSettingsController extends Controller
{
    /**
     * Show WhatsApp settings page.
     */
    public function index(): View
    {
        $envPath = base_path('.env');
        $envExists = File::exists($envPath);

        $instanceId = '';
        $token = '';
        $baseUrl = 'https://api.ultramsg.com';

        if ($envExists) {
            $instanceId = config('services.ultramsg.instance_id') ?? '';
            $token = config('services.ultramsg.token') ?? '';
            $baseUrl = config('services.ultramsg.base_url') ?? 'https://api.ultramsg.com';
        }

        return view('admin.whatsapp.index', compact(
            'instanceId',
            'token',
            'baseUrl'
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
                ->route('admin.whatsapp.index')
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
            ->route('admin.whatsapp.index')
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

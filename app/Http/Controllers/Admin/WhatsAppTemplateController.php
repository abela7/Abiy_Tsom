<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manage WhatsApp message templates used by member reminder/confirmation flows.
 *
 * This controller edits existing translation keys only, so reminder delivery
 * and webhook behavior remain unchanged.
 */
class WhatsAppTemplateController extends Controller
{
    /**
     * @return array<int, array{key: string, group: string, title: string, placeholders: string}>
     */
    private function templateConfig(): array
    {
        return [
            [
                'key' => 'whatsapp_daily_reminder_header',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_header'),
                'placeholders' => ':baptism_name, :day',
            ],
            [
                'key' => 'whatsapp_daily_reminder_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_content'),
                'placeholders' => ':url',
            ],
            [
                'key' => 'whatsapp_confirmation_prompt_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_confirm_prompt'),
                'placeholders' => ':name',
            ],
            [
                'key' => 'whatsapp_invalid_reply_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_invalid_reply'),
                'placeholders' => ':name',
            ],
            [
                'key' => 'whatsapp_confirmation_activated_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_confirmed_notice'),
                'placeholders' => __('app.whatsapp_template_none'),
            ],
            [
                'key' => 'whatsapp_confirmation_go_back_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_go_back'),
                'placeholders' => ':url, :telegram_url',
            ],
            [
                'key' => 'whatsapp_confirmation_rejected_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_rejected_notice'),
                'placeholders' => __('app.whatsapp_template_none'),
            ],
        ];
    }

    /**
     * Show WhatsApp template editor.
     */
    public function index(): View
    {
        $config = $this->templateConfig();
        $keys = array_map(static fn (array $item): string => $item['key'], $config);

        $enDb = Translation::query()
            ->where('locale', 'en')
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        $amDb = Translation::query()
            ->where('locale', 'am')
            ->whereIn('key', $keys)
            ->get()
            ->keyBy('key');

        $enFile = [];
        $enPath = base_path('lang/en/app.php');
        if (is_file($enPath)) {
            $loaded = require $enPath;
            if (is_array($loaded)) {
                $enFile = $loaded;
            }
        }

        $amFile = [];
        $amPath = base_path('lang/am/app.php');
        if (is_file($amPath)) {
            $loaded = require $amPath;
            if (is_array($loaded)) {
                $amFile = $loaded;
            }
        }

        $templates = array_map(function (array $item) use ($enDb, $amDb, $enFile, $amFile): array {
            $key = $item['key'];

            return [
                ...$item,
                'en' => (string) ($enDb->get($key)?->value ?? ($enFile[$key] ?? '')),
                'am' => (string) ($amDb->get($key)?->value ?? ($amFile[$key] ?? '')),
            ];
        }, $config);

        return view('admin.whatsapp.template', compact('templates'));
    }

    /**
     * Persist WhatsApp template updates.
     */
    public function update(Request $request): RedirectResponse
    {
        $config = $this->templateConfig();
        $rules = [];

        foreach ($config as $item) {
            $key = $item['key'];
            $rules["templates.{$key}.en"] = ['required', 'string'];
            $rules["templates.{$key}.am"] = ['nullable', 'string'];
        }

        /** @var array<string, array{en: string, am?: string}> $validatedTemplates */
        $validatedTemplates = $request->validate([
            'templates' => ['required', 'array'],
            ...$rules,
        ])['templates'];

        foreach ($config as $item) {
            $key = $item['key'];
            $group = $item['group'];
            $enValue = trim((string) ($validatedTemplates[$key]['en'] ?? ''));
            $amValue = trim((string) ($validatedTemplates[$key]['am'] ?? ''));

            Translation::updateOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => 'en'],
                ['value' => $enValue]
            );

            Translation::updateOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => 'am'],
                ['value' => $amValue]
            );
        }

        Translation::clearCache();

        return redirect()
            ->route('admin.whatsapp.template')
            ->with('success', __('app.whatsapp_template_saved'));
    }
}


<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\Translation;
use App\Services\TelegramAuthService;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Carbon\CarbonImmutable;
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
     * @return array<int, array{key: string, group: string, title: string, placeholder_keys: list<string>}>
     */
    private function templateConfig(): array
    {
        return [
            [
                'key' => 'whatsapp_daily_reminder_header',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_header'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_daily_reminder_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_content'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_confirmation_prompt_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_confirm_prompt'),
                'placeholder_keys' => WhatsAppTemplateService::CONFIRMATION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_invalid_reply_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_invalid_reply'),
                'placeholder_keys' => WhatsAppTemplateService::CONFIRMATION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_confirmation_activated_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_confirmed_notice'),
                'placeholder_keys' => WhatsAppTemplateService::CONFIRMATION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_confirmation_go_back_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_go_back'),
                'placeholder_keys' => WhatsAppTemplateService::CONFIRMATION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_confirmation_rejected_message',
                'group' => 'wizard',
                'title' => __('app.whatsapp_template_rejected_notice'),
                'placeholder_keys' => WhatsAppTemplateService::CONFIRMATION_PLACEHOLDERS,
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
        $testMembers = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->orderBy('baptism_name')
            ->orderBy('id')
            ->get(['id', 'baptism_name', 'whatsapp_phone', 'whatsapp_language', 'whatsapp_confirmation_status']);

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

        return view('admin.whatsapp.template', compact('templates', 'testMembers'));
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

    /**
     * Send today's saved daily reminder template to a selected member.
     */
    public function sendTest(
        Request $request,
        UltraMsgService $ultraMsg,
        TelegramAuthService $telegramAuthService,
        WhatsAppTemplateService $whatsAppTemplateService
    ): RedirectResponse {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
        ]);

        $member = Member::query()->findOrFail((int) $validated['member_id']);

        if (! $member->whatsapp_phone) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput(['template_test_member_id' => $member->id])
                ->with('error', __('app.whatsapp_template_test_missing_phone'));
        }

        if (! $ultraMsg->isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput(['template_test_member_id' => $member->id])
                ->with('error', __('app.whatsapp_not_configured'));
        }

        $season = LentSeason::active();
        if (! $season) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput(['template_test_member_id' => $member->id])
                ->with('error', __('app.no_active_season'));
        }

        $today = CarbonImmutable::now('Europe/London')->toDateString();
        $dailyContent = DailyContent::query()
            ->where('lent_season_id', $season->id)
            ->whereDate('date', $today)
            ->where('is_published', true)
            ->first();

        if (! $dailyContent) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput(['template_test_member_id' => $member->id])
                ->with('error', __('app.timetable_no_content_today'));
        }

        $code = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_MEMBER_ACCESS,
            route('member.day', ['daily' => $dailyContent], false)
        );

        $dayUrl = route('share.day', [
            'daily' => $dailyContent,
            'code' => $code,
        ]);

        $message = $whatsAppTemplateService
            ->renderDailyReminder($member, $dailyContent, $this->ensureHttpsUrl($dayUrl))['message'];

        if (! $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput(['template_test_member_id' => $member->id])
                ->with('error', __('app.whatsapp_test_failed'));
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->withInput(['template_test_member_id' => $member->id])
            ->with('success', __('app.whatsapp_template_test_sent', [
                'name' => (string) ($member->baptism_name ?: $member->whatsapp_phone),
            ]));
    }

    /**
     * Ensure reminder links are sent as full HTTPS URLs
     * on non-local environments for best WhatsApp clickability.
     */
    private function ensureHttpsUrl(string $url): string
    {
        if (app()->environment('local')) {
            return $url;
        }

        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}

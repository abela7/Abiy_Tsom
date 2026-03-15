<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkWhatsAppMessageJob;
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
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_SECTION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_daily_reminder_yearly_block',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_yearly_block'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_SECTION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_daily_reminder_monthly_block',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_monthly_block'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_SECTION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_daily_reminder_footer',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_footer'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_SECTION_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_daily_reminder_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_daily_content'),
                'placeholder_keys' => WhatsAppTemplateService::DAILY_REMINDER_FINAL_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_bulk_message_header',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_bulk_header'),
                'placeholder_keys' => WhatsAppTemplateService::BULK_MESSAGE_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_bulk_message_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_bulk_content'),
                'placeholder_keys' => WhatsAppTemplateService::BULK_MESSAGE_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_bulk_message_final',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_bulk_final'),
                'placeholder_keys' => WhatsAppTemplateService::BULK_MESSAGE_PLACEHOLDERS,
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
        $activeMembers = Member::query()
            ->activeConfirmedWhatsApp()
            ->orderBy('baptism_name')
            ->orderBy('id')
            ->get(['id', 'baptism_name', 'whatsapp_phone', 'whatsapp_language']);

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

        return view('admin.whatsapp.template', compact('templates', 'testMembers', 'activeMembers'));
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
            'test_locale' => ['nullable', 'string', 'in:member,en,am'],
        ]);

        $member = Member::query()->findOrFail((int) $validated['member_id']);
        $testLocale = (string) ($validated['test_locale'] ?? 'member');
        $localeOverride = $testLocale === 'member' ? null : $testLocale;

        if (! $member->whatsapp_phone) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                ])
                ->with('error', __('app.whatsapp_template_test_missing_phone'));
        }

        if (! $ultraMsg->isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                ])
                ->with('error', __('app.whatsapp_not_configured'));
        }

        $season = LentSeason::active();
        if (! $season) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                ])
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
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                ])
                ->with('error', __('app.timetable_no_content_today'));
        }

        $code = $telegramAuthService->createCode(
            $member,
            TelegramAuthService::PURPOSE_SHARE_DAY_ACCESS,
            $dailyContent->memberDayUrl(false)
        );

        $dayUrl = route('share.day', [
            'daily' => $dailyContent,
            'code' => $code,
        ]);

        $message = $whatsAppTemplateService
            ->renderDailyReminder($member, $dailyContent, $this->ensureHttpsUrl($dayUrl), $localeOverride)['message'];

        if (! $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                ])
                ->with('error', __('app.whatsapp_test_failed'));
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->withInput([
                'template_test_member_id' => $member->id,
                'template_test_locale' => $testLocale,
            ])
            ->with('success', __('app.whatsapp_template_test_sent', [
                'name' => (string) ($member->baptism_name ?: $member->whatsapp_phone),
            ]));
    }

    /**
     * Queue a custom bulk WhatsApp message for active confirmed members.
     */
    public function sendBulk(Request $request, UltraMsgService $ultraMsg): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_mode' => ['required', 'string', 'in:all_active,selected_active'],
            'selected_member_ids' => ['nullable', 'array'],
            'selected_member_ids.*' => ['integer', 'exists:members,id'],
            'bulk_header' => ['required', 'string'],
            'bulk_content' => ['required', 'string'],
            'bulk_link' => ['nullable', 'url'],
        ]);

        $header = trim((string) $validated['bulk_header']);
        $content = trim((string) $validated['bulk_content']);
        $recipientMode = (string) $validated['recipient_mode'];
        $selectedIds = collect($validated['selected_member_ids'] ?? [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();
        $link = trim((string) ($validated['bulk_link'] ?? ''));
        $link = $link !== '' ? $this->ensureHttpsUrl($link) : null;

        if (! $ultraMsg->isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput()
                ->with('error', __('app.whatsapp_not_configured'));
        }

        $recipients = Member::query()
            ->activeConfirmedWhatsApp()
            ->when(
                $recipientMode === 'selected_active',
                fn ($query) => $query->whereIn('id', $selectedIds->all())
            )
            ->orderBy('id')
            ->get(['id']);

        if ($recipientMode === 'selected_active' && $selectedIds->isEmpty()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput()
                ->withErrors([
                    'selected_member_ids' => __('app.whatsapp_bulk_message_members_required'),
                ]);
        }

        if ($recipientMode === 'selected_active' && $recipients->count() !== $selectedIds->count()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput()
                ->withErrors([
                    'selected_member_ids' => __('app.whatsapp_bulk_message_members_invalid'),
                ]);
        }

        if ($recipients->isEmpty()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput()
                ->with('error', __('app.whatsapp_bulk_message_no_recipients'));
        }

        foreach ($recipients as $member) {
            SendBulkWhatsAppMessageJob::dispatch($member->id, $header, $content, $link);
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->with('success', __('app.whatsapp_bulk_message_queued', [
                'count' => $recipients->count(),
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

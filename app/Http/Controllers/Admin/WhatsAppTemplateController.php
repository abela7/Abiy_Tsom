<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkWhatsAppMessageJob;
use App\Models\DailyContent;
use App\Models\HimamatDay;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\Translation;
use App\Services\HimamatWhatsAppTemplateService;
use App\Services\TelegramAuthService;
use App\Services\UltraMsgService;
use App\Services\WhatsAppTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Manage WhatsApp message templates used by member reminder/confirmation flows.
 *
 * This controller edits existing translation keys only, so reminder delivery
 * and webhook behavior remain unchanged.
 */
class WhatsAppTemplateController extends Controller
{
    private const BULK_MESSAGE_KEY = 'whatsapp_bulk_message_custom_body';

    /**
     * @return array<int, array{key: string, title: string}>
     */
    private function testableTemplateConfig(): array
    {
        return [
            [
                'key' => 'whatsapp_daily_reminder_content',
                'title' => __('app.whatsapp_template_daily_content'),
            ],
            [
                'key' => 'whatsapp_himamat_intro_content',
                'title' => __('app.whatsapp_template_himamat_intro'),
            ],
            [
                'key' => 'whatsapp_himamat_third_content',
                'title' => __('app.whatsapp_template_himamat_third'),
            ],
            [
                'key' => 'whatsapp_himamat_sixth_content',
                'title' => __('app.whatsapp_template_himamat_sixth'),
            ],
            [
                'key' => 'whatsapp_himamat_ninth_content',
                'title' => __('app.whatsapp_template_himamat_ninth'),
            ],
            [
                'key' => 'whatsapp_himamat_eleventh_content',
                'title' => __('app.whatsapp_template_himamat_eleventh'),
            ],
        ];
    }

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
                'key' => 'whatsapp_himamat_intro_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_himamat_intro'),
                'placeholder_keys' => WhatsAppTemplateService::HIMAMAT_INTRO_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_himamat_third_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_himamat_third'),
                'placeholder_keys' => WhatsAppTemplateService::HIMAMAT_SLOT_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_himamat_sixth_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_himamat_sixth'),
                'placeholder_keys' => WhatsAppTemplateService::HIMAMAT_SLOT_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_himamat_ninth_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_himamat_ninth'),
                'placeholder_keys' => WhatsAppTemplateService::HIMAMAT_SLOT_PLACEHOLDERS,
            ],
            [
                'key' => 'whatsapp_himamat_eleventh_content',
                'group' => 'whatsapp_member',
                'title' => __('app.whatsapp_template_himamat_eleventh'),
                'placeholder_keys' => WhatsAppTemplateService::HIMAMAT_SLOT_PLACEHOLDERS,
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
    public function index(UltraMsgService $ultraMsg): View
    {
        $config = $this->templateConfig();
        $testableTemplates = $this->testableTemplateConfig();
        $keys = array_map(static fn (array $item): string => $item['key'], $config);
        $testMembers = Member::query()
            ->whereNotNull('whatsapp_phone')
            ->where('whatsapp_phone', '!=', '')
            ->where('whatsapp_phone', 'LIKE', '+44%')
            ->where('whatsapp_confirmation_status', 'confirmed')
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

        $bulkMessages = [
            'en' => (string) (Translation::query()
                ->where('group', 'whatsapp_member')
                ->where('key', self::BULK_MESSAGE_KEY)
                ->where('locale', 'en')
                ->value('value') ?? ($enFile[self::BULK_MESSAGE_KEY] ?? '')),
            'am' => (string) (Translation::query()
                ->where('group', 'whatsapp_member')
                ->where('key', self::BULK_MESSAGE_KEY)
                ->where('locale', 'am')
                ->value('value') ?? ($amFile[self::BULK_MESSAGE_KEY] ?? '')),
        ];

        $queueConnection = (string) config('queue.default', 'sync');
        $bulkDeliveryStatus = [
            'queue_connection' => $queueConnection,
            'runs_inline' => $queueConnection === 'sync',
            'requires_worker' => $queueConnection !== 'sync',
            'ultramsg_configured' => $ultraMsg->isConfigured(),
            'pending_jobs' => $this->queueCount('jobs', SendBulkWhatsAppMessageJob::QUEUE_NAME),
            'failed_jobs' => $this->queueCount('failed_jobs', SendBulkWhatsAppMessageJob::QUEUE_NAME),
        ];

        return view('admin.whatsapp.template', compact(
            'templates',
            'testableTemplates',
            'testMembers',
            'activeMembers',
            'bulkMessages',
            'bulkDeliveryStatus'
        ));
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

    public function saveBulk(Request $request): RedirectResponse
    {
        [$englishMessage, $amharicMessage] = $this->validateBulkMessages($request);

        $this->persistBulkMessages($englishMessage, $amharicMessage);

        return redirect()
            ->route('admin.whatsapp.template')
            ->with('success', __('app.whatsapp_bulk_saved'));
    }

    /**
     * Send today's saved daily reminder template to a selected member.
     */
    public function sendTest(
        Request $request,
        UltraMsgService $ultraMsg,
        TelegramAuthService $telegramAuthService,
        WhatsAppTemplateService $whatsAppTemplateService,
        HimamatWhatsAppTemplateService $himamatWhatsAppTemplateService
    ): RedirectResponse {
        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'test_locale' => ['nullable', 'string', 'in:member,en,am'],
            'template_key' => [
                'required',
                'string',
                Rule::in(array_map(
                    static fn (array $item): string => $item['key'],
                    $this->testableTemplateConfig()
                )),
            ],
        ]);

        $member = Member::query()->findOrFail((int) $validated['member_id']);
        $testLocale = (string) ($validated['test_locale'] ?? 'member');
        $localeOverride = $testLocale === 'member' ? null : $testLocale;
        $templateKey = (string) $validated['template_key'];

        if (! $member->whatsapp_phone) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                    'template_test_key' => $templateKey,
                ])
                ->with('error', __('app.whatsapp_template_test_missing_phone'));
        }

        if (! $ultraMsg->isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                    'template_test_key' => $templateKey,
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
                    'template_test_key' => $templateKey,
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
                    'template_test_key' => $templateKey,
                ])
                ->with('error', __('app.timetable_no_content_today'));
        }

        $dayUrl = $this->ensureHttpsUrl($dailyContent->memberDayUrl($member->token));
        $message = $this->resolveTemplatePreviewMessage(
            $templateKey,
            $member,
            $dailyContent,
            $dayUrl,
            $localeOverride,
            $whatsAppTemplateService,
            $himamatWhatsAppTemplateService
        );

        if ($message === null) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                    'template_test_key' => $templateKey,
                ])
                ->with('error', __('app.whatsapp_template_himamat_test_missing_day'));
        }

        if ($this->contactIsInvalid($ultraMsg, (string) $member->whatsapp_phone)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                    'template_test_key' => $templateKey,
                ])
                ->with('error', __('app.whatsapp_test_invalid_recipient', [
                    'phone' => (string) $member->whatsapp_phone,
                ]));
        }

        if (! $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'template_test_member_id' => $member->id,
                    'template_test_locale' => $testLocale,
                    'template_test_key' => $templateKey,
                ])
                ->with('error', __('app.whatsapp_test_failed'));
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->withInput([
                'template_test_member_id' => $member->id,
                'template_test_locale' => $testLocale,
                'template_test_key' => $templateKey,
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
        ]);

        [$englishMessage, $amharicMessage] = $this->validateBulkMessages($request);
        $recipientMode = (string) $validated['recipient_mode'];
        $selectedIds = collect($validated['selected_member_ids'] ?? [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

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

        $this->persistBulkMessages($englishMessage, $amharicMessage);

        foreach ($recipients as $member) {
            SendBulkWhatsAppMessageJob::dispatch($member->id, $englishMessage, $amharicMessage);
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->with('success', __('app.whatsapp_bulk_message_queued', [
                'count' => $recipients->count(),
            ]));
    }

    public function sendBulkSample(
        Request $request,
        UltraMsgService $ultraMsg,
        WhatsAppTemplateService $whatsAppTemplateService
    ): RedirectResponse {
        [$englishMessage, $amharicMessage] = $this->validateBulkMessages($request);
        $sampleMemberId = $this->resolveBulkSampleMemberId($request);

        if ($sampleMemberId === null) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput()
                ->withErrors([
                    'bulk_sample_member_id' => __('app.whatsapp_bulk_sample_member_required'),
                ]);
        }

        /** @var Member|null $member */
        $member = Member::query()
            ->activeConfirmedWhatsApp()
            ->find($sampleMemberId);

        if (! $member || ! $member->whatsapp_phone) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'bulk_sample_member_id' => $sampleMemberId,
                ])
                ->with('error', __('app.whatsapp_bulk_sample_member_invalid'));
        }

        if (! $ultraMsg->isConfigured()) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'bulk_sample_member_id' => $member->id,
                ])
                ->with('error', __('app.whatsapp_not_configured'));
        }

        $this->persistBulkMessages($englishMessage, $amharicMessage);

        $message = $whatsAppTemplateService
            ->renderBulkMessage($member, $englishMessage, $amharicMessage)['message'];

        if ($this->contactIsInvalid($ultraMsg, (string) $member->whatsapp_phone)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'bulk_sample_member_id' => $member->id,
                ])
                ->with('error', __('app.whatsapp_test_invalid_recipient', [
                    'phone' => (string) $member->whatsapp_phone,
                ]));
        }

        if ($message === '' || ! $ultraMsg->sendTextMessage((string) $member->whatsapp_phone, $message)) {
            return redirect()
                ->route('admin.whatsapp.template')
                ->withInput([
                    'bulk_sample_member_id' => $member->id,
                ])
                ->with('error', __('app.whatsapp_test_failed'));
        }

        return redirect()
            ->route('admin.whatsapp.template')
            ->with('success', __('app.whatsapp_bulk_sample_sent', [
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

    /**
     * @return array{0: string, 1: string}
     */
    private function validateBulkMessages(Request $request): array
    {
        $validated = $request->validate([
            'bulk_message_en' => ['required', 'string'],
            'bulk_message_am' => ['required', 'string'],
        ]);

        return [
            trim((string) $validated['bulk_message_en']),
            trim((string) $validated['bulk_message_am']),
        ];
    }

    private function persistBulkMessages(string $englishMessage, string $amharicMessage): void
    {
        Translation::updateOrCreate(
            ['group' => 'whatsapp_member', 'key' => self::BULK_MESSAGE_KEY, 'locale' => 'en'],
            ['value' => $englishMessage]
        );

        Translation::updateOrCreate(
            ['group' => 'whatsapp_member', 'key' => self::BULK_MESSAGE_KEY, 'locale' => 'am'],
            ['value' => $amharicMessage]
        );

        Translation::clearCache();
    }

    private function resolveBulkSampleMemberId(Request $request): ?int
    {
        $recipientMode = (string) $request->input('recipient_mode', 'all_active');
        $selectedIds = collect($request->input('selected_member_ids', []))
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($recipientMode === 'selected_active' && $selectedIds->count() === 1) {
            return $selectedIds->first();
        }

        $explicitId = (int) $request->integer('bulk_sample_member_id');
        if ($explicitId > 0) {
            return $explicitId;
        }

        return null;
    }

    private function queueCount(string $table, string $queue): int
    {
        try {
            return (int) DB::table($table)
                ->where('queue', $queue)
                ->count();
        } catch (Throwable) {
            return 0;
        }
    }

    private function contactIsInvalid(UltraMsgService $ultraMsg, string $phone): bool
    {
        return ($ultraMsg->checkContact($phone)['status'] ?? 'unknown') === 'invalid';
    }

    private function resolveTemplatePreviewMessage(
        string $templateKey,
        Member $member,
        DailyContent $dailyContent,
        string $dayUrl,
        ?string $localeOverride,
        WhatsAppTemplateService $whatsAppTemplateService,
        HimamatWhatsAppTemplateService $himamatWhatsAppTemplateService
    ): ?string {
        if (! str_starts_with($templateKey, 'whatsapp_himamat_')) {
            return $whatsAppTemplateService
                ->renderDailyReminder($member, $dailyContent, $dayUrl, $localeOverride)['message'];
        }

        $himamatDay = $this->resolvePublishedHimamatDay($dailyContent);
        if (! $himamatDay) {
            return null;
        }

        if ($templateKey === 'whatsapp_himamat_intro_content') {
            return $whatsAppTemplateService
                ->renderHimamatIntroReminder($member, $dailyContent, $himamatDay, $dayUrl, $localeOverride)['message'];
        }

        $slot = $this->resolvePublishedHimamatSlotFromTemplateKey($himamatDay, $templateKey);
        if (! $slot) {
            return null;
        }

        return $himamatWhatsAppTemplateService
            ->renderReminder(
                $member,
                $himamatDay,
                $slot,
                $this->ensureHttpsUrl($dailyContent->memberDayUrl($member->token).'#himamat-slot-'.$slot->slot_key),
                $localeOverride
            )['message'];
    }

    private function resolvePublishedHimamatDay(DailyContent $dailyContent): ?HimamatDay
    {
        if ($dailyContent->day_number < 50 || $dailyContent->day_number > 55) {
            return null;
        }

        return HimamatDay::query()
            ->where('lent_season_id', $dailyContent->lent_season_id)
            ->whereDate('date', $dailyContent->date)
            ->where('is_published', true)
            ->with([
                'slots' => fn ($query) => $query
                    ->where('is_published', true)
                    ->orderBy('slot_order'),
            ])
            ->first();
    }

    private function resolvePublishedHimamatSlotFromTemplateKey(HimamatDay $himamatDay, string $templateKey): ?HimamatSlot
    {
        $slotKey = match ($templateKey) {
            'whatsapp_himamat_third_content' => 'third',
            'whatsapp_himamat_sixth_content' => 'sixth',
            'whatsapp_himamat_ninth_content' => 'ninth',
            'whatsapp_himamat_eleventh_content' => 'eleventh',
            default => null,
        };

        if ($slotKey === null) {
            return null;
        }

        return $himamatDay->slots
            ->where('slot_key', $slotKey)
            ->first();
    }
}

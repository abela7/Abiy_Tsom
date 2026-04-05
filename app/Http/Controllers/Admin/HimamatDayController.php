<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\HimamatSlot;
use App\Models\HimamatSlotResource;
use App\Models\LentSeason;
use App\Models\MemberHimamatInvitationDelivery;
use App\Models\MemberHimamatPreference;
use App\Services\HimamatScaffoldService;
use App\Services\HimamatSynaxariumService;
use App\Services\HimamatTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HimamatDayController extends Controller
{
    public function index(): View
    {
        $season = LentSeason::active();
        $days = $season
            ? HimamatDay::query()
                ->where('lent_season_id', $season->id)
                ->with(['slots', 'faqs'])
                ->orderBy('date')
                ->orderBy('sort_order')
                ->get()
            : collect();

        return view('admin.himamat.index', [
            'season' => $season,
            'days' => $days,
        ]);
    }

    public function tracking(Request $request): View
    {
        $season = LentSeason::active();
        $campaigns = MemberHimamatInvitationDelivery::query()
            ->select('campaign_key')
            ->distinct()
            ->orderByDesc('campaign_key')
            ->pluck('campaign_key');

        $selectedCampaign = trim((string) $request->query('campaign', ''));
        if ($selectedCampaign === '' && $campaigns->isNotEmpty()) {
            $selectedCampaign = (string) $campaigns->first();
        }

        $search = trim((string) $request->query('search', ''));

        $deliveriesQuery = MemberHimamatInvitationDelivery::query()
            ->with([
                'member',
                'member.himamatPreferences' => fn ($query) => $query
                    ->when($season, fn ($seasonQuery) => $seasonQuery->where('lent_season_id', $season->id)),
            ])
            ->where('channel', 'whatsapp')
            ->when($selectedCampaign !== '', fn ($query) => $query->where('campaign_key', $selectedCampaign))
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('baptism_name', 'like', '%'.$search.'%')
                        ->orWhere('whatsapp_phone', 'like', '%'.$search.'%');
                });
            })
            ->orderByDesc('delivered_at')
            ->orderByDesc('id');

        $summaryQuery = clone $deliveriesQuery;
        $totalSent = (clone $summaryQuery)->count();
        $totalClicked = (clone $summaryQuery)->whereNotNull('first_opened_at')->count();
        $totalNotClicked = max($totalSent - $totalClicked, 0);

        $preferencesQuery = MemberHimamatPreference::query()
            ->when($season, fn ($query) => $query->where('lent_season_id', $season->id))
            ->when($selectedCampaign !== '', function ($query) use ($selectedCampaign): void {
                $query->whereHas('member.himamatInvitationDeliveries', function ($deliveryQuery) use ($selectedCampaign): void {
                    $deliveryQuery->where('campaign_key', $selectedCampaign)
                        ->where('channel', 'whatsapp');
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('member', function ($memberQuery) use ($search): void {
                    $memberQuery->where('baptism_name', 'like', '%'.$search.'%')
                        ->orWhere('whatsapp_phone', 'like', '%'.$search.'%');
                });
            });

        $totalPreferencesRecorded = (clone $preferencesQuery)->count();

        $slotLabelKeys = [
            'intro' => 'app.himamat_slot_7am',
            'third' => 'app.himamat_slot_9am',
            'sixth' => 'app.himamat_slot_12pm',
            'ninth' => 'app.himamat_slot_3pm',
            'eleventh' => 'app.himamat_slot_5pm',
        ];

        $slotDefinitions = collect(config('himamat.slots', []))
            ->map(fn (array $slot): array => [
                'key' => (string) ($slot['key'] ?? ''),
                'label' => __($slotLabelKeys[(string) ($slot['key'] ?? '')] ?? 'app.himamat_title'),
            ])
            ->values();

        $deliveries = $deliveriesQuery->paginate(30)->withQueryString();

        return view('admin.himamat.tracking', [
            'season' => $season,
            'campaigns' => $campaigns,
            'selectedCampaign' => $selectedCampaign,
            'search' => $search,
            'slotDefinitions' => $slotDefinitions,
            'deliveries' => $deliveries,
            'totalSent' => $totalSent,
            'totalClicked' => $totalClicked,
            'totalNotClicked' => $totalNotClicked,
            'totalPreferencesRecorded' => $totalPreferencesRecorded,
        ]);
    }

    public function scaffold(HimamatScaffoldService $scaffold): RedirectResponse
    {
        $result = $scaffold->scaffoldActiveSeason(auth()->id());

        if (! $result['season']) {
            return redirect()->route('admin.himamat.index')
                ->with('error', __('app.no_active_season'));
        }

        return redirect()->route('admin.himamat.index')
            ->with('success', __('app.himamat_scaffold_success', [
                'days' => $result['created_days'],
                'slots' => $result['created_slots'],
            ]));
    }

    public function edit(string $day, HimamatSynaxariumService $synaxarium): View
    {
        $himamatDay = $this->resolveDay($day);
        $ethDateInfo = $synaxarium->resolveDateInfo($himamatDay, app()->getLocale());

        return view('admin.himamat.edit', [
            'day' => $himamatDay,
            'season' => $himamatDay->lentSeason,
            'ethDateInfo' => $ethDateInfo,
            'ethiopianMonthOptions' => $synaxarium->monthOptions(app()->getLocale()),
        ]);
    }

    public function preview(
        string $day,
        HimamatTimelineService $timeline,
        HimamatSynaxariumService $synaxarium
    ): View {
        $himamatDay = $this->resolveDay($day);
        $timelineData = $timeline->buildTimeline(
            $himamatDay->load([
                'slots' => fn ($query) => $query
                    ->with(['resources' => fn ($resourceQuery) => $resourceQuery->orderBy('sort_order')])
                    ->orderBy('slot_order'),
                'faqs' => fn ($query) => $query->orderBy('sort_order'),
            ]),
            (string) ($himamatDay->slots->first()?->slot_key ?? 'intro')
        );
        $ethDateInfo = $synaxarium->resolveDateInfo($himamatDay, app()->getLocale());

        return view('member.himamat.day', [
            'member' => null,
            'day' => $himamatDay,
            'timeline' => $timelineData,
            'ethDateInfo' => $ethDateInfo,
            'previousDay' => null,
            'nextDay' => null,
            'publicPreview' => true,
            'previewMode' => true,
            'backUrl' => route('admin.himamat.edit', ['day' => $himamatDay->getKey()]),
        ]);
    }

    public function update(Request $request, string $day): RedirectResponse
    {
        $himamatDay = $this->resolveDay($day);
        $saveMode = trim((string) $request->input('save_mode', 'exit'));
        $saveSection = trim((string) $request->input('save_section', ''));
        $slotIds = $himamatDay->slots()->orderBy('slot_order')->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $faqIds = $himamatDay->faqs()->pluck('id')->map(fn ($id): string => (string) $id)->all();

        $validated = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_am' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'day_reminder_time' => ['required', 'date_format:H:i'],
            'day_reminder_title_en' => ['nullable', 'string', 'max:255'],
            'day_reminder_title_am' => ['nullable', 'string', 'max:255'],
            'spiritual_meaning_en' => ['nullable', 'string'],
            'spiritual_meaning_am' => ['nullable', 'string'],
            'ritual_guide_intro_en' => ['nullable', 'string'],
            'ritual_guide_intro_am' => ['nullable', 'string'],
            'synaxarium_title_en' => ['nullable', 'string', 'max:255'],
            'synaxarium_title_am' => ['nullable', 'string', 'max:255'],
            'synaxarium_text_en' => ['nullable', 'string'],
            'synaxarium_text_am' => ['nullable', 'string'],
            'synaxarium_source' => ['required', 'string', Rule::in(['automatic', 'manual'])],
            'synaxarium_month' => ['nullable', 'integer', 'min:1', 'max:13', 'required_if:synaxarium_source,manual'],
            'synaxarium_day' => ['nullable', 'integer', 'min:1', 'max:30', 'required_if:synaxarium_source,manual'],
            'is_published' => ['nullable', 'boolean'],
            'faqs' => ['nullable', 'array'],
            'faqs.*.id' => ['nullable', 'integer'],
            'faqs.*.question_en' => ['nullable', 'string', 'max:255'],
            'faqs.*.question_am' => ['nullable', 'string', 'max:255'],
            'faqs.*.answer_en' => ['nullable', 'string'],
            'faqs.*.answer_am' => ['nullable', 'string'],
            'slots' => ['required', 'array', 'size:5'],
            'slots.*.id' => ['required', 'integer'],
            'slots.*.slot_header_en' => ['required', 'string', 'max:255'],
            'slots.*.slot_header_am' => ['nullable', 'string', 'max:255'],
            'slots.*.reminder_header_en' => ['nullable', 'string', 'max:255'],
            'slots.*.reminder_header_am' => ['nullable', 'string', 'max:255'],
            'slots.*.reading_reference_en' => ['nullable', 'string', 'max:255'],
            'slots.*.reading_reference_am' => ['nullable', 'string', 'max:255'],
            'slots.*.reading_text_en' => ['nullable', 'string'],
            'slots.*.reading_text_am' => ['nullable', 'string'],
            'slots.*.resources' => ['nullable', 'array'],
            'slots.*.resources.*.id' => ['nullable', 'integer'],
            'slots.*.resources.*.type' => ['nullable', 'string', Rule::in(HimamatSlotResource::allowedTypes())],
            'slots.*.resources.*.title_en' => ['nullable', 'string', 'max:255'],
            'slots.*.resources.*.title_am' => ['nullable', 'string', 'max:255'],
            'slots.*.resources.*.text_en' => ['nullable', 'string'],
            'slots.*.resources.*.text_am' => ['nullable', 'string'],
            'slots.*.resources.*.url' => ['nullable', 'url', 'max:1000'],
            'slots.*.resources.*.file_path' => ['nullable', 'string', 'max:500'],
            'slots.*.resources.*.upload' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:20480'],
            'slots.*.is_published' => ['nullable', 'boolean'],
        ]);

        $faqPayloads = $this->normalizeFaqPayloads($validated['faqs'] ?? [], $faqIds);
        $resolvedReminderTime = $validated['day_reminder_time'].':00';
        $resolvedReminderTitleEn = $this->resolveReminderTitle(
            $validated['day_reminder_title_en'] ?? null,
            $validated['title_en']
        );
        $resolvedReminderTitleAm = $this->resolveReminderTitle(
            $validated['day_reminder_title_am'] ?? null,
            $validated['title_am'] ?? null
        );

        $this->validateTimelineOrdering($himamatDay, $resolvedReminderTime);

        DB::transaction(function () use (
            $faqPayloads,
            $himamatDay,
            $request,
            $resolvedReminderTime,
            $resolvedReminderTitleAm,
            $resolvedReminderTitleEn,
            $slotIds,
            $validated
        ): void {
            $himamatDay->update([
                'title_en' => $validated['title_en'],
                'title_am' => $validated['title_am'] ?: null,
                'date' => $validated['date'],
                'spiritual_meaning_en' => $validated['spiritual_meaning_en'] ?: null,
                'spiritual_meaning_am' => $validated['spiritual_meaning_am'] ?: null,
                'ritual_guide_intro_en' => $validated['ritual_guide_intro_en'] ?: null,
                'ritual_guide_intro_am' => $validated['ritual_guide_intro_am'] ?: null,
                'synaxarium_title_en' => $validated['synaxarium_title_en'] ?: null,
                'synaxarium_title_am' => $validated['synaxarium_title_am'] ?: null,
                'synaxarium_text_en' => $validated['synaxarium_text_en'] ?: null,
                'synaxarium_text_am' => $validated['synaxarium_text_am'] ?: null,
                'synaxarium_source' => $validated['synaxarium_source'],
                'synaxarium_month' => $validated['synaxarium_source'] === 'manual'
                    ? (int) $validated['synaxarium_month']
                    : null,
                'synaxarium_day' => $validated['synaxarium_source'] === 'manual'
                    ? (int) $validated['synaxarium_day']
                    : null,
                'is_published' => $request->boolean('is_published'),
                'updated_by_id' => auth()->id(),
            ]);

            $slots = $himamatDay->slots->keyBy('id');
            foreach ($validated['slots'] as $slotIndex => $slotInput) {
                $slotId = (string) $slotInput['id'];
                if (! in_array($slotId, $slotIds, true)) {
                    continue;
                }

                $slot = $slots->get((int) $slotInput['id']);
                if (! $slot) {
                    continue;
                }

                $slotAttributes = [
                    'slot_header_en' => $slotInput['slot_header_en'],
                    'slot_header_am' => $slotInput['slot_header_am'] ?: null,
                    'reminder_header_en' => $this->resolveReminderTitle(
                        $slotInput['reminder_header_en'] ?? null,
                        $slotInput['slot_header_en']
                    ),
                    'reminder_header_am' => $this->resolveReminderTitle(
                        $slotInput['reminder_header_am'] ?? null,
                        $slotInput['slot_header_am'] ?? null
                    ),
                    'reading_reference_en' => $slotInput['reading_reference_en'] ?: null,
                    'reading_reference_am' => $slotInput['reading_reference_am'] ?: null,
                    'reading_text_en' => $slotInput['reading_text_en'] ?: null,
                    'reading_text_am' => $slotInput['reading_text_am'] ?: null,
                    'is_published' => (bool) ($slotInput['is_published'] ?? false),
                    'updated_by_id' => auth()->id(),
                ];

                if ($slot->slot_key === 'intro') {
                    $slotAttributes['scheduled_time_london'] = $resolvedReminderTime;
                    $slotAttributes['reminder_header_en'] = $resolvedReminderTitleEn;
                    $slotAttributes['reminder_header_am'] = $resolvedReminderTitleAm;
                }

                $slot->update($slotAttributes);

                $resourcePayloads = $this->normalizeResourcePayloads(
                    $request,
                    $slot,
                    $slotIndex,
                    $slotInput['resources'] ?? []
                );
                $this->syncResources($slot, $resourcePayloads);
            }

            $this->syncFaqs($himamatDay, $faqPayloads);
        });

        if ($saveMode === 'stay') {
            $target = route('admin.himamat.edit', ['day' => $himamatDay->getKey()]);
            if ($saveSection !== '') {
                $target .= '#'.$saveSection;
            }

            return redirect($target)
                ->with('success', __('app.himamat_draft_saved'));
        }

        return redirect()->route('admin.himamat.index')
            ->with('success', __('app.himamat_updated'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawFaqs
     * @param  list<string>  $faqIds
     * @return list<array<string, mixed>>
     */
    private function normalizeFaqPayloads(array $rawFaqs, array $faqIds): array
    {
        $normalized = [];

        foreach ($rawFaqs as $index => $faqInput) {
            $questionEn = trim((string) ($faqInput['question_en'] ?? ''));
            $questionAm = trim((string) ($faqInput['question_am'] ?? ''));
            $answerEn = trim((string) ($faqInput['answer_en'] ?? ''));
            $answerAm = trim((string) ($faqInput['answer_am'] ?? ''));
            $faqId = isset($faqInput['id']) && $faqInput['id'] !== ''
                ? (string) (int) $faqInput['id']
                : null;

            $hasAnyContent = $questionEn !== '' || $questionAm !== '' || $answerEn !== '' || $answerAm !== '';
            if (! $hasAnyContent) {
                continue;
            }

            if ($questionEn === '' || $answerEn === '') {
                throw ValidationException::withMessages([
                    "faqs.$index.question_en" => __('app.himamat_faq_requires_english'),
                ]);
            }

            if ($faqId !== null && ! in_array($faqId, $faqIds, true)) {
                throw ValidationException::withMessages([
                    "faqs.$index.id" => __('app.himamat_faq_invalid'),
                ]);
            }

            $normalized[] = [
                'id' => $faqId !== null ? (int) $faqId : null,
                'question_en' => $questionEn,
                'question_am' => $questionAm !== '' ? $questionAm : null,
                'answer_en' => $answerEn,
                'answer_am' => $answerAm !== '' ? $answerAm : null,
            ];
        }

        return $normalized;
    }

    private function resolveReminderTitle(?string $preferredValue, ?string $fallbackValue): ?string
    {
        $preferred = trim((string) $preferredValue);
        if ($preferred !== '') {
            return $preferred;
        }

        $fallback = trim((string) $fallbackValue);

        return $fallback !== '' ? $fallback : null;
    }

    private function validateTimelineOrdering(HimamatDay $day, string $introTime): void
    {
        $slots = $day->slots()->orderBy('slot_order')->get();
        if ($slots->count() < 2) {
            return;
        }

        $orderedTimes = $slots->map(function ($slot) use ($introTime): array {
            return [
                'slot_key' => (string) $slot->slot_key,
                'time' => $slot->slot_key === 'intro'
                    ? $introTime
                    : (string) $slot->scheduled_time_london,
            ];
        })->values();

        for ($index = 0; $index < $orderedTimes->count() - 1; $index++) {
            $current = $orderedTimes[$index];
            $next = $orderedTimes[$index + 1];

            if ($current['time'] >= $next['time']) {
                throw ValidationException::withMessages([
                    'day_reminder_time' => __('app.himamat_day_reminder_time_order_error'),
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rawResources
     * @return list<array<string, mixed>>
     */
    private function normalizeResourcePayloads(
        Request $request,
        HimamatSlot $slot,
        int $slotIndex,
        array $rawResources
    ): array {
        $resourceIds = $slot->resources->pluck('id')->map(fn ($id): string => (string) $id)->all();
        $normalized = [];

        foreach ($rawResources as $resourceIndex => $resourceInput) {
            $type = trim((string) ($resourceInput['type'] ?? HimamatSlotResource::TYPE_WEBSITE));
            if (! in_array($type, HimamatSlotResource::allowedTypes(), true)) {
                $type = HimamatSlotResource::TYPE_WEBSITE;
            }

            $resourceId = isset($resourceInput['id']) && $resourceInput['id'] !== ''
                ? (string) (int) $resourceInput['id']
                : null;
            $titleEn = trim((string) ($resourceInput['title_en'] ?? ''));
            $titleAm = trim((string) ($resourceInput['title_am'] ?? ''));
            $textEn = trim((string) ($resourceInput['text_en'] ?? ''));
            $textAm = trim((string) ($resourceInput['text_am'] ?? ''));
            $url = trim((string) ($resourceInput['url'] ?? ''));
            $filePath = trim((string) ($resourceInput['file_path'] ?? ''));
            $upload = $request->file("slots.$slotIndex.resources.$resourceIndex.upload");

            $hasAnyContent = $titleEn !== ''
                || $titleAm !== ''
                || $textEn !== ''
                || $textAm !== ''
                || $url !== ''
                || $filePath !== ''
                || $upload !== null;

            if (! $hasAnyContent) {
                continue;
            }

            if ($resourceId !== null && ! in_array($resourceId, $resourceIds, true)) {
                throw ValidationException::withMessages([
                    "slots.$slotIndex.resources.$resourceIndex.id" => __('app.himamat_resource_invalid'),
                ]);
            }

            if ($type === HimamatSlotResource::TYPE_TEXT && $textEn === '' && $textAm === '') {
                throw ValidationException::withMessages([
                    "slots.$slotIndex.resources.$resourceIndex.text_en" => __('app.himamat_resource_requires_text'),
                ]);
            }

            if ($type !== HimamatSlotResource::TYPE_TEXT && $url === '' && $filePath === '' && $upload === null) {
                throw ValidationException::withMessages([
                    "slots.$slotIndex.resources.$resourceIndex.url" => __('app.himamat_resource_requires_media'),
                ]);
            }

            if (in_array($type, [HimamatSlotResource::TYPE_VIDEO, HimamatSlotResource::TYPE_WEBSITE], true) && $url === '') {
                throw ValidationException::withMessages([
                    "slots.$slotIndex.resources.$resourceIndex.url" => __('app.himamat_resource_requires_url'),
                ]);
            }

            if ($upload !== null) {
                $this->validateUploadedResource($type, $upload->getClientOriginalExtension(), $slotIndex, $resourceIndex);
            }

            $normalized[] = [
                'id' => $resourceId !== null ? (int) $resourceId : null,
                'type' => $type,
                'title_en' => $titleEn !== '' ? $titleEn : null,
                'title_am' => $titleAm !== '' ? $titleAm : null,
                'text_en' => $type === HimamatSlotResource::TYPE_TEXT && $textEn !== '' ? $textEn : null,
                'text_am' => $type === HimamatSlotResource::TYPE_TEXT && $textAm !== '' ? $textAm : null,
                'url' => $url !== '' ? $url : null,
                'file_path' => $filePath !== '' ? $filePath : null,
                'upload' => $upload,
            ];
        }

        return $normalized;
    }

    private function validateUploadedResource(
        string $type,
        string $extension,
        int $slotIndex,
        int $resourceIndex
    ): void {
        $normalizedExtension = strtolower(trim($extension));
        $allowedPhotoExtensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($type, [HimamatSlotResource::TYPE_VIDEO, HimamatSlotResource::TYPE_WEBSITE, HimamatSlotResource::TYPE_TEXT], true)) {
            throw ValidationException::withMessages([
                "slots.$slotIndex.resources.$resourceIndex.upload" => __('app.himamat_resource_upload_not_supported'),
            ]);
        }

        if ($type === HimamatSlotResource::TYPE_PHOTO && ! in_array($normalizedExtension, $allowedPhotoExtensions, true)) {
            throw ValidationException::withMessages([
                "slots.$slotIndex.resources.$resourceIndex.upload" => __('app.himamat_photo_upload_invalid'),
            ]);
        }

        if ($type === HimamatSlotResource::TYPE_PDF && $normalizedExtension !== 'pdf') {
            throw ValidationException::withMessages([
                "slots.$slotIndex.resources.$resourceIndex.upload" => __('app.himamat_pdf_upload_invalid'),
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $resourcePayloads
     */
    private function syncResources(HimamatSlot $slot, array $resourcePayloads): void
    {
        $existingResources = $slot->resources()->get()->keyBy('id');
        $keepIds = [];

        foreach ($resourcePayloads as $index => $resourcePayload) {
            $resourceId = $resourcePayload['id'];
            /** @var HimamatSlotResource|null $existingResource */
            $existingResource = $resourceId !== null ? $existingResources->get($resourceId) : null;
            $filePath = $resourcePayload['file_path'];

            if ($resourcePayload['upload'] !== null) {
                $filePath = $this->storeResourceUpload($resourcePayload['upload'], (string) $resourcePayload['type']);
                if ($existingResource?->file_path && $existingResource->file_path !== $filePath) {
                    Storage::disk('public')->delete($existingResource->file_path);
                }
            }

            if (in_array($resourcePayload['type'], [HimamatSlotResource::TYPE_VIDEO, HimamatSlotResource::TYPE_WEBSITE, HimamatSlotResource::TYPE_TEXT], true)) {
                if ($existingResource?->file_path && $resourcePayload['upload'] === null) {
                    Storage::disk('public')->delete($existingResource->file_path);
                }

                $filePath = null;
            }

            if ($resourcePayload['type'] === HimamatSlotResource::TYPE_TEXT) {
                $resourcePayload['url'] = null;
            }

            $attributes = [
                'type' => $resourcePayload['type'],
                'sort_order' => $index + 1,
                'title_en' => $resourcePayload['title_en'],
                'title_am' => $resourcePayload['title_am'],
                'text_en' => $resourcePayload['text_en'],
                'text_am' => $resourcePayload['text_am'],
                'url' => $resourcePayload['url'],
                'file_path' => $filePath,
                'updated_by_id' => auth()->id(),
            ];

            if ($existingResource) {
                $existingResource->update($attributes);
                $keepIds[] = $existingResource->id;

                continue;
            }

            $resource = $slot->resources()->create($attributes + [
                'created_by_id' => auth()->id(),
            ]);
            $keepIds[] = $resource->id;
        }

        foreach ($existingResources as $resource) {
            if (in_array($resource->id, $keepIds, true)) {
                continue;
            }

            if ($resource->file_path) {
                Storage::disk('public')->delete($resource->file_path);
            }

            $resource->delete();
        }
    }

    private function storeResourceUpload(\Illuminate\Http\UploadedFile $upload, string $type): string
    {
        $directory = $type === HimamatSlotResource::TYPE_PHOTO
            ? 'himamat-resources/photos'
            : 'himamat-resources/files';

        return $upload->store($directory, 'public');
    }

    /**
     * @param  list<array<string, mixed>>  $faqPayloads
     */
    private function syncFaqs(HimamatDay $day, array $faqPayloads): void
    {
        $existingFaqs = $day->faqs()->get()->keyBy('id');
        $keepIds = [];

        foreach ($faqPayloads as $index => $faqPayload) {
            $attributes = [
                'sort_order' => $index + 1,
                'question_en' => $faqPayload['question_en'],
                'question_am' => $faqPayload['question_am'],
                'answer_en' => $faqPayload['answer_en'],
                'answer_am' => $faqPayload['answer_am'],
                'updated_by_id' => auth()->id(),
            ];

            $faqId = $faqPayload['id'];
            if ($faqId !== null && $existingFaqs->has($faqId)) {
                /** @var HimamatDayFaq $faq */
                $faq = $existingFaqs->get($faqId);
                $faq->update($attributes);
                $keepIds[] = $faq->id;

                continue;
            }

            $faq = $day->faqs()->create($attributes + [
                'created_by_id' => auth()->id(),
            ]);
            $keepIds[] = $faq->id;
        }

        $deleteQuery = $day->faqs();
        if ($keepIds !== []) {
            $deleteQuery->whereNotIn('id', $keepIds);
        }

        $deleteQuery->delete();
    }

    private function resolveDay(string $day): HimamatDay
    {
        return HimamatDay::query()
            ->with([
                'lentSeason',
                'slots' => fn ($query) => $query->with(['resources' => fn ($resourceQuery) => $resourceQuery->orderBy('sort_order')])->orderBy('slot_order'),
                'faqs' => fn ($query) => $query->orderBy('sort_order'),
            ])
            ->findOrFail((int) $day);
    }
}

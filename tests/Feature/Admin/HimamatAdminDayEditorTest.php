<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\HimamatSlot;
use App\Models\HimamatSlotResource;
use App\Models\LentSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HimamatAdminDayEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_global_himamat_fields_and_faq_rows(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = $this->createDayWithSlots($season, 'holy-monday');

        $existingFaq = HimamatDayFaq::create([
            'himamat_day_id' => $day->id,
            'sort_order' => 1,
            'question_en' => 'Old question',
            'answer_en' => 'Old answer',
        ]);

        $slots = $day->slots()->orderBy('slot_order')->get()->values();

        $payload = [
            'title_en' => 'Holy Monday - Fig Tree',
            'title_am' => '',
            'date' => '2026-04-06',
            'day_reminder_time' => '06:45',
            'day_reminder_title_en' => 'Holy Monday Reminder',
            'day_reminder_title_am' => '',
            'spiritual_meaning_en' => 'A full day meaning for Holy Monday.',
            'spiritual_meaning_am' => '',
            'ritual_guide_intro_en' => 'A ritual introduction for the whole day.',
            'ritual_guide_intro_am' => '',
            'synaxarium_title_en' => 'Synaxarium of Holy Monday',
            'synaxarium_title_am' => '',
            'synaxarium_text_en' => 'The faithful remember the saints and teachings appointed for this day.',
            'synaxarium_text_am' => '',
            'synaxarium_source' => 'manual',
            'synaxarium_month' => 8,
            'synaxarium_day' => 27,
            'is_published' => '1',
            'faqs' => [
                [
                    'id' => (string) $existingFaq->id,
                    'question_en' => 'Why do we say Kiryalaisson 12 times?',
                    'question_am' => '',
                    'answer_en' => 'To remember mercy during the Holy Week hours.',
                    'answer_am' => '',
                ],
                [
                    'id' => '',
                    'question_en' => 'How should we offer the third-hour prostrations?',
                    'question_am' => '',
                    'answer_en' => 'With silence, prayer, and reverence.',
                    'answer_am' => '',
                ],
            ],
            'slots' => $slots->map(fn (HimamatSlot $slot) => [
                'id' => $slot->id,
                'slot_header_en' => $slot->slot_header_en,
                'slot_header_am' => '',
                'reminder_header_en' => $slot->reminder_header_en,
                'reminder_header_am' => '',
                'reading_reference_en' => 'Reference for '.$slot->slot_key,
                'reading_reference_am' => '',
                'reading_text_en' => 'Reading text for '.$slot->slot_key,
                'reading_text_am' => '',
                'resources' => $slot->slot_key === 'third'
                    ? [
                        [
                            'id' => '',
                            'type' => HimamatSlotResource::TYPE_VIDEO,
                            'title_en' => 'Temple teaching video',
                            'title_am' => '',
                            'url' => 'https://www.youtube.com/watch?v=holy-third-hour',
                            'file_path' => '',
                        ],
                        [
                            'id' => '',
                            'type' => HimamatSlotResource::TYPE_PHOTO,
                            'title_en' => 'Temple photo',
                            'title_am' => '',
                            'url' => '',
                            'file_path' => '',
                            'upload' => UploadedFile::fake()->create('temple-photo.png', 200, 'image/png'),
                        ],
                        [
                            'id' => '',
                            'type' => HimamatSlotResource::TYPE_TEXT,
                            'title_en' => 'Bowing prayer',
                            'title_am' => '',
                            'text_en' => 'Offer the prayer text for this hour with reverence.',
                            'text_am' => '',
                            'url' => '',
                            'file_path' => '',
                        ],
                    ]
                    : [],
                'is_published' => '1',
            ])->all(),
        ];

        $response = $this->actingAs($admin)->post(route('admin.himamat.update', ['day' => $day->id]), array_merge($payload, [
            '_method' => 'PUT',
        ]));

        $response->assertRedirect(route('admin.himamat.index'));

        $this->assertDatabaseHas('himamat_days', [
            'id' => $day->id,
            'title_en' => 'Holy Monday - Fig Tree',
            'spiritual_meaning_en' => 'A full day meaning for Holy Monday.',
            'ritual_guide_intro_en' => 'A ritual introduction for the whole day.',
            'synaxarium_title_en' => 'Synaxarium of Holy Monday',
            'synaxarium_text_en' => 'The faithful remember the saints and teachings appointed for this day.',
            'synaxarium_source' => 'manual',
            'synaxarium_month' => 8,
            'synaxarium_day' => 27,
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('himamat_slots', [
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'scheduled_time_london' => '06:45:00',
            'reminder_header_en' => 'Holy Monday Reminder',
        ]);

        $this->assertDatabaseHas('himamat_day_faqs', [
            'himamat_day_id' => $day->id,
            'question_en' => 'Why do we say Kiryalaisson 12 times?',
            'answer_en' => 'To remember mercy during the Holy Week hours.',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('himamat_day_faqs', [
            'himamat_day_id' => $day->id,
            'question_en' => 'How should we offer the third-hour prostrations?',
            'answer_en' => 'With silence, prayer, and reverence.',
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('himamat_slot_resources', [
            'himamat_slot_id' => $slots[1]->id,
            'type' => HimamatSlotResource::TYPE_VIDEO,
            'title_en' => 'Temple teaching video',
            'url' => 'https://www.youtube.com/watch?v=holy-third-hour',
        ]);

        $this->assertDatabaseHas('himamat_slot_resources', [
            'himamat_slot_id' => $slots[1]->id,
            'type' => HimamatSlotResource::TYPE_TEXT,
            'title_en' => 'Bowing prayer',
            'text_en' => 'Offer the prayer text for this hour with reverence.',
        ]);

        $photoResource = HimamatSlotResource::query()
            ->where('himamat_slot_id', $slots[1]->id)
            ->where('type', HimamatSlotResource::TYPE_PHOTO)
            ->first();

        $this->assertNotNull($photoResource);
        $this->assertNotNull($photoResource->file_path);
        Storage::disk('public')->assertExists($photoResource->file_path);

        $this->assertSame(2, HimamatDayFaq::where('himamat_day_id', $day->id)->count());
    }

    public function test_blank_day_reminder_title_falls_back_to_day_title(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = $this->createDayWithSlots($season, 'holy-tuesday');
        $slots = $day->slots()->orderBy('slot_order')->get()->values();

        $payload = $this->basePayload($slots, [
            'title_en' => 'Holy Tuesday - Watchfulness',
            'day_reminder_time' => '06:50',
            'day_reminder_title_en' => '',
        ]);

        $this->actingAs($admin)
            ->put(route('admin.himamat.update', ['day' => $day->id]), $payload)
            ->assertRedirect(route('admin.himamat.index'));

        $this->assertDatabaseHas('himamat_slots', [
            'himamat_day_id' => $day->id,
            'slot_key' => 'intro',
            'scheduled_time_london' => '06:50:00',
            'reminder_header_en' => 'Holy Tuesday - Watchfulness',
        ]);
    }

    public function test_day_reminder_time_must_stay_before_later_slots(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $season = LentSeason::create([
            'year' => 2026,
            'start_date' => '2026-04-06',
            'end_date' => '2026-04-12',
            'total_days' => 55,
            'is_active' => true,
        ]);

        $day = $this->createDayWithSlots($season, 'holy-wednesday');
        $slots = $day->slots()->orderBy('slot_order')->get()->values();

        $payload = $this->basePayload($slots, [
            'day_reminder_time' => '09:00',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.himamat.edit', ['day' => $day->id]))
            ->put(route('admin.himamat.update', ['day' => $day->id]), $payload)
            ->assertRedirect(route('admin.himamat.edit', ['day' => $day->id]))
            ->assertSessionHasErrors('day_reminder_time');
    }

    private function createDayWithSlots(LentSeason $season, string $slug): HimamatDay
    {
        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => $slug,
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        foreach (config('himamat.slots', []) as $slotDefinition) {
            HimamatSlot::create([
                'himamat_day_id' => $day->id,
                'slot_key' => $slotDefinition['key'],
                'slot_order' => $slotDefinition['order'],
                'scheduled_time_london' => $slotDefinition['time'],
                'slot_header_en' => $slotDefinition['default_slot_header_en'],
                'reminder_header_en' => $slotDefinition['default_reminder_header_en'],
                'is_published' => false,
            ]);
        }

        return $day;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, HimamatSlot>  $slots
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload($slots, array $overrides = []): array
    {
        return array_replace_recursive([
            'title_en' => 'Holy Monday',
            'title_am' => '',
            'date' => '2026-04-06',
            'day_reminder_time' => '07:00',
            'day_reminder_title_en' => 'Holy Monday Reminder',
            'day_reminder_title_am' => '',
            'spiritual_meaning_en' => 'A full day meaning for Holy Monday.',
            'spiritual_meaning_am' => '',
            'ritual_guide_intro_en' => 'A ritual introduction for the whole day.',
            'ritual_guide_intro_am' => '',
            'synaxarium_title_en' => 'Synaxarium of Holy Monday',
            'synaxarium_title_am' => '',
            'synaxarium_text_en' => 'The faithful remember the saints and teachings appointed for this day.',
            'synaxarium_text_am' => '',
            'synaxarium_source' => 'manual',
            'synaxarium_month' => 8,
            'synaxarium_day' => 27,
            'is_published' => '1',
            'faqs' => [],
            'slots' => $slots->map(fn (HimamatSlot $slot) => [
                'id' => $slot->id,
                'slot_header_en' => $slot->slot_header_en,
                'slot_header_am' => '',
                'reminder_header_en' => $slot->reminder_header_en,
                'reminder_header_am' => '',
                'reading_reference_en' => 'Reference for '.$slot->slot_key,
                'reading_reference_am' => '',
                'reading_text_en' => 'Reading text for '.$slot->slot_key,
                'reading_text_am' => '',
                'resources' => [],
                'is_published' => '1',
            ])->all(),
        ], $overrides);
    }
}

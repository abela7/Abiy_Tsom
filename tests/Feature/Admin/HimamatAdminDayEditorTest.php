<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\HimamatDay;
use App\Models\HimamatDayFaq;
use App\Models\HimamatSlot;
use App\Models\LentSeason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HimamatAdminDayEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_global_himamat_fields_and_faq_rows(): void
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

        $day = HimamatDay::create([
            'lent_season_id' => $season->id,
            'slug' => 'holy-monday',
            'sort_order' => 1,
            'date' => '2026-04-06',
            'title_en' => 'Holy Monday',
            'is_published' => false,
        ]);

        $existingFaq = HimamatDayFaq::create([
            'himamat_day_id' => $day->id,
            'sort_order' => 1,
            'question_en' => 'Old question',
            'answer_en' => 'Old answer',
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

        $slots = $day->slots()->orderBy('slot_order')->get()->values();

        $payload = [
            'title_en' => 'Holy Monday - Fig Tree',
            'title_am' => '',
            'date' => '2026-04-06',
            'spiritual_meaning_en' => 'A full day meaning for Holy Monday.',
            'spiritual_meaning_am' => '',
            'ritual_guide_intro_en' => 'A ritual introduction for the whole day.',
            'ritual_guide_intro_am' => '',
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
                'spiritual_significance_en' => 'Meaning for '.$slot->slot_key,
                'spiritual_significance_am' => '',
                'reading_reference_en' => 'Reference for '.$slot->slot_key,
                'reading_reference_am' => '',
                'reading_text_en' => 'Reading text for '.$slot->slot_key,
                'reading_text_am' => '',
                'prostration_count' => 12,
                'prostration_guidance_en' => 'Offer bows with reverence.',
                'prostration_guidance_am' => '',
                'short_prayer_en' => 'Lord, remember us.',
                'short_prayer_am' => '',
                'is_published' => '1',
            ])->all(),
        ];

        $response = $this->actingAs($admin)->put(route('admin.himamat.update', ['day' => $day->id]), $payload);

        $response->assertRedirect(route('admin.himamat.index'));

        $this->assertDatabaseHas('himamat_days', [
            'id' => $day->id,
            'title_en' => 'Holy Monday - Fig Tree',
            'spiritual_meaning_en' => 'A full day meaning for Holy Monday.',
            'ritual_guide_intro_en' => 'A ritual introduction for the whole day.',
            'synaxarium_source' => 'manual',
            'synaxarium_month' => 8,
            'synaxarium_day' => 27,
            'is_published' => 1,
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

        $this->assertSame(2, HimamatDayFaq::where('himamat_day_id', $day->id)->count());
    }
}

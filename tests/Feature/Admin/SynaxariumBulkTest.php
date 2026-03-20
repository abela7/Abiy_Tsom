<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\EthiopianSynaxariumAnnual;
use App\Models\EthiopianSynaxariumMonthly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SynaxariumBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_bulk_create_monthly_and_annual_celebrations(): void
    {
        $editor = User::create([
            'name' => 'Editor',
            'username' => 'editor_syn',
            'email' => 'editor_syn@example.com',
            'password' => bcrypt('password'),
            'role' => 'editor',
            'is_super_admin' => false,
        ]);

        $payload = [
            'rows' => [
                [
                    'kind' => 'monthly',
                    'day' => 5,
                    'month' => 1,
                    'celebration_en' => 'Saint Alpha',
                    'celebration_am' => '',
                    'description_en' => '',
                    'description_am' => '',
                    'is_main' => '1',
                    'sort_order' => 0,
                ],
                [
                    'kind' => 'monthly',
                    'day' => 5,
                    'month' => 1,
                    'celebration_en' => 'Saint Beta',
                    'celebration_am' => '',
                    'description_en' => '',
                    'description_am' => '',
                    'sort_order' => 1,
                ],
                [
                    'kind' => 'annual',
                    'month' => 3,
                    'day' => 12,
                    'celebration_en' => 'Annual Feast Gamma',
                    'celebration_am' => 'ጋማ',
                    'description_en' => 'Note',
                    'description_am' => '',
                    'is_main' => '1',
                    'sort_order' => 0,
                ],
            ],
        ];

        $this->actingAs($editor)
            ->post(route('admin.synaxarium.bulk.store'), $payload)
            ->assertRedirect(route('admin.synaxarium.index'))
            ->assertSessionHas('success');

        $this->assertSame(2, EthiopianSynaxariumMonthly::query()->where('day', 5)->count());
        $this->assertTrue(
            EthiopianSynaxariumMonthly::query()->where('day', 5)->where('celebration_en', 'Saint Alpha')->value('is_main')
        );
        $this->assertFalse(
            EthiopianSynaxariumMonthly::query()->where('day', 5)->where('celebration_en', 'Saint Beta')->value('is_main')
        );

        $annual = EthiopianSynaxariumAnnual::query()->where('month', 3)->where('day', 12)->first();
        $this->assertNotNull($annual);
        $this->assertSame('Annual Feast Gamma', $annual->celebration_en);
        $this->assertSame('ጋማ', $annual->celebration_am);
        $this->assertSame('Note', $annual->description_en);
        $this->assertTrue($annual->is_main);
    }

    public function test_bulk_store_requires_at_least_one_celebration_en(): void
    {
        $editor = User::create([
            'name' => 'Editor',
            'username' => 'editor_syn2',
            'email' => 'editor_syn2@example.com',
            'password' => bcrypt('password'),
            'role' => 'editor',
            'is_super_admin' => false,
        ]);

        $rows = [];
        for ($i = 0; $i < 3; $i++) {
            $rows[] = [
                'kind' => 'monthly',
                'day' => 1,
                'month' => 1,
                'celebration_en' => '',
                'celebration_am' => '',
                'description_en' => '',
                'description_am' => '',
                'sort_order' => 0,
            ];
        }

        $this->actingAs($editor)
            ->post(route('admin.synaxarium.bulk.store'), ['rows' => $rows])
            ->assertSessionHasErrors('rows');
    }

    public function test_bulk_store_validates_annual_month(): void
    {
        $editor = User::create([
            'name' => 'Editor',
            'username' => 'editor_syn3',
            'email' => 'editor_syn3@example.com',
            'password' => bcrypt('password'),
            'role' => 'editor',
            'is_super_admin' => false,
        ]);

        $this->actingAs($editor)
            ->post(route('admin.synaxarium.bulk.store'), [
                'rows' => [
                    [
                        'kind' => 'annual',
                        'day' => 1,
                        'month' => 0,
                        'celebration_en' => 'Bad month',
                        'celebration_am' => '',
                        'description_en' => '',
                        'description_am' => '',
                        'sort_order' => 0,
                    ],
                ],
            ])
            ->assertSessionHasErrors('rows.0.month');
    }
}

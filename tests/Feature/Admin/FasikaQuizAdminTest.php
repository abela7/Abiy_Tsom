<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FasikaQuizAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_open_fasika_quiz_admin_under_greetings_path(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
        ]);

        $this->actingAs($editor)
            ->get('/admin/fasika-greetings/quiz')
            ->assertOk()
            ->assertSee(__('app.fasika_quiz_admin_page_title'), false);
    }

    public function test_old_fasika_quiz_url_redirects_to_nested_path(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
        ]);

        $this->actingAs($editor)
            ->get('/admin/fasika-quiz')
            ->assertRedirect('/admin/fasika-greetings/quiz');
    }
}

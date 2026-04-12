<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FasikaQuizQuestion;
use App\Models\FasikaQuizSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FasikaQuizController extends Controller
{
    // ─── Questions ────────────────────────────────────────────────────────────

    public function index(): View
    {
        $questions = FasikaQuizQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $stats = [
            'total' => FasikaQuizQuestion::query()->count(),
            'active' => FasikaQuizQuestion::query()->where('is_active', true)->count(),
            'submissions' => FasikaQuizSubmission::query()->count(),
            'avg_score' => (int) round((float) FasikaQuizSubmission::query()->avg('score')),
        ];

        return view('admin.fasika-quiz.index', compact('questions', 'stats'));
    }

    public function create(): View
    {
        return view('admin.fasika-quiz.form', ['question' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateQuestion($request);

        FasikaQuizQuestion::create($validated);

        return redirect()
            ->route('admin.fasika-quiz.index')
            ->with('success', __('app.fasika_quiz_admin_store_success'));
    }

    public function edit(FasikaQuizQuestion $question): View
    {
        return view('admin.fasika-quiz.form', compact('question'));
    }

    public function update(Request $request, FasikaQuizQuestion $question): RedirectResponse
    {
        $validated = $this->validateQuestion($request);

        $question->update($validated);

        return redirect()
            ->route('admin.fasika-quiz.index')
            ->with('success', __('app.fasika_quiz_admin_update_success'));
    }

    public function toggle(FasikaQuizQuestion $question): RedirectResponse
    {
        $question->update(['is_active' => ! $question->is_active]);

        return redirect()
            ->route('admin.fasika-quiz.index')
            ->with(
                'success',
                $question->is_active
                    ? __('app.fasika_quiz_admin_toggle_active_success')
                    : __('app.fasika_quiz_admin_toggle_inactive_success')
            );
    }

    public function destroy(FasikaQuizQuestion $question): RedirectResponse
    {
        $question->delete();

        return redirect()
            ->route('admin.fasika-quiz.index')
            ->with('success', __('app.fasika_quiz_admin_destroy_success'));
    }

    // ─── Submissions ──────────────────────────────────────────────────────────

    public function submissions(): View
    {
        $submissions = FasikaQuizSubmission::query()
            ->latest()
            ->paginate(30);

        $stats = [
            'total' => FasikaQuizSubmission::query()->count(),
            'named' => FasikaQuizSubmission::query()->whereNotNull('participant_name')->count(),
            'avg_score' => (int) round((float) FasikaQuizSubmission::query()->avg('score')),
            'perfect' => FasikaQuizSubmission::query()->whereColumn('score', 'total_possible')->count(),
        ];

        return view('admin.fasika-quiz.submissions', compact('submissions', 'stats'));
    }

    public function destroySubmission(FasikaQuizSubmission $submission): RedirectResponse
    {
        $submission->delete();

        return redirect()
            ->route('admin.fasika-quiz.submissions')
            ->with('success', __('app.fasika_quiz_admin_submission_destroy_success'));
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:1000'],
            'option_a' => ['required', 'string', 'max:500'],
            'option_b' => ['required', 'string', 'max:500'],
            'option_c' => ['required', 'string', 'max:500'],
            'option_d' => ['required', 'string', 'max:500'],
            'correct_option' => ['required', 'in:a,b,c,d'],
            'difficulty' => ['required', 'in:easy,medium,hard'],
            'points' => ['required', 'integer', 'min:1', 'max:10'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}

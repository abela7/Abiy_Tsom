<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FasikaQuizQuestion;
use App\Models\FasikaQuizSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicFasikaQuizController extends Controller
{
    /**
     * Return shuffled active questions (no correct answers exposed).
     */
    public function questions(): JsonResponse
    {
        $questions = FasikaQuizQuestion::query()
            ->where('is_active', true)
            ->inRandomOrder()
            ->get();

        if ($questions->isEmpty()) {
            return response()->json(['error' => 'No questions available'], 404);
        }

        // Store quiz session: token → [question_id => {correct_option, points}]
        $token = Str::random(32);
        $sessionData = [];

        foreach ($questions as $q) {
            $sessionData[$q->id] = [
                'correct_option' => $q->correct_option,
                'points'         => $q->points,
                'difficulty'     => $q->difficulty,
            ];
        }

        session(["quiz_{$token}" => $sessionData]);

        return response()->json([
            'token'          => $token,
            'total_possible' => $questions->sum('points'),
            'questions'      => $questions->map(fn ($q) => $q->toPublicArray())->values(),
        ]);
    }

    /**
     * Validate a single answer server-side; return correctness + correct option.
     */
    public function answer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'           => ['required', 'string', 'size:32'],
            'question_id'     => ['required', 'integer'],
            'selected_option' => ['required', 'string', 'in:a,b,c,d'],
        ]);

        $sessionData = session("quiz_{$validated['token']}", []);
        $qId = (int) $validated['question_id'];

        if (! isset($sessionData[$qId])) {
            return response()->json(['error' => 'Invalid question or session expired'], 422);
        }

        $correct    = $sessionData[$qId]['correct_option'];
        $isCorrect  = $validated['selected_option'] === $correct;
        $pointsEarned = $isCorrect ? $sessionData[$qId]['points'] : 0;

        return response()->json([
            'is_correct'    => $isCorrect,
            'correct_option' => $correct,
            'points_earned' => $pointsEarned,
        ]);
    }

    /**
     * Complete the quiz: re-validate all answers, save submission, return results + leaderboard.
     */
    public function complete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token'                       => ['required', 'string', 'size:32'],
            'name'                        => ['nullable', 'string', 'max:120', 'regex:/.*\S.*/u'],
            'answers'                     => ['required', 'array', 'min:1'],
            'answers.*.question_id'       => ['required', 'integer'],
            'answers.*.selected_option'   => ['required', 'string', 'in:a,b,c,d'],
            'time_taken_seconds'          => ['nullable', 'integer', 'min:0', 'max:600'],
        ]);

        $sessionData = session("quiz_{$validated['token']}", []);

        // Re-validate every answer server-side
        $score          = 0;
        $totalPossible  = 0;
        $verifiedAnswers = [];

        foreach ($validated['answers'] as $answer) {
            $qId = (int) $answer['question_id'];

            if (! isset($sessionData[$qId])) {
                continue;
            }

            $correct      = $sessionData[$qId]['correct_option'];
            $points       = $sessionData[$qId]['points'];
            $isCorrect    = $answer['selected_option'] === $correct;
            $pointsEarned = $isCorrect ? $points : 0;

            $totalPossible  += $points;
            $score          += $pointsEarned;

            $verifiedAnswers[] = [
                'question_id'     => $qId,
                'selected_option' => $answer['selected_option'],
                'correct_option'  => $correct,
                'is_correct'      => $isCorrect,
                'points_earned'   => $pointsEarned,
            ];
        }

        $name = filled($validated['name'] ?? null)
            ? Str::of((string) $validated['name'])->squish()->value()
            : null;

        FasikaQuizSubmission::create([
            'participant_name'    => $name,
            'ip_address'          => $request->ip(),
            'user_agent'          => (string) $request->userAgent(),
            'score'               => $score,
            'total_possible'      => $totalPossible ?: 30,
            'answers'             => $verifiedAnswers,
            'time_taken_seconds'  => $validated['time_taken_seconds'] ?? null,
        ]);

        session()->forget("quiz_{$validated['token']}");

        // Top 10 leaderboard
        $leaderboard = FasikaQuizSubmission::query()
            ->whereNotNull('participant_name')
            ->where('participant_name', '!=', '')
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->limit(10)
            ->get(['participant_name', 'score', 'total_possible', 'time_taken_seconds']);

        $correctCount = count(array_filter($verifiedAnswers, fn ($a) => $a['is_correct']));

        return response()->json([
            'score'           => $score,
            'total_possible'  => $totalPossible ?: 30,
            'percentage'      => $totalPossible > 0 ? (int) round(($score / $totalPossible) * 100) : 0,
            'correct_count'   => $correctCount,
            'total_questions' => count($verifiedAnswers),
            'leaderboard'     => $leaderboard,
        ]);
    }
}

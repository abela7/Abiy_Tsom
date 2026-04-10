<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SurveyController extends Controller
{
    private const FEATURE_OPTIONS = [
        'daily_content',
        'himamat',
        'reminders',
        'events',
        'all_equal',
    ];

    public function show(string $token): View|\Illuminate\Http\RedirectResponse
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        if ($feedback->status === 'submitted') {
            return redirect()->route('survey.thanks', ['token' => $token]);
        }

        return view('member.survey.show', [
            'feedback' => $feedback,
            'member'   => $feedback->member,
        ]);
    }

    public function save(Request $request, string $token): JsonResponse
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        if ($feedback->status === 'submitted') {
            return response()->json(['ok' => false, 'reason' => 'already_submitted']);
        }

        $validated = $request->validate([
            'q1_overall_rating'          => ['nullable', 'integer', 'min:1', 'max:5'],
            'q2_most_used_feature'       => ['nullable', 'string', 'in:' . implode(',', self::FEATURE_OPTIONS)],
            'q3_himamat_rating'          => ['nullable', 'integer', 'min:1', 'max:5'],
            'q4_whatsapp_reminder_useful' => ['nullable', 'boolean'],
            'q5_suggestion'              => ['nullable', 'string', 'max:2000'],
            'q6_opt_in_future_fasts'     => ['nullable', 'boolean'],
        ]);

        // Remove null values so we don't overwrite previously saved answers
        $payload = array_filter($validated, fn ($v) => $v !== null);

        $feedback->forceFill(array_merge($payload, [
            'status'        => 'draft',
            'last_saved_at' => now(),
        ]))->save();

        return response()->json(['ok' => true]);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        if ($feedback->status === 'submitted') {
            return response()->json(['ok' => true, 'redirect' => route('survey.thanks', ['token' => $token])]);
        }

        $request->validate([
            'q1_overall_rating'          => ['required', 'integer', 'min:1', 'max:5'],
            'q2_most_used_feature'       => ['required', 'string', 'in:' . implode(',', self::FEATURE_OPTIONS)],
            'q3_himamat_rating'          => ['required', 'integer', 'min:1', 'max:5'],
            'q4_whatsapp_reminder_useful' => ['required', 'boolean'],
            'q5_suggestion'              => ['nullable', 'string', 'max:2000'],
            'q6_opt_in_future_fasts'     => ['required', 'boolean'],
        ]);

        $feedback->forceFill([
            'q1_overall_rating'          => $request->integer('q1_overall_rating'),
            'q2_most_used_feature'       => $request->input('q2_most_used_feature'),
            'q3_himamat_rating'          => $request->integer('q3_himamat_rating'),
            'q4_whatsapp_reminder_useful' => $request->boolean('q4_whatsapp_reminder_useful'),
            'q5_suggestion'              => $request->input('q5_suggestion'),
            'q6_opt_in_future_fasts'     => $request->boolean('q6_opt_in_future_fasts'),
            'status'                     => 'submitted',
            'submitted_at'               => now(),
            'last_saved_at'              => now(),
            'ip_address'                 => $request->ip(),
            'user_agent'                 => $request->userAgent(),
        ])->save();

        return response()->json([
            'ok'       => true,
            'redirect' => route('survey.thanks', ['token' => $token]),
        ]);
    }

    public function thanks(string $token): View
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        return view('member.survey.thankyou', [
            'feedback' => $feedback,
            'member'   => $feedback->member,
        ]);
    }
}

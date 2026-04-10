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
    public function show(string $token): View|\Illuminate\Http\RedirectResponse
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        if ($feedback->status === 'submitted') {
            return redirect()->route('survey.thanks', ['token' => $token]);
        }

        return view('member.survey.show', [
            'feedback'      => $feedback,
            'member'        => $feedback->member,
            'currentMember' => $feedback->member,
        ]);
    }

    public function save(Request $request, string $token): JsonResponse
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        if ($feedback->status === 'submitted') {
            return response()->json(['ok' => false, 'reason' => 'already_submitted']);
        }

        $validated = $request->validate([
            'q1_usefulness'           => ['nullable', 'string', 'in:' . implode(',', MemberFeedback::USEFULNESS_OPTIONS)],
            'q2_improvement_feedback' => ['nullable', 'string', 'max:2000'],
            'q3_continuity_preference'=> ['nullable', 'string', 'in:' . implode(',', MemberFeedback::CONTINUITY_OPTIONS)],
            'q4_overall_rating'       => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        // Keep empty strings (valid for optional text) but drop true nulls
        // so we never overwrite a previously saved answer with null
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

        $q1 = $request->input('q1_usefulness');

        // Early-exit path: member never saw the app
        if ($q1 === 'not_seen') {
            $request->validate([
                'q1_usefulness' => ['required', 'string', 'in:' . implode(',', MemberFeedback::USEFULNESS_OPTIONS)],
            ]);

            $feedback->forceFill([
                'q1_usefulness' => 'not_seen',
                'status'        => 'submitted',
                'submitted_at'  => now(),
                'last_saved_at' => now(),
                'ip_address'    => $request->ip(),
                'user_agent'    => $request->userAgent(),
            ])->save();

            return response()->json(['ok' => true, 'redirect' => route('survey.thanks', ['token' => $token])]);
        }

        // Full submission: Q1 + Q4 required; Q2 or Q3 optional per branch
        $request->validate([
            'q1_usefulness'           => ['required', 'string', 'in:' . implode(',', MemberFeedback::USEFULNESS_OPTIONS)],
            'q2_improvement_feedback' => ['nullable', 'string', 'max:2000'],
            'q3_continuity_preference'=> ['nullable', 'string', 'in:' . implode(',', MemberFeedback::CONTINUITY_OPTIONS)],
            'q4_overall_rating'       => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        $feedback->forceFill([
            'q1_usefulness'           => $request->input('q1_usefulness'),
            'q2_improvement_feedback' => $request->input('q2_improvement_feedback'),
            'q3_continuity_preference'=> $request->input('q3_continuity_preference'),
            'q4_overall_rating'       => $request->integer('q4_overall_rating'),
            'status'                  => 'submitted',
            'submitted_at'            => now(),
            'last_saved_at'           => now(),
            'ip_address'              => $request->ip(),
            'user_agent'              => $request->userAgent(),
        ])->save();

        return response()->json(['ok' => true, 'redirect' => route('survey.thanks', ['token' => $token])]);
    }

    public function thanks(string $token): View
    {
        $feedback = MemberFeedback::where('token', $token)->firstOrFail();

        return view('member.survey.thankyou', [
            'feedback'      => $feedback,
            'member'        => $feedback->member,
            'currentMember' => $feedback->member,
        ]);
    }
}

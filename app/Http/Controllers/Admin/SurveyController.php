<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberFeedback;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(): View
    {
        $total     = MemberFeedback::count();
        $submitted = MemberFeedback::where('status', 'submitted')->count();
        $draft     = MemberFeedback::where('status', 'draft')->count();
        $pending   = MemberFeedback::where('status', 'pending')->count();
        $rate      = $total > 0 ? round($submitted / $total * 100) : 0;

        $avgRating = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q4_overall_rating')
            ->avg('q4_overall_rating');

        $usefulnessBreakdown = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q1_usefulness')
            ->selectRaw('q1_usefulness, COUNT(*) as count')
            ->groupBy('q1_usefulness')
            ->pluck('count', 'q1_usefulness');

        $continuityBreakdown = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q3_continuity_preference')
            ->selectRaw('q3_continuity_preference, COUNT(*) as count')
            ->groupBy('q3_continuity_preference')
            ->pluck('count', 'q3_continuity_preference');

        $ratingDistribution = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q4_overall_rating')
            ->selectRaw('q4_overall_rating as rating, COUNT(*) as count')
            ->groupBy('q4_overall_rating')
            ->orderBy('q4_overall_rating')
            ->pluck('count', 'rating');

        $wantAllSeasons = $continuityBreakdown['all_seasons'] ?? 0;

        $responses = MemberFeedback::with('member')
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('admin.survey.index', compact(
            'total', 'submitted', 'draft', 'pending', 'rate',
            'avgRating', 'usefulnessBreakdown', 'continuityBreakdown',
            'ratingDistribution', 'wantAllSeasons', 'responses'
        ));
    }

    public function export(): Response
    {
        $rows = MemberFeedback::with('member')
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fasika-survey-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Member ID', 'Name', 'Phone',
                'Q1 Usefulness', 'Q2 Improvement Feedback',
                'Q3 Future Season Preference', 'Q4 Overall Rating (1-5)',
                'Submitted At',
            ]);

            foreach ($rows as $fb) {
                fputcsv($handle, [
                    $fb->member?->id,
                    $fb->member?->baptism_name,
                    $fb->member?->whatsapp_phone,
                    $fb->q1_usefulness,
                    $fb->q2_improvement_feedback,
                    $fb->q3_continuity_preference,
                    $fb->q4_overall_rating,
                    $fb->submitted_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

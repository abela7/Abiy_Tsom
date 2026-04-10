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
            ->whereNotNull('q1_overall_rating')
            ->avg('q1_overall_rating');

        $featureBreakdown = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q2_most_used_feature')
            ->selectRaw('q2_most_used_feature, COUNT(*) as count')
            ->groupBy('q2_most_used_feature')
            ->orderByDesc('count')
            ->pluck('count', 'q2_most_used_feature');

        $ratingDistribution = MemberFeedback::where('status', 'submitted')
            ->whereNotNull('q1_overall_rating')
            ->selectRaw('q1_overall_rating as rating, COUNT(*) as count')
            ->groupBy('q1_overall_rating')
            ->orderBy('q1_overall_rating')
            ->pluck('count', 'rating');

        $optInCount = MemberFeedback::where('status', 'submitted')
            ->where('q6_opt_in_future_fasts', true)
            ->count();

        $responses = MemberFeedback::with('member')
            ->where('status', 'submitted')
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return view('admin.survey.index', compact(
            'total', 'submitted', 'draft', 'pending', 'rate',
            'avgRating', 'featureBreakdown', 'ratingDistribution',
            'optInCount', 'responses'
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
            'Content-Disposition' => 'attachment; filename="fasika-feedback-' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Member ID', 'Name', 'Phone',
                'Q1 Overall (1-5)', 'Q2 Most Used Feature',
                'Q3 Himamat Rating (1-5)', 'Q4 WhatsApp Useful',
                'Q5 Suggestion', 'Q6 Opt-in Future Fasts',
                'Submitted At',
            ]);

            foreach ($rows as $fb) {
                fputcsv($handle, [
                    $fb->member?->id,
                    $fb->member?->baptism_name,
                    $fb->member?->whatsapp_phone,
                    $fb->q1_overall_rating,
                    $fb->q2_most_used_feature,
                    $fb->q3_himamat_rating,
                    $fb->q4_whatsapp_reminder_useful === null ? '' : ($fb->q4_whatsapp_reminder_useful ? 'Yes' : 'No'),
                    $fb->q5_suggestion,
                    $fb->q6_opt_in_future_fasts === null ? '' : ($fb->q6_opt_in_future_fasts ? 'Yes' : 'No'),
                    $fb->submitted_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}

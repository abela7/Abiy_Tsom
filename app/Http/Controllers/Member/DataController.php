<?php

declare(strict_types=1);

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomActivity;
use App\Models\MemberCustomChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Member data export, import, and reset.
 */
class DataController extends Controller
{
    /**
     * Export member data as JSON file.
     */
    public function export(Request $request): StreamedResponse
    {
        /** @var Member $member */
        $member = $request->attributes->get('member');

        $season = LentSeason::active();
        $dailyByDay = $season
            ? DailyContent::where('lent_season_id', $season->id)
                ->get()
                ->keyBy('day_number')
            : collect();

        $activities = $season
            ? Activity::where('lent_season_id', $season->id)->get()->keyBy('id')
            : collect();

        $checklists = MemberChecklist::where('member_id', $member->id)
            ->with(['dailyContent', 'activity'])
            ->get();

        $customChecklists = MemberCustomChecklist::where('member_id', $member->id)
            ->with(['dailyContent', 'customActivity'])
            ->get();

        $customActivities = $member->customActivities()->orderBy('sort_order')->get();

        $exportChecklists = [];
        foreach ($checklists as $c) {
            if ($c->dailyContent && $c->activity) {
                $exportChecklists[] = [
                    'day_number' => $c->dailyContent->day_number,
                    'activity_name' => $c->activity->name,
                    'activity_type' => 'admin',
                    'completed' => $c->completed,
                ];
            }
        }

        foreach ($customChecklists as $c) {
            if ($c->dailyContent && $c->customActivity) {
                $exportChecklists[] = [
                    'day_number' => $c->dailyContent->day_number,
                    'activity_name' => $c->customActivity->name,
                    'activity_type' => 'custom',
                    'completed' => $c->completed,
                ];
            }
        }

        $export = [
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'member' => [
                'baptism_name' => $member->baptism_name,
                'locale' => $member->locale,
                'theme' => $member->theme,
            ],
            'custom_activities' => $customActivities->map(fn ($a) => [
                'name' => $a->name,
                'sort_order' => $a->sort_order,
            ])->toArray(),
            'checklists' => $exportChecklists,
        ];

        $filename = 'abiy-tsom-data-' . now()->format('Y-m-d-His') . '.json';

        return response()->streamDownload(
            function () use ($export): void {
                echo json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            },
            $filename,
            [
                'Content-Type' => 'application/json',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    /**
     * Import member data from JSON file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'data' => ['required', 'string'],
        ]);

        /** @var Member $member */
        $member = $request->attributes->get('member');

        $data = json_decode($request->input('data'), true);
        if (! is_array($data) || empty($data['version'])) {
            return response()->json([
                'success' => false,
                'message' => __('app.import_invalid_format'),
            ], 422);
        }

        $season = LentSeason::active();
        if (! $season) {
            return response()->json([
                'success' => false,
                'message' => __('app.import_no_season'),
            ], 422);
        }

        $dailyByDay = DailyContent::where('lent_season_id', $season->id)
            ->get()
            ->keyBy('day_number');

        $activitiesByName = Activity::where('lent_season_id', $season->id)
            ->get()
            ->keyBy('name');

        DB::beginTransaction();
        try {
            $customActivitiesByName = $member->customActivities()->get()->keyBy('name');

            // Import custom activities (merge by name)
            if (! empty($data['custom_activities'])) {
                foreach ($data['custom_activities'] as $ca) {
                    $name = trim((string) ($ca['name'] ?? ''));
                    if ($name === '' || $customActivitiesByName->has($name)) {
                        continue;
                    }
                    $maxOrder = $member->customActivities()->max('sort_order') ?? 0;
                    $newCa = MemberCustomActivity::create([
                        'member_id' => $member->id,
                        'name' => $name,
                        'sort_order' => $ca['sort_order'] ?? $maxOrder + 1,
                    ]);
                    $customActivitiesByName->put($name, $newCa);
                }
            }

            // Import checklists (upsert by day + activity)
            if (! empty($data['checklists'])) {
                foreach ($data['checklists'] as $row) {
                    $dayNum = (int) ($row['day_number'] ?? 0);
                    $dailyContent = $dailyByDay->get($dayNum);
                    if (! $dailyContent) {
                        continue;
                    }

                    $activityName = trim((string) ($row['activity_name'] ?? ''));
                    $isCustom = ($row['activity_type'] ?? '') === 'custom';
                    $completed = (bool) ($row['completed'] ?? false);

                    if ($isCustom) {
                        $customActivity = $customActivitiesByName->get($activityName);
                        if (! $customActivity) {
                            continue;
                        }
                        MemberCustomChecklist::updateOrCreate(
                            [
                                'member_id' => $member->id,
                                'daily_content_id' => $dailyContent->id,
                                'member_custom_activity_id' => $customActivity->id,
                            ],
                            ['completed' => $completed]
                        );
                    } else {
                        $activity = $activitiesByName->get($activityName);
                        if (! $activity) {
                            continue;
                        }
                        MemberChecklist::updateOrCreate(
                            [
                                'member_id' => $member->id,
                                'daily_content_id' => $dailyContent->id,
                                'activity_id' => $activity->id,
                            ],
                            ['completed' => $completed]
                        );
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'success' => false,
                'message' => __('app.import_failed'),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('app.import_success'),
        ]);
    }

    /**
     * Clear all member data (checklists + custom activities). Resets to fresh state.
     */
    public function clear(Request $request): JsonResponse
    {
        $request->validate([
            'confirm' => ['required', 'string', 'in:RESET'],
        ]);

        /** @var Member $member */
        $member = $request->attributes->get('member');

        MemberChecklist::where('member_id', $member->id)->delete();
        MemberCustomChecklist::where('member_id', $member->id)->delete();
        MemberCustomActivity::where('member_id', $member->id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('app.data_cleared'),
        ]);
    }
}

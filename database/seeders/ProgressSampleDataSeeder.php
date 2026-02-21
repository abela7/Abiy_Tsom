<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\DailyContent;
use App\Models\LentSeason;
use App\Models\Member;
use App\Models\MemberChecklist;
use App\Models\MemberCustomChecklist;
use Illuminate\Database\Seeder;

/**
 * Seeds ~30 days of sample checklist data for a demo member
 * so the progress dashboard shows meaningful charts and reports.
 */
class ProgressSampleDataSeeder extends Seeder
{
    private const DEMO_TOKEN = 'sample-progress-demo';

    public function run(): void
    {
        $season = LentSeason::active();
        if (! $season) {
            $this->command->warn('No active Lent season. Create one first.');

            return;
        }

        $days = DailyContent::where('lent_season_id', $season->id)
            ->where('is_published', true)
            ->orderBy('day_number')
            ->limit(30)
            ->get();

        if ($days->isEmpty()) {
            $this->command->warn('No published daily content. Add days first.');

            return;
        }

        $activities = Activity::where('lent_season_id', $season->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($activities->isEmpty()) {
            $this->command->warn('No activities defined. Add activities first.');

            return;
        }

        $member = Member::firstOrCreate(
            ['token' => self::DEMO_TOKEN],
            [
                'baptism_name' => 'Sample Member',
                'locale' => 'en',
                'theme' => 'light',
            ]
        );

        // Remove existing checklist data for this member so we can reseed cleanly
        MemberChecklist::where('member_id', $member->id)->delete();
        MemberCustomChecklist::where('member_id', $member->id)->delete();

        $customActivities = $member->customActivities()->orderBy('sort_order')->get();
        $totalActivities = $activities->count() + $customActivities->count();

        if ($totalActivities === 0) {
            $this->command->warn('No activities to seed.');

            return;
        }

        $inserted = 0;

        foreach ($days as $day) {
            $baseRate = $this->completionRateForDay($day->day_number);
            mt_srand($day->day_number + 100); // reproducible per day

            foreach ($activities as $activity) {
                $activityBias = max(0.7, 1.0 - ($activity->sort_order * 0.06));
                $threshold = $baseRate * $activityBias;
                $doComplete = (mt_rand(1, 100) / 100.0) <= $threshold;

                MemberChecklist::create([
                    'member_id' => $member->id,
                    'daily_content_id' => $day->id,
                    'activity_id' => $activity->id,
                    'completed' => $doComplete,
                ]);
                $inserted++;
            }

            foreach ($customActivities as $ca) {
                $doComplete = (mt_rand(1, 100) / 100.0) <= ($baseRate * 0.9);

                MemberCustomChecklist::create([
                    'member_id' => $member->id,
                    'daily_content_id' => $day->id,
                    'member_custom_activity_id' => $ca->id,
                    'completed' => $doComplete,
                ]);
                $inserted++;
            }
        }

        $this->command->info(sprintf(
            '✓ Seeded %d checklist entries for "%s" across %d days.',
            $inserted,
            $member->baptism_name,
            $days->count()
        ));
        $this->command->info('  View progress: /member/home (or /member/progress) after member login');
        if ($days->count() < 30) {
            $this->command->warn('  Tip: Scaffold more days in Admin > Daily Content for a richer report.');
        }
    }

    /**
     * Returns a completion rate 0.0–1.0 for a given day to create varied charts.
     * Deterministic pattern: some strong days, some average, some weak.
     */
    private function completionRateForDay(int $dayNumber): float
    {
        $mod = ($dayNumber - 1) % 10;
        $cycle = (int) (($dayNumber - 1) / 10);

        // Wave pattern over 30 days: high -> low -> high
        $positions = [0.95, 0.82, 0.68, 0.55, 0.45, 0.52, 0.65, 0.78, 0.88, 0.92];

        return $positions[$mod] ?? 0.70;
    }
}

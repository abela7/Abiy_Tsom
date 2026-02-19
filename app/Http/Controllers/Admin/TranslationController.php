<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Translation;
use Database\Seeders\TranslationSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin translation management page.
 * Displays all English strings with their Amharic translations.
 */
class TranslationController extends Controller
{
    /**
     * Structure: section => [group => label].
     *
     * @return array<string, array<string, string>>
     */
    public static function getSections(): array
    {
        return [
            'user' => [
                'onboarding' => __('app.group_onboarding'),
                'wizard' => __('app.group_wizard'),
                'whatsapp_member' => __('app.group_whatsapp_member'),
                'navigation' => __('app.group_navigation'),
                'home' => __('app.group_home'),
                'content_sections' => __('app.group_day_content'),
                'calendar' => __('app.group_calendar'),
                'progress' => __('app.group_progress'),
                'settings' => __('app.group_settings'),
                'general' => __('app.group_general'),
            ],
            'admin' => [
                'admin_login' => __('app.group_admin_login'),
                'admin_dashboard' => __('app.group_admin_dashboard'),
                'admin_daily' => __('app.group_admin_daily'),
                'admin_activities' => __('app.group_activities'),
                'admin_other' => __('app.group_admin_other'),
                'admin_translations' => __('app.group_admin_translations'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getGroupLabels(): array
    {
        $labels = [];
        foreach (self::getSections() as $sectionGroups) {
            $labels = array_merge($labels, $sectionGroups);
        }

        return $labels;
    }

    public function index(Request $request): View
    {
        $sections = self::getSections();
        $allGroups = collect(array_merge(...array_values(array_map('array_keys', $sections))));

        $group = $request->query('group');
        $section = $request->query('section', 'user');

        if (! in_array($section, ['user', 'admin'], true)) {
            $section = 'user';
        }

        if (! $group || ! $allGroups->contains($group) || ! isset($sections[$section][$group])) {
            $group = array_key_first($sections[$section]) ?? 'onboarding';
        }

        $isActivityTranslationGroup = $group === 'admin_activities';
        $activities = collect();

        if ($isActivityTranslationGroup) {
            $enStrings = collect();
            $amStrings = collect();
            $activities = Activity::query()
                ->with('lentSeason')
                ->orderBy('lent_season_id')
                ->orderBy('sort_order')
                ->get();
        } else {
            // Get English strings for this group
            $enStrings = Translation::where('locale', 'en')
                ->where('group', $group)
                ->orderBy('key')
                ->get()
                ->keyBy('key');

            // Get Amharic translations
            $amStrings = Translation::where('locale', 'am')
                ->where('group', $group)
                ->orderBy('key')
                ->get()
                ->keyBy('key');
        }

        return view('admin.translations.index', compact('sections', 'section', 'group', 'isActivityTranslationGroup', 'activities', 'enStrings', 'amStrings'));
    }

    /**
     * Save/update translations in bulk.
     */
    public function update(Request $request): RedirectResponse
    {
        $group = $request->input('group');

        if ($group === 'admin_activities') {
            $request->validate([
                'group' => ['required', 'string'],
                'activities' => ['required', 'array'],
                'activities.*.name_en' => ['nullable', 'string'],
                'activities.*.name_am' => ['nullable', 'string'],
                'activities.*.description_en' => ['nullable', 'string'],
                'activities.*.description_am' => ['nullable', 'string'],
            ]);

            foreach ($request->input('activities', []) as $activityId => $payload) {
                $activity = Activity::query()->find((int) $activityId);
                if (! $activity) {
                    continue;
                }

                $activity->update([
                    'name_en' => trim((string) ($payload['name_en'] ?? '')),
                    'name_am' => trim((string) ($payload['name_am'] ?? '')),
                    'description_en' => trim((string) ($payload['description_en'] ?? '')),
                    'description_am' => trim((string) ($payload['description_am'] ?? '')),
                ]);
            }

            return redirect("/admin/translations?section=admin&group={$group}")
                ->with('success', __('app.translations_saved'));
        }

        $request->validate([
            'group' => ['required', 'string'],
            'translations' => ['required', 'array'],
            'translations.*.key' => ['required', 'string'],
            'translations.*.en' => ['required', 'string'],
            'translations.*.am' => ['nullable', 'string'],
        ]);

        foreach ($request->input('translations') as $item) {
            Translation::updateOrCreate(
                ['group' => $group, 'key' => $item['key'], 'locale' => 'en'],
                ['value' => trim((string) ($item['en'] ?? ''))]
            );
            Translation::updateOrCreate(
                ['group' => $group, 'key' => $item['key'], 'locale' => 'am'],
                ['value' => trim((string) ($item['am'] ?? ''))]
            );
        }

        Translation::clearCache();

        $section = isset(self::getSections()['user'][$group]) ? 'user' : 'admin';

        return redirect("/admin/translations?section={$section}&group={$group}")
            ->with('success', __('app.translations_saved'));
    }

    /**
     * Add a new translation key.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:255'],
            'en' => ['required', 'string'],
            'am' => ['nullable', 'string'],
        ]);

        Translation::updateOrCreate(
            ['group' => $validated['group'], 'key' => $validated['key'], 'locale' => 'en'],
            ['value' => $validated['en']]
        );

        if (! empty($validated['am'])) {
            Translation::updateOrCreate(
                ['group' => $validated['group'], 'key' => $validated['key'], 'locale' => 'am'],
                ['value' => $validated['am']]
            );
        }

        Translation::clearCache();

        $g = $validated['group'];
        $section = isset(self::getSections()['user'][$g]) ? 'user' : 'admin';

        return redirect("/admin/translations?section={$section}&group={$g}")
            ->with('success', __('app.translation_added'));
    }

    /**
     * Sync translations from lang files to the database.
     */
    public function sync(): RedirectResponse
    {
        (new TranslationSeeder)->run();

        return redirect()
            ->route('admin.translations.index', ['section' => 'user', 'group' => 'wizard'])
            ->with('success', __('app.translations_saved'));
    }
}

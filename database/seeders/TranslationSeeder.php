<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Translation;
use Illuminate\Database\Seeder;

/**
 * Seeds the translations table from lang files, grouped by page.
 * Admin can fill in Amharic page by page via the Translations UI.
 */
class TranslationSeeder extends Seeder
{
    /**
     * Keys per page/group for organized translation.
     *
     * @return array<string, array<int, string>>
     */
    private function getKeysByPage(): array
    {
        return [
            'onboarding' => [
                'app_name', 'tagline', 'onboarding_title', 'onboarding_subtitle',
                'baptism_name', 'baptism_name_placeholder', 'start_journey', 'already_registered',
            ],
            'navigation' => [
                'nav_home', 'nav_calendar', 'nav_progress', 'nav_settings',
            ],
            'home' => [
                'easter_countdown', 'easter_countdown_subtitle', 'days', 'hours', 'minutes', 'seconds',
                'welcome', 'today', 'day_of', 'day_page_title', 'week', 'this_week', 'no_content_today',
                'checklist', 'mark_complete', 'christ_is_risen',
                'view_today', 'view_recommended_day', 'well_done',
            ],
            'content_sections' => [
                'bible_reading', 'read', 'mezmur', 'sinksar', 'spiritual_book', 'reflection',
                'listen', 'watch', 'open_in_youtube', 'open_externally', 'read_more',
                'references', 'reference_name', 'reference_url', 'add_reference', 'close',
                'weekly_theme', 'gospel_reference', 'epistles_reference',
            ],
            'calendar' => [
                'calendar_title', 'no_calendar_content', 'completed', 'not_started',
                'in_progress', 'past', 'future', 'today',
                'calendar_passed', 'calendar_upcoming', 'check_back_soon',
                'week_one', 'week_two', 'week_three', 'week_four',
                'week_five', 'week_six', 'week_seven', 'week_eight',
            ],
            'progress' => [
                'progress_title', 'progress_subtitle', 'overall_progress',
                'daily_completion', 'activity_breakdown',
                'suggestions', 'suggestion_text', 'suggestion_improve',
                'great_job', 'no_data', 'start_tracking_hint',
                'day_streak', 'consecutive_days', 'best_day', 'needs_work',
                'period_daily', 'period_weekly', 'period_monthly', 'period_all',
                'season_heatmap', 'heatmap_hint',
            ],
            'settings' => [
                'settings_title', 'language', 'theme', 'theme_light', 'theme_dark',
                'lang_en', 'lang_am', 'custom_activities', 'custom_activities_desc',
                'custom_activity_placeholder', 'custom_activity_added', 'custom_activity_deleted',
                'no_custom_activities', 'add', 'passcode_lock', 'passcode_enable', 'passcode_disable',
                'set_passcode', 'passcode_saved', 'passcode_enabled', 'incorrect_passcode',
                'enter_passcode', 'passcode_title', 'passcode_subtitle', 'unlock',
            ],
            'general' => [
                'save', 'cancel', 'edit', 'delete', 'create', 'back', 'yes', 'no',
                'search', 'submit', 'confirm', 'loading',
            ],
            'admin_login' => [
                'admin_login', 'email', 'password', 'remember_me', 'login', 'logout',
            ],
            'admin_dashboard' => [
                'dashboard', 'active_season', 'none', 'create_one', 'published_days',
                'total_members', 'quick_actions', 'add_daily_content', 'add_activity',
                'manage_translations',
            ],
            'admin_daily' => [
                'edit_day', 'create_daily_content', 'day_number_label', 'date_label',
                'weekly_theme_label', 'select_placeholder', 'day_title_optional',
                'bible_reading_label', 'reference_placeholder', 'summary_label',
                'bible_text_en_label', 'bible_text_en_placeholder', 'shown_when_english',
                'bible_text_am_label', 'bible_text_am_placeholder', 'shown_when_amharic',
                'mezmur_label', 'title_label', 'url_label', 'description_label',
                'sinksar_label', 'url_video_label', 'spiritual_book_label',
                'references_legend', 'references_help', 'reflection_label', 'publish_label',
            ],
            'admin_other' => [
                'seasons', 'themes', 'daily_content', 'announcements', 'announcement',
                'photo', 'no_announcements', 'show_action_button', 'button_label', 'button_url',
                'current_photo', 'announcement_created', 'announcement_updated', 'announcement_deleted',
                'actions', 'announcements_section',
                'activities', 'translations',
                'members', 'published', 'draft', 'active', 'inactive',
            ],
            'admin_translations' => [
                'add_key', 'no_translation_groups', 'group_label', 'key_label',
                'english_label', 'amharic_label', 'translations_saved', 'translation_added',
                'no_translations_in_group', 'sync_translations', 'sync_translations_help',
                'section_user', 'section_admin',
                'pages_label', 'admin_pages_label',
                'group_onboarding', 'group_navigation', 'group_home', 'group_day_content',
                'group_calendar', 'group_progress', 'group_settings', 'group_general',
                'group_admin_login', 'group_admin_dashboard', 'group_admin_daily',
                'group_admin_other', 'group_admin_translations',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getAllStrings(): array
    {
        $en = require base_path('lang/en/app.php');
        if (! is_array($en)) {
            return [];
        }

        $extra = [
            'add_key' => '+ Add Key',
            'no_translation_groups' => 'No translation groups yet. Add one below.',
            'group_label' => 'Group',
            'key_label' => 'Key',
            'english_label' => 'English',
            'amharic_label' => 'Amharic',
            'translations_saved' => 'Translations saved.',
            'translation_added' => 'Translation key added.',
            'no_translations_in_group' => 'No translations in this group yet.',
            'sync_translations' => 'Sync from Files',
            'sync_translations_help' => 'Import all strings from language files into the database. Run this first to populate the list for Amharic translation.',
            'section_user' => 'User (Member)',
            'section_admin' => 'Admin',
            'active_season' => 'Active Season',
            'none' => 'None',
            'create_one' => 'Create one',
            'published_days' => 'Published Days',
            'total_members' => 'Total Members',
            'quick_actions' => 'Quick Actions',
            'add_daily_content' => 'Add Daily Content',
            'add_activity' => 'Add Activity',
            'manage_translations' => 'Manage Translations',
            'edit_day' => 'Edit Day :day',
            'create_daily_content' => 'Create Daily Content',
            'day_number_label' => 'Day Number (1-55)',
            'date_label' => 'Date',
            'weekly_theme_label' => 'Weekly Theme',
            'select_placeholder' => 'Select...',
            'day_title_optional' => 'Day Title (optional)',
            'bible_reading_label' => 'Bible Reading',
            'reference_placeholder' => 'Reference (e.g. John 3:1-16)',
            'summary_label' => 'Summary',
            'bible_text_en_label' => 'Bible Text (English)',
            'bible_text_en_placeholder' => 'Full reading text in English (optional)',
            'shown_when_english' => 'Shown when user language is English',
            'bible_text_am_label' => 'Bible Text (Amharic)',
            'bible_text_am_placeholder' => 'Full reading text in Amharic (optional)',
            'shown_when_amharic' => 'Shown when user language is Amharic',
            'mezmur_label' => 'Mezmur (Spiritual Music)',
            'title_label' => 'Title',
            'url_label' => 'URL (YouTube / Audio link)',
            'description_label' => 'Description',
            'sinksar_label' => 'Sinksar (Synaxarium)',
            'url_video_label' => 'URL (YouTube or video link)',
            'spiritual_book_label' => 'Spiritual Book',
            'references_legend' => 'References (know more about the week or day)',
            'references_help' => 'Add links for members to learn more. Each needs a name and URL.',
            'reflection_label' => 'Daily Reflection / Message',
            'publish_label' => 'Publish (visible to members)',
            'passcode_enabled' => 'Passcode is enabled.',
        ];

        return array_merge($en, $extra);
    }

    /**
     * Build key => group map from getKeysByPage.
     *
     * @return array<string, string>
     */
    private function getKeyToGroupMap(): array
    {
        $map = [];
        foreach ($this->getKeysByPage() as $group => $keys) {
            foreach ($keys as $key) {
                $map[$key] = $group;
            }
        }

        return $map;
    }

    /**
     * Infer group for keys not in predefined mapping.
     * Uncategorized keys go to 'general' (user) or 'admin_other' (admin UI).
     */
    private function inferGroup(string $key): string
    {
        $adminKeys = [
            'admin_login', 'email', 'password', 'remember_me', 'login', 'logout',
            'dashboard', 'seasons', 'themes', 'daily_content', 'activities', 'translations',
            'members', 'published', 'draft', 'active', 'inactive',
        ];
        if (in_array($key, $adminKeys, true) || str_starts_with($key, 'admin_')) {
            return 'admin_other';
        }

        return 'general';
    }

    public function run(): void
    {
        Translation::where('group', 'app')->delete();

        $allStrings = $this->getAllStrings();
        $keyToGroup = $this->getKeyToGroupMap();
        $amFile = base_path('lang/am/app.php');
        $am = file_exists($amFile) && is_array($amData = require $amFile) ? $amData : [];

        foreach ($allStrings as $key => $enValue) {
            $group = $keyToGroup[$key] ?? $this->inferGroup($key);
            $amValue = isset($am[$key]) ? (string) $am[$key] : '';

            // English: create or update from file
            Translation::updateOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => 'en'],
                ['value' => (string) $enValue]
            );

            // Amharic: create if missing; also replace placeholder English.
            $amRecord = Translation::firstOrCreate(
                ['group' => $group, 'key' => $key, 'locale' => 'am'],
                ['value' => $amValue]
            );
            $currentAm = trim((string) $amRecord->value);
            $englishSource = trim((string) $enValue);

            if ($amValue !== '' && ($currentAm === '' || $currentAm === $englishSource)) {
                $amRecord->update(['value' => $amValue]);
            }
        }

        Translation::clearCache();
    }
}

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
     * Every key in lang/en/app.php must appear here.
     *
     * @return array<string, array<int, string>>
     */
    private function getKeysByPage(): array
    {
        return [
            /* ── User-facing pages ── */
            'onboarding' => [
                'app_name', 'tagline', 'meta_description', 'og_title', 'og_description',
                'onboarding_title', 'onboarding_subtitle',
                'baptism_name', 'baptism_name_placeholder', 'baptism_name_saved',
                'start_journey', 'already_registered',
            ],
            'navigation' => [
                'nav_home', 'nav_calendar', 'nav_progress', 'nav_settings',
                'footer_branding',
            ],
            'home' => [
                'welcome', 'easter_countdown', 'easter_countdown_subtitle',
                'days', 'hours', 'minutes', 'seconds',
                'easter_countdown_remaining', 'easter_countdown_tz',
                'today', 'view_today', 'view_recommended_day',
                'day_of', 'day_page_title', 'week', 'this_week', 'no_content_today',
                'checklist', 'mark_complete', 'christ_is_risen', 'well_done',
            ],
            'content_sections' => [
                'bible_reading', 'read', 'mezmur', 'sinksar', 'spiritual_book', 'reflection',
                'listen', 'watch', 'open_in_youtube', 'open_externally',
                'read_more', 'show_less', 'tap_for_details', 'tap_to_collapse',
                'references', 'reference_name', 'reference_url', 'add_reference',
                'close', 'weekly_theme', 'gospel_reference', 'epistles_reference',
                'video_player',
            ],
            'calendar' => [
                'calendar_title', 'no_calendar_content', 'completed', 'not_started',
                'in_progress', 'past', 'future',
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
                'report_scope', 'jump_to_day', 'jump_to_week', 'view_day_content',
                'season_heatmap', 'heatmap_hint', 'day_x_rate',
            ],
            'settings' => [
                'settings_title', 'language', 'theme', 'theme_light', 'theme_dark',
                'lang_en', 'lang_am',
                'custom_activities', 'custom_activities_desc',
                'custom_activity_placeholder', 'custom_activity_added', 'custom_activity_deleted',
                'no_custom_activities', 'add',
                'passcode_lock', 'lock_app', 'passcode_enable', 'passcode_disable',
                'set_passcode', 'passcode_saved', 'passcode_enabled', 'incorrect_passcode',
                'enter_passcode', 'passcode_title', 'passcode_subtitle', 'unlock',
                'data_management', 'data_management_desc',
                'export_data', 'export_data_desc',
                'import_data', 'import_data_desc',
                'clear_data', 'clear_data_desc',
                'export', 'import', 'reset',
                'export_success', 'import_invalid_format', 'import_no_season',
                'import_failed', 'import_success',
                'data_cleared', 'clear_confirm_label', 'clear_confirm_placeholder',
                'are_you_sure', 'failed_to_add', 'failed_to_clear',
                'failed_to_save', 'failed', 'export_failed',
            ],
            'general' => [
                'loading', 'save', 'cancel', 'edit', 'delete', 'create', 'back',
                'yes', 'no', 'search', 'submit', 'confirm',
                'amharic', 'english', 'amharic_default', 'english_fallback',
                'name', 'remove',
            ],

            /* ── Admin pages ── */
            'admin_login' => [
                'admin_login', 'email', 'password', 'remember_me', 'login', 'logout',
                'username', 'username_placeholder',
            ],
            'admin_dashboard' => [
                'admin', 'dashboard', 'toggle_menu', 'toggle_theme',
                'active_season', 'none', 'create_one', 'published_days',
                'total_members', 'quick_actions',
                'add_daily_content', 'add_activity', 'manage_translations',
            ],
            'admin_daily' => [
                'edit_day', 'create_daily_content',
                'step_x_of_y', 'next', 'saving', 'saved', 'finish',
                'review_and_publish',
                'step_day_info', 'step_bible_reading', 'step_mezmur',
                'step_sinksar', 'step_spiritual_book',
                'step_reflection_refs', 'step_review_publish',
                'step_saved_continue',
                'day_number_label', 'date_label', 'weekly_theme_label',
                'select_placeholder', 'day_title_optional',
                'bible_reading_label', 'reference_placeholder', 'summary_label',
                'bible_text_en_label', 'bible_text_en_placeholder', 'shown_when_english',
                'bible_text_am_label', 'bible_text_am_placeholder', 'shown_when_amharic',
                'mezmur_label', 'title_label', 'url_label', 'description_label',
                'sinksar_label', 'url_video_label', 'spiritual_book_label',
                'references_legend', 'references_help', 'reflection_label', 'publish_label',
                'add_mezmur_hint', 'name_amharic_label', 'name_english_label',
                'url_placeholder', 'day_label', 'week_label',
                'no_active_season', 'create_one_first',
                'no_daily_content_yet', 'scaffold_55_days', 'scaffold_confirm',
                'title', 'bible', 'start', 'end',
            ],
            'admin_other' => [
                'seasons', 'themes', 'daily_content', 'announcements', 'announcement',
                'photo', 'no_announcements', 'show_action_button',
                'button_label', 'button_url', 'button_label_placeholder',
                'youtube_url', 'youtube_url_placeholder',
                'youtube_position', 'youtube_position_top', 'youtube_position_end',
                'current_photo', 'announcement_created', 'announcement_updated',
                'announcement_deleted', 'actions', 'announcements_section',
                'activities', 'translations', 'seo', 'members',
                'published', 'draft', 'active', 'inactive',
                'manage_admins', 'add_admin', 'edit_admin', 'view_admin', 'view',
                'admin_created', 'admin_updated', 'admin_deleted', 'confirm_delete_admin',
                'email_optional', 'editor', 'writer', 'role', 'super_admin',
                'password_leave_blank', 'password_confirmation',
                'confirm_delete_activity', 'edit_activity', 'create_activity',
                'activity_name', 'description_optional', 'sort_order',
                'activity_placeholder', 'no_activities_yet',
                'created', 'created_by', 'updated_by', 'count', 'order', 'status',
                'no_registrations_yet', 'no_data_short',
                'edit_season', 'create_season', 'no_seasons_yet',
                'year', 'total_days', 'start_date', 'end_date_easter',
                'set_as_active_season', 'regenerate_8_weeks',
                'edit_theme', 'create_theme', 'no_weekly_themes_yet',
                'week_num', 'week_number_1_8',
                'name_english', 'name_geez', 'name_amharic',
                'meaning', 'theme_summary',
                'week_start_date', 'week_end_date', 'liturgy_anaphora',
                'theme_name_en_placeholder', 'theme_name_geez_placeholder',
                'reference_placeholder_short', 'epistles_placeholder',
                'psalm_placeholder', 'liturgy_placeholder', 'meaning_placeholder',
                'psalm_reference', 'key_placeholder', 'passcode_placeholder',
                // Member tracking stats
                'members_tracking', 'members_tracking_subtitle',
                'registrations_by_day', 'first_registration', 'last_registration',
                'new_last_7_days', 'new_last_30_days',
                'locale_distribution', 'theme_distribution', 'passcode_users',
                'total_completions', 'engaged_members',
                // SEO settings
                'seo_settings', 'seo_help', 'site_identity',
                'site_title_en', 'site_title_am',
                'meta_description_label', 'meta_description_en', 'meta_description_am',
                'open_graph', 'og_title_en', 'og_title_am',
                'og_description_en', 'og_description_am',
                'og_image', 'upload_new_image', 'og_image_hint',
                'current_og_image', 'remove_og_image',
                'twitter_card', 'twitter_card_summary', 'twitter_card_summary_large_image',
                'robots', 'robots_directive', 'robots_placeholder', 'robots_help',
                'share_preview', 'social_preview_hint', 'seo_saved',
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
     * All translatable strings. The lang/en/app.php file is the
     * single source of truth — no extra duplicates needed.
     *
     * @return array<string, string>
     */
    private function getAllStrings(): array
    {
        $en = require base_path('lang/en/app.php');

        return is_array($en) ? $en : [];
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
     * Safety net — ideally every key is in getKeysByPage().
     */
    private function inferGroup(string $key): string
    {
        if (str_starts_with($key, 'admin_') || str_starts_with($key, 'group_')) {
            return 'admin_other';
        }
        if (str_starts_with($key, 'step_')) {
            return 'admin_daily';
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

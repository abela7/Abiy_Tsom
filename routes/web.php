<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContentSuggestionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\Member;
use App\Http\Controllers\VolunteerInviteController;
use App\Http\Controllers\Webhook;
use App\Http\Controllers\TelegramAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member-Facing Routes (public + member-identified)
|--------------------------------------------------------------------------
*/

// Welcome / onboarding (no auth required)
Route::get('/', [Member\OnboardingController::class, 'welcome'])->name('home');
Route::post('/member/register', [Member\OnboardingController::class, 'register'])->name('member.register');
Route::get('/member/access/{token}', [Member\OnboardingController::class, 'access'])
    ->where('token', '[A-Za-z0-9]{20,128}')
    ->name('member.access');
Route::get('/r/{code}', [Member\ReferralController::class, 'track'])
    ->where('code', '[A-Za-z0-9]{8}')
    ->name('referral.track');
Route::post('/member/identify', [Member\OnboardingController::class, 'identify'])
    ->middleware('member')
    ->name('member.identify');
Route::post('/member/reset', [Member\OnboardingController::class, 'reset'])
    ->middleware('member')
    ->name('member.reset');
Route::post('/webhooks/telegram', [Webhook\TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.telegram');
Route::post('/webhooks/ultramsg', [Webhook\UltraMsgWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.ultramsg');

// Public share page — serves OG meta for social crawlers, then redirects
Route::get('/share/day/{daily}', [Member\ShareController::class, 'day'])->name('share.day');
Route::get('/share/day/{daily}/public', [Member\ShareController::class, 'publicDay'])->name('share.day.public');
// Landing page for WhatsApp go-back links — serves OG tags; JS then redirects to /auth/access
Route::get('/auth/go', [TelegramAuthController::class, 'go'])
    ->middleware('throttle:60,1')
    ->name('auth.go');
Route::get('/auth/access', [TelegramAuthController::class, 'access'])
    ->middleware('throttle:60,1')
    ->name('auth.access');
Route::get('/telegram/mini/connect', [TelegramAuthController::class, 'miniConnect'])->name('telegram.mini.connect');
Route::get('/telegram/embed', [TelegramAuthController::class, 'embed'])->name('telegram.embed');
Route::get('/telegram/webapp/home', [TelegramAuthController::class, 'webappHome'])
    ->middleware('throttle:60,1')
    ->name('telegram.webapp.home');
Route::post('/telegram/mini/connect', [TelegramAuthController::class, 'miniConnectSubmit'])
    ->middleware('throttle:60,1')
    ->name('telegram.mini.connect.submit');

// Public content suggestion form (no auth required)
Route::get('/suggest', [ContentSuggestionController::class, 'show'])->name('suggest');
Route::post('/suggest', [ContentSuggestionController::class, 'store'])->name('suggest.store');
// Feedback (modal submit, no auth required)
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('feedback.store');

Route::get('/invite/{slug}', [VolunteerInviteController::class, 'show'])->name('volunteer.invite.show');
Route::post('/invite/{slug}/track', [VolunteerInviteController::class, 'track'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('volunteer.invite.track');
Route::post('/invite/{slug}/decision', [VolunteerInviteController::class, 'decision'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('volunteer.invite.decision');
Route::post('/invite/{slug}/contact', [VolunteerInviteController::class, 'contact'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('volunteer.invite.contact');

// Passcode routes (member-identified but before passcode check)
Route::middleware('member')->group(function () {
    Route::get('/member/passcode', [Member\PasscodeController::class, 'show'])->name('member.passcode');
    Route::get('/member/passcode/lock', [Member\PasscodeController::class, 'lock'])->name('member.passcode.lock');
    Route::post('/member/passcode/verify', [Member\PasscodeController::class, 'verify'])->name('member.passcode.verify');
    Route::post('/member/passcode/update', [Member\PasscodeController::class, 'update'])->name('member.passcode.update');
    Route::post('/member/passcode/reset', [Member\PasscodeController::class, 'reset'])->name('member.passcode.reset');
});

// Member-protected routes (identified + passcode cleared)
Route::middleware(['member', 'member.passcode'])->prefix('member')->name('member.')->group(function () {
    Route::get('/home', [Member\HomeController::class, 'index'])->name('home');
    Route::get('/calendar', [Member\HomeController::class, 'calendar'])->name('calendar');
    Route::get('/day/{daily}', [Member\HomeController::class, 'day'])->name('day');
    Route::get('/day/{daily}/commemorations', [Member\HomeController::class, 'commemorations'])->name('commemorations');
    Route::get('/week/{weeklyTheme}', [Member\HomeController::class, 'week'])->name('week');
    Route::get('/announcement/{announcement}', [Member\AnnouncementController::class, 'show'])->name('announcement.show');

    // Progress
    Route::get('/progress', [Member\ProgressController::class, 'index'])->name('progress');

    // Settings
    Route::get('/settings', [Member\SettingsController::class, 'index'])->name('settings');
});

// API-style routes for AJAX calls (JSON responses) — member resolved from secure session
Route::prefix('api/member')->middleware('api.member')->name('api.member.')->group(function () {
    Route::post('/checklist/toggle', [Member\ChecklistController::class, 'toggle'])->name('checklist.toggle');
    Route::post('/checklist/custom-toggle', [Member\CustomActivityController::class, 'toggle'])->name('checklist.custom-toggle');
    Route::post('/settings', [Member\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/custom-activities', [Member\CustomActivityController::class, 'store'])->name('custom-activities.store');
    Route::post('/custom-activities/update', [Member\CustomActivityController::class, 'update'])->name('custom-activities.update');
    Route::post('/custom-activities/delete', [Member\CustomActivityController::class, 'destroy'])->name('custom-activities.destroy');
    Route::get('/progress/data', [Member\ProgressController::class, 'data'])->name('progress.data');
    Route::get('/data/export', [Member\DataController::class, 'export'])->name('data.export');
    Route::post('/data/import', [Member\DataController::class, 'import'])->name('data.import');
    Route::post('/data/clear', [Member\DataController::class, 'clear'])->name('data.clear');
    Route::post('/telegram-link', [Member\SettingsController::class, 'generateTelegramLink'])->name('telegram-link');
    Route::post('/telegram-unlink', [Member\SettingsController::class, 'unlinkTelegram'])->name('telegram-unlink');
    Route::get('/fundraising/popup', [Member\FundraisingController::class, 'popup'])->name('fundraising.popup');
    Route::post('/fundraising/snooze', [Member\FundraisingController::class, 'snooze'])->name('fundraising.snooze');
    Route::post('/fundraising/interested', [Member\FundraisingController::class, 'interested'])->name('fundraising.interested');
    Route::post('/banner/{banner}/respond', [Member\BannerController::class, 'respond'])->name('banner.respond');
    Route::post('/tour/complete', [Member\TourController::class, 'complete'])->name('tour.complete');
    Route::post('/tour/reset', [Member\TourController::class, 'reset'])->name('tour.reset');
    Route::post('/account/delete', [Member\SettingsController::class, 'deleteAccount'])->name('account.delete');
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Laravel auth)
|--------------------------------------------------------------------------
*/

// Admin login (guest only)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [Admin\AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [Admin\AuthController::class, 'login'])->name('login.submit');
    });

    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
});

// Admin protected routes
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    // Writer/editor/admin routes
    Route::middleware('admin_role:writer,editor,admin')->group(function () {
        // My suggestions (writer sees their own submissions)
        Route::get('/suggestions/my', [App\Http\Controllers\ContentSuggestionController::class, 'my'])->name('suggestions.my');

        // Daily content
        Route::get('/daily', [Admin\DailyContentController::class, 'index'])->name('daily.index');
        Route::post('/daily/scaffold', [Admin\DailyContentController::class, 'scaffold'])->name('daily.scaffold');
        Route::get('/daily/create', [Admin\DailyContentController::class, 'create'])->name('daily.create');
        Route::get('/daily/copy-from/{day_number}', [Admin\DailyContentController::class, 'copyFrom'])->name('daily.copy_from');
        Route::post('/daily/upload-book-pdf', [Admin\DailyContentController::class, 'uploadBookPdf'])->name('daily.upload_book_pdf');
        Route::post('/daily/upload-sinksar-image', [Admin\DailyContentController::class, 'uploadSinksarImage'])->name('daily.upload_sinksar_image');
        Route::post('/daily/delete-sinksar-image', [Admin\DailyContentController::class, 'deleteSinksarImage'])->name('daily.delete_sinksar_image');
        Route::post('/daily', [Admin\DailyContentController::class, 'store'])->name('daily.store');
        Route::get('/daily/{daily}/preview', [Admin\DailyContentController::class, 'preview'])->name('daily.preview');
        Route::get('/daily/{daily}/edit', [Admin\DailyContentController::class, 'edit'])->name('daily.edit');
        Route::patch('/daily/{daily}', [Admin\DailyContentController::class, 'patch'])->name('daily.patch');
        Route::put('/daily/{daily}', [Admin\DailyContentController::class, 'update'])->name('daily.update');
        Route::delete('/daily/{daily}', [Admin\DailyContentController::class, 'destroy'])->name('daily.destroy');

        // Announcements
        Route::resource('announcements', Admin\AnnouncementController::class)->except(['show']);

        // Activities
        Route::get('/activities', [Admin\ActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [Admin\ActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities', [Admin\ActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}/edit', [Admin\ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/activities/{activity}', [Admin\ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [Admin\ActivityController::class, 'destroy'])->name('activities.destroy');
    });

    // Editor/admin routes
    Route::middleware('admin_role:editor,admin')->group(function () {
        // Fundraising campaign popup management
        Route::get('/fundraising', [Admin\FundraisingController::class, 'index'])->name('fundraising.index');
        Route::post('/fundraising', [Admin\FundraisingController::class, 'store'])->name('fundraising.store');
        Route::delete('/fundraising/response/{id}', [Admin\FundraisingController::class, 'deleteResponse'])->name('fundraising.delete-response');
        Route::post('/fundraising/reset', [Admin\FundraisingController::class, 'resetResponses'])->name('fundraising.reset');
        Route::get('/volunteer-invitations', [Admin\VolunteerInviteController::class, 'index'])->name('volunteer-invitations.index');
        Route::post('/volunteer-invitations', [Admin\VolunteerInviteController::class, 'store'])->name('volunteer-invitations.store');
        Route::get('/volunteer-invitations/{campaign}', [Admin\VolunteerInviteController::class, 'stats'])->name('volunteer-invitations.stats');
        Route::post('/volunteer-invitations/{campaign}/submissions/export', [Admin\VolunteerInviteController::class, 'exportSubmissions'])->name('volunteer-invitations.submissions.export');
        Route::post('/volunteer-invitations/{campaign}/submissions/delete', [Admin\VolunteerInviteController::class, 'deleteSubmissions'])->name('volunteer-invitations.submissions.delete');
        Route::put('/volunteer-invitations/{campaign}', [Admin\VolunteerInviteController::class, 'update'])->name('volunteer-invitations.update');
        Route::delete('/volunteer-invitations/{campaign}', [Admin\VolunteerInviteController::class, 'destroy'])->name('volunteer-invitations.destroy');
        Route::post('/volunteer-invitations/{campaign}/activate', [Admin\VolunteerInviteController::class, 'activate'])->name('volunteer-invitations.activate');

        // Banners
        Route::get('/banners', [Admin\BannerController::class, 'index'])->name('banners.index');
        Route::post('/banners', [Admin\BannerController::class, 'store'])->name('banners.store');
        Route::put('/banners/{banner}', [Admin\BannerController::class, 'update'])->name('banners.update');
        Route::delete('/banners/{banner}', [Admin\BannerController::class, 'destroy'])->name('banners.destroy');
        Route::post('/banners/{banner}/toggle', [Admin\BannerController::class, 'toggleActive'])->name('banners.toggle');

        // Feedback
        Route::get('/feedback', [Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback/{feedback}/toggle-read', [Admin\FeedbackController::class, 'toggleRead'])->name('feedback.toggle-read');
        Route::delete('/feedback/{feedback}', [Admin\FeedbackController::class, 'destroy'])->name('feedback.destroy');

        // Content suggestions review
        Route::get('/suggestions', [Admin\ContentSuggestionController::class, 'index'])->name('suggestions.index');
        Route::delete('/suggestions/clear-all', [Admin\ContentSuggestionController::class, 'clearAll'])->name('suggestions.clear-all');
        Route::post('/suggestions/{suggestion}/use', [Admin\ContentSuggestionController::class, 'markUsed'])->name('suggestions.mark-used');
        Route::post('/suggestions/{suggestion}/unuse', [Admin\ContentSuggestionController::class, 'unmarkUsed'])->name('suggestions.unmark-used');
        Route::post('/suggestions/{suggestion}/reject', [Admin\ContentSuggestionController::class, 'reject'])->name('suggestions.reject');

        // Seasons
        Route::get('/seasons', [Admin\LentSeasonController::class, 'index'])->name('seasons.index');
        Route::get('/seasons/create', [Admin\LentSeasonController::class, 'create'])->name('seasons.create');
        Route::post('/seasons', [Admin\LentSeasonController::class, 'store'])->name('seasons.store');
        Route::get('/seasons/{season}/edit', [Admin\LentSeasonController::class, 'edit'])->name('seasons.edit');
        Route::put('/seasons/{season}', [Admin\LentSeasonController::class, 'update'])->name('seasons.update');

        // Weekly themes
        Route::get('/themes', [Admin\WeeklyThemeController::class, 'index'])->name('themes.index');
        Route::get('/themes/create', [Admin\WeeklyThemeController::class, 'create'])->name('themes.create');
        Route::post('/themes', [Admin\WeeklyThemeController::class, 'store'])->name('themes.store');
        Route::get('/themes/{theme}/edit', [Admin\WeeklyThemeController::class, 'edit'])->name('themes.edit');
        Route::put('/themes/{theme}', [Admin\WeeklyThemeController::class, 'update'])->name('themes.update');

        // Lectionary (ግጻዌ — daily scripture readings)
        Route::get('/lectionary', [Admin\LectionaryController::class, 'index'])->name('lectionary.index');
        Route::post('/lectionary', [Admin\LectionaryController::class, 'store'])->name('lectionary.store');
        Route::put('/lectionary/{lectionary}', [Admin\LectionaryController::class, 'update'])->name('lectionary.update');
        Route::delete('/lectionary/{lectionary}', [Admin\LectionaryController::class, 'destroy'])->name('lectionary.destroy');

        // Ethiopian Synaxarium (calendar celebrations)
        Route::get('/synaxarium', [Admin\SynaxariumController::class, 'index'])->name('synaxarium.index');
        Route::post('/synaxarium/monthly', [Admin\SynaxariumController::class, 'storeMonthly'])->name('synaxarium.monthly.store');
        Route::put('/synaxarium/monthly/{monthly}', [Admin\SynaxariumController::class, 'updateMonthly'])->name('synaxarium.monthly.update');
        Route::delete('/synaxarium/monthly/{monthly}', [Admin\SynaxariumController::class, 'destroyMonthly'])->name('synaxarium.monthly.destroy');
        Route::post('/synaxarium/annual', [Admin\SynaxariumController::class, 'storeAnnual'])->name('synaxarium.annual.store');
        Route::put('/synaxarium/annual/{annual}', [Admin\SynaxariumController::class, 'updateAnnual'])->name('synaxarium.annual.update');
        Route::delete('/synaxarium/annual/{annual}', [Admin\SynaxariumController::class, 'destroyAnnual'])->name('synaxarium.annual.destroy');
    });

    // Available to all authenticated admin roles (writer, editor, admin, super admin)
    Route::middleware('admin_role:writer,editor,admin')->group(function () {
        Route::get('/telegram/my-link', [TelegramAuthController::class, 'myTelegramLink'])->name('telegram.my-link');
    });

    // Super admin only routes
    Route::middleware('super_admin')->group(function () {
        // Dashboard & Members
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/members', [Admin\MembersController::class, 'index'])->name('members.index');
        Route::post('/members/{member}/telegram-link', [Admin\MembersController::class, 'createTelegramMiniLink'])->name('members.telegram-link');
        Route::delete('/members/wipe-all', [Admin\MembersController::class, 'wipeAll'])->name('members.wipe-all');
        Route::delete('/members/{member}', [Admin\MembersController::class, 'destroy'])->name('members.destroy');
        Route::delete('/members/{member}/data', [Admin\MembersController::class, 'wipeData'])->name('members.wipe-data');
        Route::post('/members/{member}/restart-tour', [Admin\MembersController::class, 'restartTour'])->name('members.restart-tour');

        // Referrals
        Route::get('/referrals', [Admin\ReferralController::class, 'index'])->name('referrals.index');
        Route::get('/referrals/{user}', [Admin\ReferralController::class, 'show'])->name('referrals.show');
        Route::post('/referrals/{user}/enable', [Admin\ReferralController::class, 'enable'])->name('referrals.enable');
        Route::post('/referrals/{user}/disable', [Admin\ReferralController::class, 'disable'])->name('referrals.disable');
        Route::post('/referrals/{user}/regenerate', [Admin\ReferralController::class, 'regenerate'])->name('referrals.regenerate');

        Route::get('/tour', [Admin\TourController::class, 'index'])->name('tour.index');
        Route::delete('/tour/clear-all', [Admin\TourController::class, 'clearAll'])->name('tour.clear-all');
        Route::post('/tour/{member}/reset', [Admin\TourController::class, 'resetMember'])->name('tour.reset-member');

        // Translations
        Route::get('/translations', [Admin\TranslationController::class, 'index'])->name('translations.index');
        Route::post('/translations', [Admin\TranslationController::class, 'store'])->name('translations.store');
        Route::put('/translations', [Admin\TranslationController::class, 'update'])->name('translations.update');
        Route::post('/translations/sync', [Admin\TranslationController::class, 'sync'])->name('translations.sync');

        // SEO
        Route::get('/seo', [Admin\SeoController::class, 'index'])->name('seo.index');
        Route::put('/seo', [Admin\SeoController::class, 'update'])->name('seo.update');

        // Day assignments
        Route::get('/day-assignments', [Admin\DayAssignmentsController::class, 'index'])->name('day-assignments.index');
        Route::patch('/day-assignments/{daily}', [Admin\DayAssignmentsController::class, 'update'])->name('day-assignments.update');
        Route::post('/day-assignments/{daily}/send-reminder', [Admin\DayAssignmentsController::class, 'sendReminder'])->name('day-assignments.send-reminder');

        // WhatsApp
        Route::get('/whatsapp', fn () => redirect()->route('admin.whatsapp.settings'))->name('whatsapp.index');
        Route::get('/whatsapp/settings', [Admin\WhatsAppSettingsController::class, 'settings'])->name('whatsapp.settings');
        Route::put('/whatsapp', [Admin\WhatsAppSettingsController::class, 'update'])->name('whatsapp.update');
        Route::post('/whatsapp/test', [Admin\WhatsAppSettingsController::class, 'test'])->name('whatsapp.test');
        Route::post('/whatsapp/webhook', [Admin\WhatsAppSettingsController::class, 'updateWebhook'])->name('whatsapp.webhook');
        Route::put('/whatsapp/webhook-secret', [Admin\WhatsAppSettingsController::class, 'updateWebhookSecret'])->name('whatsapp.update-webhook-secret');
        Route::put('/whatsapp/reminder-once-only', [Admin\WhatsAppSettingsController::class, 'updateReminderOnceOnly'])->name('whatsapp.update-reminder-once-only');
        Route::get('/whatsapp/reminders', [Admin\WhatsAppRemindersController::class, 'index'])->name('whatsapp.reminders');
        Route::get('/whatsapp/template', [Admin\WhatsAppTemplateController::class, 'index'])->name('whatsapp.template');
        Route::put('/whatsapp/template', [Admin\WhatsAppTemplateController::class, 'update'])->name('whatsapp.template.update');
        Route::post('/whatsapp/template/test', [Admin\WhatsAppTemplateController::class, 'sendTest'])->name('whatsapp.template.test');
        Route::get('/whatsapp/timetable', [Admin\WhatsAppTimetableController::class, 'index'])->name('whatsapp.timetable');
        Route::get('/whatsapp/reminders/{member}/engagement', [Admin\WhatsAppRemindersController::class, 'engagement'])->name('whatsapp.reminders.engagement');
        Route::put('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'update'])->name('whatsapp.reminders.update');
        Route::post('/whatsapp/reminders/{member}/send', [Admin\WhatsAppRemindersController::class, 'sendReminder'])->name('whatsapp.reminders.send');
        Route::post('/whatsapp/reminders/{member}/disable', [Admin\WhatsAppRemindersController::class, 'disable'])->name('whatsapp.reminders.disable');
        Route::post('/whatsapp/reminders/{member}/confirm', [Admin\WhatsAppRemindersController::class, 'confirm'])->name('whatsapp.reminders.confirm');
        Route::delete('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'destroy'])->name('whatsapp.reminders.destroy');
        Route::get('/whatsapp/cron', [Admin\WhatsAppCronController::class, 'index'])->name('whatsapp.cron');
        Route::get('/whatsapp/members-data', [Admin\WhatsAppMembersDataController::class, 'index'])->name('whatsapp.members-data');
        Route::delete('/whatsapp/members-data/{member}', [Admin\WhatsAppMembersDataController::class, 'destroy'])->name('whatsapp.members-data.destroy');
        Route::get('/telegram', fn () => redirect()->route('admin.telegram.settings'))->name('telegram.index');
        Route::get('/telegram/settings', [Admin\TelegramSettingsController::class, 'settings'])->name('telegram.settings');
        Route::put('/telegram', [Admin\TelegramSettingsController::class, 'update'])->name('telegram.update');
        Route::put('/telegram/builder', [Admin\TelegramSettingsController::class, 'updateBuilder'])->name('telegram.builder.update');
        Route::post('/telegram/sync-menu', [Admin\TelegramSettingsController::class, 'syncMenu'])->name('telegram.sync-menu');
        Route::post('/telegram/test', [Admin\TelegramSettingsController::class, 'test'])->name('telegram.test');
        Route::post('/telegram/login-link', [TelegramAuthController::class, 'createAdminLoginLink'])->name('telegram.login-link');

        // Admin users
        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [Admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [Admin\AdminUserController::class, 'store'])->name('store');
            Route::get('/{admin}', [Admin\AdminUserController::class, 'show'])->name('show');
            Route::get('/{admin}/edit', [Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{admin}', [Admin\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{admin}', [Admin\AdminUserController::class, 'destroy'])->name('destroy');
            Route::post('/{admin}/telegram-link', [Admin\AdminUserController::class, 'createTelegramMiniLink'])
                ->name('telegram-link');
        });
    });
});

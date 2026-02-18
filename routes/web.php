<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Member;
use App\Http\Controllers\Webhook;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member-Facing Routes (public + member-identified)
|--------------------------------------------------------------------------
*/

// Welcome / onboarding (no auth required)
Route::get('/', [Member\OnboardingController::class, 'welcome'])->name('home');
Route::post('/member/register', [Member\OnboardingController::class, 'register'])->name('member.register');
Route::post('/member/identify', [Member\OnboardingController::class, 'identify'])->name('member.identify');
Route::post('/webhooks/ultramsg', [Webhook\UltraMsgWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.ultramsg');

// Public share page — serves OG meta for social crawlers, then redirects
Route::get('/share/day/{daily}', [Member\ShareController::class, 'day'])->name('share.day');

// Passcode routes (member-identified but before passcode check)
Route::get('/member/passcode', [Member\PasscodeController::class, 'show'])->name('member.passcode');
Route::get('/member/passcode/lock', [Member\PasscodeController::class, 'lock'])->name('member.passcode.lock');
Route::post('/member/passcode/verify', [Member\PasscodeController::class, 'verify'])->name('member.passcode.verify');
Route::post('/member/passcode/update', [Member\PasscodeController::class, 'update'])->name('member.passcode.update');

// Member-protected routes (identified + passcode cleared)
Route::middleware(['member', 'member.passcode'])->prefix('member')->name('member.')->group(function () {
    Route::get('/home', [Member\HomeController::class, 'index'])->name('home');
    Route::get('/calendar', [Member\HomeController::class, 'calendar'])->name('calendar');
    Route::get('/day/{daily}', [Member\HomeController::class, 'day'])->name('day');
    Route::get('/announcement/{announcement}', [Member\AnnouncementController::class, 'show'])->name('announcement.show');

    // Progress
    Route::get('/progress', [Member\ProgressController::class, 'index'])->name('progress');

    // Settings
    Route::get('/settings', [Member\SettingsController::class, 'index'])->name('settings');
});

// API-style routes for AJAX calls (JSON responses) — member resolved from token only
Route::prefix('api/member')->middleware('api.member')->name('api.member.')->group(function () {
    Route::post('/checklist/toggle', [Member\ChecklistController::class, 'toggle'])->name('checklist.toggle');
    Route::post('/checklist/custom-toggle', [Member\CustomActivityController::class, 'toggle'])->name('checklist.custom-toggle');
    Route::post('/settings', [Member\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/custom-activities', [Member\CustomActivityController::class, 'store'])->name('custom-activities.store');
    Route::post('/custom-activities/delete', [Member\CustomActivityController::class, 'destroy'])->name('custom-activities.destroy');
    Route::get('/progress/data', [Member\ProgressController::class, 'data'])->name('progress.data');
    Route::get('/data/export', [Member\DataController::class, 'export'])->name('data.export');
    Route::post('/data/import', [Member\DataController::class, 'import'])->name('data.import');
    Route::post('/data/clear', [Member\DataController::class, 'clear'])->name('data.clear');
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
        // Daily content
        Route::get('/daily', [Admin\DailyContentController::class, 'index'])->name('daily.index');
        Route::post('/daily/scaffold', [Admin\DailyContentController::class, 'scaffold'])->name('daily.scaffold');
        Route::get('/daily/create', [Admin\DailyContentController::class, 'create'])->name('daily.create');
        Route::get('/daily/copy-from/{day_number}', [Admin\DailyContentController::class, 'copyFrom'])->name('daily.copy_from');
        Route::post('/daily', [Admin\DailyContentController::class, 'store'])->name('daily.store');
        Route::get('/daily/{daily}/edit', [Admin\DailyContentController::class, 'edit'])->name('daily.edit');
        Route::patch('/daily/{daily}', [Admin\DailyContentController::class, 'patch'])->name('daily.patch');
        Route::put('/daily/{daily}', [Admin\DailyContentController::class, 'update'])->name('daily.update');

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
    });

    // Super admin only routes
    Route::middleware('super_admin')->group(function () {
        // Dashboard & Members
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/members', [Admin\MembersController::class, 'index'])->name('members.index');
        Route::delete('/members/wipe-all', [Admin\MembersController::class, 'wipeAll'])->name('members.wipe-all');
        Route::delete('/members/{member}', [Admin\MembersController::class, 'destroy'])->name('members.destroy');
        Route::delete('/members/{member}/data', [Admin\MembersController::class, 'wipeData'])->name('members.wipe-data');

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
        Route::get('/whatsapp/timetable', [Admin\WhatsAppTimetableController::class, 'index'])->name('whatsapp.timetable');
        Route::put('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'update'])->name('whatsapp.reminders.update');
        Route::post('/whatsapp/reminders/{member}/send', [Admin\WhatsAppRemindersController::class, 'sendReminder'])->name('whatsapp.reminders.send');
        Route::post('/whatsapp/reminders/{member}/disable', [Admin\WhatsAppRemindersController::class, 'disable'])->name('whatsapp.reminders.disable');
        Route::post('/whatsapp/reminders/{member}/confirm', [Admin\WhatsAppRemindersController::class, 'confirm'])->name('whatsapp.reminders.confirm');
        Route::delete('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'destroy'])->name('whatsapp.reminders.destroy');
        Route::get('/whatsapp/cron', [Admin\WhatsAppCronController::class, 'index'])->name('whatsapp.cron');

        // Admin users
        Route::prefix('admins')->name('admins.')->group(function () {
            Route::get('/', [Admin\AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [Admin\AdminUserController::class, 'create'])->name('create');
            Route::post('/', [Admin\AdminUserController::class, 'store'])->name('store');
            Route::get('/{admin}', [Admin\AdminUserController::class, 'show'])->name('show');
            Route::get('/{admin}/edit', [Admin\AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{admin}', [Admin\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{admin}', [Admin\AdminUserController::class, 'destroy'])->name('destroy');
        });
    });
});

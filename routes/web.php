<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\Member;
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
    // Dashboard
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/members', [Admin\MembersController::class, 'index'])->name('members.index');

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

    // Daily content
    Route::get('/daily', [Admin\DailyContentController::class, 'index'])->name('daily.index');
    Route::post('/daily/scaffold', [Admin\DailyContentController::class, 'scaffold'])->name('daily.scaffold');
    Route::get('/daily/create', [Admin\DailyContentController::class, 'create'])->name('daily.create');
    Route::post('/daily', [Admin\DailyContentController::class, 'store'])->name('daily.store');
    Route::get('/daily/{daily}/edit', [Admin\DailyContentController::class, 'edit'])->name('daily.edit');
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

    // Translations
    Route::get('/translations', [Admin\TranslationController::class, 'index'])->name('translations.index');
    Route::post('/translations', [Admin\TranslationController::class, 'store'])->name('translations.store');
    Route::put('/translations', [Admin\TranslationController::class, 'update'])->name('translations.update');
    Route::post('/translations/sync', [Admin\TranslationController::class, 'sync'])->name('translations.sync');

    // Admin users (super admin only)
    Route::middleware('super_admin')->prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [Admin\AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [Admin\AdminUserController::class, 'create'])->name('create');
        Route::post('/', [Admin\AdminUserController::class, 'store'])->name('store');
        Route::get('/{admin}', [Admin\AdminUserController::class, 'show'])->name('show');
        Route::get('/{admin}/edit', [Admin\AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{admin}', [Admin\AdminUserController::class, 'update'])->name('update');
        Route::delete('/{admin}', [Admin\AdminUserController::class, 'destroy'])->name('destroy');
    });
});

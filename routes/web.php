<?php

declare(strict_types=1);

use App\Http\Controllers\Admin;
use App\Http\Controllers\ContentSuggestionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\Member;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\TelegramAuthController;
use App\Http\Controllers\VolunteerInviteController;
use App\Http\Controllers\Webhook;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Member-Facing Routes (public + token-in-URL authenticated)
|--------------------------------------------------------------------------
*/

// Welcome / landing page
Route::get('/', [Member\OnboardingController::class, 'welcome'])->name('home');

// Registration with phone/email verification (replaces old cookie-based registration)
Route::post('/register', [Member\RegistrationController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('register');
Route::post('/register/verify', [Member\RegistrationController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('register.verify');
Route::post('/register/resend', [Member\RegistrationController::class, 'resend'])
    ->middleware('throttle:5,1')
    ->name('register.resend');
Route::post('/register/status', [Member\RegistrationController::class, 'status'])
    ->middleware('throttle:30,1')
    ->name('register.status');

// Member login (already registered)
Route::post('/login/member', [Member\RegistrationController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('login.member');
Route::post('/login/member/verify', [Member\RegistrationController::class, 'loginVerify'])
    ->middleware('throttle:10,1')
    ->name('login.member.verify');
Route::post('/login/member/status', [Member\RegistrationController::class, 'loginStatus'])
    ->middleware('throttle:30,1')
    ->name('login.member.status');

// Referral tracking
Route::get('/r/{code}', [Member\ReferralController::class, 'track'])
    ->where('code', '[A-Za-z0-9]{8}')
    ->name('referral.track');

// Webhooks (no CSRF)
Route::post('/webhooks/telegram', [Webhook\TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.telegram');
Route::post('/webhooks/ultramsg', [Webhook\UltraMsgWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('webhooks.ultramsg');

// Public share pages (OG meta for social crawlers)
Route::get('/share/day/{daily}', [Member\ShareController::class, 'day'])->name('share.day');
Route::get('/share/day/{daily}/public', [Member\ShareController::class, 'publicDay'])->name('share.day.public');

// Telegram / WhatsApp auth landing pages (kept for backward compat)
Route::get('/auth/go', [TelegramAuthController::class, 'go'])
    ->middleware('throttle:60,1')
    ->name('auth.go');
Route::get('/auth/access', [TelegramAuthController::class, 'access'])
    ->middleware('throttle:60,1')
    ->name('auth.access');
Route::get('/auth/member/continue', [Member\PersistentAuthController::class, 'bridge'])
    ->middleware('throttle:60,1')
    ->name('member.auth.bridge');
Route::post('/auth/member/restore', [Member\PersistentAuthController::class, 'restore'])
    ->middleware('throttle:20,1')
    ->name('member.auth.restore');
Route::get('/himamat/access/{token}', [Member\HimamatAccessController::class, 'preferences'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('member.himamat.access');
Route::get('/himamat/access/{token}/{day}/{slot}', [Member\HimamatAccessController::class, 'day'])
    ->where('token', '[A-Za-z0-9]{64}')
    ->middleware('throttle:60,1')
    ->name('member.himamat.access.slot');
Route::get('/telegram/mini/connect', [TelegramAuthController::class, 'miniConnect'])->name('telegram.mini.connect');
Route::get('/telegram/embed', [TelegramAuthController::class, 'embed'])->name('telegram.embed');
Route::get('/telegram/audio', [TelegramAuthController::class, 'audioPlayer'])->name('telegram.audio');
Route::get('/telegram/mezmur', [TelegramAuthController::class, 'mezmurPlayer'])->name('telegram.mezmur');
Route::get('/telegram/commemorations/{daily}', [TelegramAuthController::class, 'commemorations'])->name('telegram.commemorations');
Route::get('/telegram/webapp/home', [TelegramAuthController::class, 'webappHome'])
    ->middleware('throttle:60,1')
    ->name('telegram.webapp.home');
Route::post('/telegram/mini/connect', [TelegramAuthController::class, 'miniConnectSubmit'])
    ->middleware('throttle:60,1')
    ->name('telegram.mini.connect.submit');

// Public content suggestion form
Route::get('/suggest', [ContentSuggestionController::class, 'show'])->name('suggest');
Route::post('/suggest', [ContentSuggestionController::class, 'store'])->name('suggest.store');
Route::post('/feedback', [FeedbackController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('feedback.store');

// Post-Fasika feedback survey (token-based, no member session required)
Route::get('/survey/{token}', [Member\SurveyController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('survey.show');
Route::post('/survey/{token}/save', [Member\SurveyController::class, 'save'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:30,1')
    ->name('survey.save');
Route::post('/survey/{token}/submit', [Member\SurveyController::class, 'submit'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:10,1')
    ->name('survey.submit');
Route::get('/survey/{token}/thanks', [Member\SurveyController::class, 'thanks'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('survey.thanks');

// Volunteer invitations
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

/*
|--------------------------------------------------------------------------
| Public Content Routes (no auth — anyone can read)
|--------------------------------------------------------------------------
*/
Route::get('/day/{dayNumber}-{daily}', [PublicContentController::class, 'showDay'])
    ->whereNumber('dayNumber')
    ->name('public.day.show');
Route::get('/calendar', [PublicContentController::class, 'calendar'])->name('public.calendar');
Route::get('/week/{weeklyTheme}', [PublicContentController::class, 'week'])->name('public.week');
Route::get('/commemorations/{dayNumber}-{daily}', [PublicContentController::class, 'commemorations'])
    ->whereNumber('dayNumber')
    ->name('public.commemorations');
Route::get('/announcement/{announcement}', [PublicContentController::class, 'announcement'])->name('public.announcement');

/*
|--------------------------------------------------------------------------
| Token-in-URL Authenticated Member Routes
|--------------------------------------------------------------------------
*/
Route::middleware('resolve.member.url')->prefix('m/{token}')->where(['token' => '[A-Za-z0-9]{64}'])->name('member.')->group(function () {
    Route::get('/home', [Member\HomeController::class, 'index'])->name('home');
    Route::get('/today-unavailable', [Member\HomeController::class, 'todayUnavailable'])->name('today-unavailable');
    Route::get('/calendar', [Member\HomeController::class, 'calendar'])->name('calendar');
    // Keep $token in the closure signature so later args map to dayNumber and daily.
    Route::get('/day/{dayNumber}-{daily}', function (\Illuminate\Http\Request $request, string $token, string $dayNumber, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->showDay($request, $dayNumber, $model, app(\App\Services\EthiopianCalendarService::class));
    })->whereNumber('dayNumber')->name('day.show');
    Route::get('/day/{daily}', function (\Illuminate\Http\Request $request, string $token, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->day($request, $model);
    })->name('day');
    Route::get('/day/{dayNumber}-{daily}/commemorations', function (\Illuminate\Http\Request $request, string $token, string $dayNumber, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->showCommemorations($request, $dayNumber, $model, app(\App\Services\EthiopianCalendarService::class));
    })->whereNumber('dayNumber')->name('commemorations.show');
    Route::get('/day/{daily}/commemorations', function (\Illuminate\Http\Request $request, string $token, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->commemorations($request, $model);
    })->name('commemorations');
    Route::get('/week/{weeklyTheme}', function (\Illuminate\Http\Request $request, string $token, string $weeklyTheme) {
        $model = \App\Models\WeeklyTheme::findOrFail($weeklyTheme);

        return app(Member\HomeController::class)->week($request, $model);
    })->name('week');
    Route::get('/announcement/{announcement}', function (\Illuminate\Http\Request $request, string $token, string $announcement) {
        $model = \App\Models\Announcement::findOrFail($announcement);

        return app(Member\AnnouncementController::class)->show($model);
    })->name('announcement.show');
    Route::get('/progress', [Member\ProgressController::class, 'index'])->name('progress');
    Route::get('/settings', [Member\SettingsController::class, 'index'])->name('settings');
});

Route::middleware('resolve.member.url')->prefix('api/m/{token}/device')->where(['token' => '[A-Za-z0-9]{64}'])->name('member.device.')->group(function () {
    Route::post('/send-code', [Member\PersistentAuthController::class, 'sendVerificationCode'])
        ->middleware('throttle:3,1')
        ->name('send-code');
    Route::post('/verify-code', [Member\PersistentAuthController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('verify-code');
});

// Token-in-URL API routes (JSON responses)
Route::middleware('resolve.member.url')->prefix('api/m/{token}')->where(['token' => '[A-Za-z0-9]{64}'])->name('api.member.')->group(function () {
    // Read-only / low-risk
    Route::post('/checklist/toggle', [Member\ChecklistController::class, 'toggle'])->name('checklist.toggle');
    Route::post('/checklist/custom-toggle', [Member\CustomActivityController::class, 'toggle'])->name('checklist.custom-toggle');
    Route::get('/progress/data', [Member\ProgressController::class, 'data'])->name('progress.data');
    Route::get('/fundraising/popup', [Member\FundraisingController::class, 'popup'])->name('fundraising.popup');
    Route::post('/fundraising/snooze', [Member\FundraisingController::class, 'snooze'])->name('fundraising.snooze');
    Route::post('/fundraising/interested', [Member\FundraisingController::class, 'interested'])->name('fundraising.interested');
    Route::post('/banner/{banner}/respond', function (\Illuminate\Http\Request $request, string $token, string $banner) {
        $model = \App\Models\Banner::findOrFail($banner);

        return app(Member\BannerController::class)->respond($request, $model);
    })->name('banner.respond');
    Route::post('/tour/complete', [Member\TourController::class, 'complete'])->name('tour.complete');
    Route::post('/tour/reset', [Member\TourController::class, 'reset'])->name('tour.reset');

    // Identity confirmation (this IS the verification endpoint)
    Route::post('/confirm-identity', [Member\SettingsController::class, 'confirmIdentity'])->name('confirm-identity');

    // Write operations: require identity confirmation (session, trusted device, or inline)
    Route::middleware('member.confirm_identity')->group(function () {
        Route::post('/settings', [Member\SettingsController::class, 'update'])->name('settings.update');
        Route::post('/custom-activities', [Member\CustomActivityController::class, 'store'])->name('custom-activities.store');
        Route::post('/custom-activities/update', [Member\CustomActivityController::class, 'update'])->name('custom-activities.update');
        Route::post('/custom-activities/delete', [Member\CustomActivityController::class, 'destroy'])->name('custom-activities.destroy');
        Route::get('/data/export', [Member\DataController::class, 'export'])->name('data.export');
        Route::post('/data/import', [Member\DataController::class, 'import'])->name('data.import');
        Route::post('/data/clear', [Member\DataController::class, 'clear'])->name('data.clear');
        Route::post('/telegram-link', [Member\SettingsController::class, 'generateTelegramLink'])->name('telegram-link');
        Route::post('/telegram-unlink', [Member\SettingsController::class, 'unlinkTelegram'])->name('telegram-unlink');
        Route::post('/account/delete', [Member\SettingsController::class, 'deleteAccount'])->name('account.delete');
    });
});

/*
|--------------------------------------------------------------------------
| Cookie-Based Member Routes (existing members — zero disruption)
|--------------------------------------------------------------------------
| These routes preserve the old /member/* paths so existing cookie-auth
| members keep working exactly as before. They use the same controllers
| as the new /m/{token}/* routes.
*/

// Direct token access → establish session & redirect to token-in-URL home
Route::get('/member/access/{token}', [Member\OnboardingController::class, 'access'])
    ->where('token', '[A-Za-z0-9]{20,128}')
    ->name('member.access');

// Old registration/session management (still cookie-based)
Route::post('/member/register', [Member\OnboardingController::class, 'register']);
Route::middleware('member')->group(function () {
    Route::post('/member/identify', [Member\OnboardingController::class, 'identify']);
    Route::post('/member/reset', [Member\OnboardingController::class, 'reset']);
});

// Passcode routes (cookie-auth members with passcode protection)
Route::middleware('member')->prefix('member')->group(function () {
    Route::get('/passcode', [Member\PasscodeController::class, 'show'])->name('member.passcode');
    Route::get('/passcode/lock', [Member\PasscodeController::class, 'lock'])->name('member.passcode.lock');
    Route::post('/passcode/verify', [Member\PasscodeController::class, 'verify'])->name('member.passcode.verify');
    Route::post('/passcode/update', [Member\PasscodeController::class, 'update'])->name('member.passcode.update');
    Route::post('/passcode/reset', [Member\PasscodeController::class, 'reset'])->name('member.passcode.reset');
});

// Legacy redirects for old-format URLs (no auth — these are shared links)
// /member/day/{id} → /day/{dayNumber}-{id} (public)
Route::get('/member/day/{daily}/commemorations', function (\App\Models\DailyContent $daily) {
    return redirect("/commemorations/{$daily->day_number}-{$daily->id}");
})->whereNumber('daily');
Route::get('/member/day/{daily}', function (\App\Models\DailyContent $daily) {
    return redirect("/day/{$daily->day_number}-{$daily->id}");
})->whereNumber('daily');

// Cookie-auth member page routes (same controllers as /m/{token}/* routes)
Route::middleware(['member', 'member.passcode'])->prefix('member')->group(function () {
    Route::get('/himamat', [Member\HimamatController::class, 'index'])->name('member.himamat.index');
    Route::get('/himamat/preferences', [Member\HimamatController::class, 'preferences'])->name('member.himamat.preferences');
    Route::get('/himamat/{day}', [Member\HimamatController::class, 'day'])->name('member.himamat.day');
    Route::get('/himamat/{day}/{slot}', [Member\HimamatController::class, 'slot'])->name('member.himamat.slot');
    Route::get('/home', [Member\HomeController::class, 'index'])->name('old.member.home');
    Route::get('/today-unavailable', [Member\HomeController::class, 'todayUnavailable'])->name('old.member.today-unavailable');
    Route::get('/calendar', [Member\HomeController::class, 'calendar'])->name('old.member.calendar');
    Route::get('/day/{dayNumber}-{daily}', function (\Illuminate\Http\Request $request, string $dayNumber, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->showDay($request, $dayNumber, $model, app(\App\Services\EthiopianCalendarService::class));
    })->whereNumber('dayNumber')->name('old.member.day.show');
    Route::get('/day/{dayNumber}-{daily}/commemorations', function (\Illuminate\Http\Request $request, string $dayNumber, string $daily) {
        $model = \App\Models\DailyContent::resolveRouteValue($daily);

        return app(Member\HomeController::class)->showCommemorations($request, $dayNumber, $model, app(\App\Services\EthiopianCalendarService::class));
    })->whereNumber('dayNumber')->name('old.member.commemorations.show');
    Route::get('/week/{weeklyTheme}', [Member\HomeController::class, 'week'])->name('old.member.week');
    Route::get('/announcement/{announcement}', [Member\AnnouncementController::class, 'show'])->name('old.member.announcement.show');
    Route::get('/progress', [Member\ProgressController::class, 'index'])->name('old.member.progress');
    Route::get('/settings', [Member\SettingsController::class, 'index'])->name('old.member.settings');
});

// Cookie-auth member API routes (same controllers as /api/m/{token}/* routes)
Route::middleware('api.member')->prefix('api/member')->group(function () {
    // Read-only / low-risk
    Route::post('/checklist/toggle', [Member\ChecklistController::class, 'toggle']);
    Route::post('/checklist/custom-toggle', [Member\CustomActivityController::class, 'toggle']);
    Route::get('/progress/data', [Member\ProgressController::class, 'data']);
    Route::get('/fundraising/popup', [Member\FundraisingController::class, 'popup']);
    Route::post('/fundraising/snooze', [Member\FundraisingController::class, 'snooze']);
    Route::post('/fundraising/interested', [Member\FundraisingController::class, 'interested']);
    Route::post('/banner/{banner}/respond', [Member\BannerController::class, 'respond']);
    Route::post('/tour/complete', [Member\TourController::class, 'complete']);
    Route::post('/tour/reset', [Member\TourController::class, 'reset']);

    // Identity confirmation
    Route::post('/confirm-identity', [Member\SettingsController::class, 'confirmIdentity']);

    // Write operations: require identity confirmation
    Route::middleware('member.confirm_identity')->group(function () {
        Route::post('/himamat/preferences', [Member\HimamatPreferenceController::class, 'update']);
        Route::post('/settings', [Member\SettingsController::class, 'update']);
        Route::post('/custom-activities', [Member\CustomActivityController::class, 'store']);
        Route::post('/custom-activities/update', [Member\CustomActivityController::class, 'update']);
        Route::post('/custom-activities/delete', [Member\CustomActivityController::class, 'destroy']);
        Route::get('/data/export', [Member\DataController::class, 'export']);
        Route::post('/data/import', [Member\DataController::class, 'import']);
        Route::post('/data/clear', [Member\DataController::class, 'clear']);
        Route::post('/telegram-link', [Member\SettingsController::class, 'generateTelegramLink']);
        Route::post('/telegram-unlink', [Member\SettingsController::class, 'unlinkTelegram']);
        Route::post('/account/delete', [Member\SettingsController::class, 'deleteAccount']);
    });
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
Route::middleware(['auth', 'admin.audit'])->prefix('admin')->name('admin.')->group(function () {
    // Writer/editor/admin routes
    Route::middleware('admin_role:writer,editor,admin')->group(function () {
        // My suggestions (writer sees their own submissions)
        Route::get('/suggestions/my', [App\Http\Controllers\ContentSuggestionController::class, 'my'])->name('suggestions.my');
        // Advanced suggestion wizard (mirrors Telegram suggest flow)
        Route::get('/suggest/advanced', [Admin\AdvancedSuggestionController::class, 'create'])->name('advanced-suggestions.create');
        Route::post('/suggest/advanced', [Admin\AdvancedSuggestionController::class, 'store'])->name('advanced-suggestions.store');
        Route::get('/profile', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [Admin\ProfileController::class, 'update'])->name('profile.update');

        // Daily content (view: writer/editor/admin; write: admin only)
        Route::get('/daily', [Admin\DailyContentController::class, 'index'])->name('daily.index');
        Route::get('/daily/{daily}/preview', [Admin\DailyContentController::class, 'preview'])->name('daily.preview');
        Route::get('/daily/{daily}/edit', [Admin\DailyContentController::class, 'edit'])->name('daily.edit');
        Route::get('/daily/{daily}/views', [Admin\DailyContentController::class, 'viewDetails'])->name('daily.views');

        // Daily content suggestions (writer/editor submit; admin reviews)
        Route::post('/daily/{daily}/suggestions', [Admin\DailyContentSuggestionController::class, 'store'])
            ->name('daily.suggestions.store');

        // Announcements (view: writer/editor/admin; write: admin only)
        Route::get('/announcements', [Admin\AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/announcements/create', [Admin\AnnouncementController::class, 'create'])->name('announcements.create');
        Route::get('/announcements/{announcement}/edit', [Admin\AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::post('/announcements/{announcement}/suggestions', [Admin\AnnouncementSuggestionController::class, 'store'])
            ->name('announcements.suggestions.store');

        // Activities
        Route::get('/activities', [Admin\ActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [Admin\ActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities', [Admin\ActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}/edit', [Admin\ActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/activities/{activity}', [Admin\ActivityController::class, 'update'])->name('activities.update');
        Route::delete('/activities/{activity}', [Admin\ActivityController::class, 'destroy'])->name('activities.destroy');
        Route::get('/himamat', [Admin\HimamatDayController::class, 'index'])->name('himamat.index');
        Route::get('/himamat/tracking', [Admin\HimamatDayController::class, 'tracking'])->name('himamat.tracking');
        Route::get('/himamat/reminder-health', [Admin\HimamatDayController::class, 'reminderHealth'])->name('himamat.reminder-health');
        Route::post('/himamat/scaffold', [Admin\HimamatDayController::class, 'scaffold'])->name('himamat.scaffold');
        Route::get('/himamat/{day}/edit', [Admin\HimamatDayController::class, 'edit'])->name('himamat.edit');
        Route::get('/himamat/{day}/preview', [Admin\HimamatDayController::class, 'preview'])->name('himamat.preview');
        Route::put('/himamat/{day}', [Admin\HimamatDayController::class, 'update'])->name('himamat.update');
    });

    // Daily content write routes (writer/editor/admin can create, edit, update, delete)
    Route::middleware('admin_role:writer,editor,admin')->group(function () {
        Route::post('/daily/scaffold', [Admin\DailyContentController::class, 'scaffold'])->name('daily.scaffold');
        Route::get('/daily/create', [Admin\DailyContentController::class, 'create'])->name('daily.create');
        Route::get('/daily/copy-from/{day_number}', [Admin\DailyContentController::class, 'copyFrom'])->name('daily.copy_from');
        Route::post('/daily/upload-book-pdf', [Admin\DailyContentController::class, 'uploadBookPdf'])->name('daily.upload_book_pdf');
        Route::post('/daily/upload-sinksar-image', [Admin\DailyContentController::class, 'uploadSinksarImage'])->name('daily.upload_sinksar_image');
        Route::post('/daily/delete-sinksar-image', [Admin\DailyContentController::class, 'deleteSinksarImage'])->name('daily.delete_sinksar_image');
        Route::post('/daily/upload-bible-audio', [Admin\DailyContentController::class, 'uploadBibleAudio'])->name('daily.upload_bible_audio');
        Route::post('/daily/delete-bible-audio', [Admin\DailyContentController::class, 'deleteBibleAudio'])->name('daily.delete_bible_audio');
        Route::post('/daily', [Admin\DailyContentController::class, 'store'])->name('daily.store');
        Route::patch('/daily/{daily}', [Admin\DailyContentController::class, 'patch'])->name('daily.patch');
        Route::put('/daily/{daily}', [Admin\DailyContentController::class, 'update'])->name('daily.update');
        Route::delete('/daily/{daily}', [Admin\DailyContentController::class, 'destroy'])->name('daily.destroy');
    });

    // Admin-only routes (suggestions review, announcements write)
    Route::middleware('admin_role:admin')->group(function () {
        // Daily content suggestions (admin review and apply/reject)
        Route::get('/daily-suggestions', [Admin\DailyContentSuggestionController::class, 'index'])
            ->name('daily-suggestions.index');
        Route::post('/daily-suggestions/{suggestion}/apply', [Admin\DailyContentSuggestionController::class, 'apply'])
            ->name('daily-suggestions.apply');
        Route::post('/daily-suggestions/{suggestion}/reject', [Admin\DailyContentSuggestionController::class, 'reject'])
            ->name('daily-suggestions.reject');

        // Announcement write + suggestions review
        Route::post('/announcements', [Admin\AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('/announcements/{announcement}', [Admin\AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/announcements/{announcement}', [Admin\AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('/announcement-suggestions', [Admin\AnnouncementSuggestionController::class, 'index'])
            ->name('announcement-suggestions.index');
        Route::post('/announcement-suggestions/{suggestion}/apply', [Admin\AnnouncementSuggestionController::class, 'apply'])
            ->name('announcement-suggestions.apply');
        Route::post('/announcement-suggestions/{suggestion}/reject', [Admin\AnnouncementSuggestionController::class, 'reject'])
            ->name('announcement-suggestions.reject');
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

        // Feedback (contact form)
        Route::get('/feedback', [Admin\FeedbackController::class, 'index'])->name('feedback.index');
        Route::post('/feedback/{feedback}/toggle-read', [Admin\FeedbackController::class, 'toggleRead'])->name('feedback.toggle-read');
        Route::delete('/feedback/{feedback}', [Admin\FeedbackController::class, 'destroy'])->name('feedback.destroy');

        // Post-Fasika survey results
        Route::get('/survey', [Admin\SurveyController::class, 'index'])->name('survey.index');
        Route::get('/survey/export', [Admin\SurveyController::class, 'export'])->name('survey.export');

        // Content suggestions review
        Route::get('/suggestions', [Admin\ContentSuggestionController::class, 'index'])->name('suggestions.index');
        Route::delete('/suggestions/clear-all', [Admin\ContentSuggestionController::class, 'clearAll'])->name('suggestions.clear-all');
        Route::post('/suggestions/{suggestion}/use', [Admin\ContentSuggestionController::class, 'markUsed'])->name('suggestions.mark-used');
        Route::post('/suggestions/{suggestion}/unuse', [Admin\ContentSuggestionController::class, 'unmarkUsed'])->name('suggestions.unmark-used');
        Route::post('/suggestions/{suggestion}/apply', [Admin\ContentSuggestionController::class, 'apply'])->name('suggestions.apply');
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
        Route::post('/themes/{theme}/import-lectionary', [Admin\WeeklyThemeController::class, 'importLectionary'])->name('themes.import-lectionary');
        Route::put('/themes/{theme}', [Admin\WeeklyThemeController::class, 'update'])->name('themes.update');

        // Lectionary (ግጻዌ — daily scripture readings)
        Route::get('/lectionary', [Admin\LectionaryController::class, 'index'])->name('lectionary.index');
        Route::post('/lectionary', [Admin\LectionaryController::class, 'store'])->name('lectionary.store');
        Route::put('/lectionary/{lectionary}', [Admin\LectionaryController::class, 'update'])->name('lectionary.update');
        Route::delete('/lectionary/{lectionary}', [Admin\LectionaryController::class, 'destroy'])->name('lectionary.destroy');

        // Ethiopian Synaxarium (calendar celebrations)
        Route::get('/synaxarium', [Admin\SynaxariumController::class, 'index'])->name('synaxarium.index');
        Route::get('/synaxarium/bulk', [Admin\SynaxariumController::class, 'bulkCreate'])->name('synaxarium.bulk');
        Route::post('/synaxarium/bulk', [Admin\SynaxariumController::class, 'bulkStore'])->name('synaxarium.bulk.store');
        Route::post('/synaxarium/monthly', [Admin\SynaxariumController::class, 'storeMonthly'])->name('synaxarium.monthly.store');
        Route::put('/synaxarium/monthly/{monthly}', [Admin\SynaxariumController::class, 'updateMonthly'])->name('synaxarium.monthly.update');
        Route::delete('/synaxarium/monthly/{monthly}', [Admin\SynaxariumController::class, 'destroyMonthly'])->name('synaxarium.monthly.destroy');
        Route::post('/synaxarium/annual', [Admin\SynaxariumController::class, 'storeAnnual'])->name('synaxarium.annual.store');
        Route::put('/synaxarium/annual/{annual}', [Admin\SynaxariumController::class, 'updateAnnual'])->name('synaxarium.annual.update');
        Route::delete('/synaxarium/annual/{annual}', [Admin\SynaxariumController::class, 'destroyAnnual'])->name('synaxarium.annual.destroy');
        Route::post('/synaxarium/monthly/{monthly}/convert-to-annual', [Admin\SynaxariumController::class, 'convertMonthlyToAnnual'])->name('synaxarium.monthly.convert');
        Route::post('/synaxarium/annual/{annual}/convert-to-monthly', [Admin\SynaxariumController::class, 'convertAnnualToMonthly'])->name('synaxarium.annual.convert');
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
        Route::get('/members/{member}', [Admin\MembersController::class, 'show'])->name('members.show');
        Route::patch('/members/{member}', [Admin\MembersController::class, 'update'])->name('members.update');
        Route::post('/members/{member}/telegram-link', [Admin\MembersController::class, 'createTelegramMiniLink'])->name('members.telegram-link');
        Route::delete('/members/wipe-all', [Admin\MembersController::class, 'wipeAll'])->name('members.wipe-all');
        Route::delete('/members/{member}', [Admin\MembersController::class, 'destroy'])->name('members.destroy');
        Route::delete('/members/{member}/data', [Admin\MembersController::class, 'wipeData'])->name('members.wipe-data');
        Route::post('/members/{member}/restart-tour', [Admin\MembersController::class, 'restartTour'])->name('members.restart-tour');
        Route::post('/members/{member}/reinvite', [Admin\MembersController::class, 'reInvite'])->name('members.reinvite');

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
        Route::post('/whatsapp/template/bulk-save', [Admin\WhatsAppTemplateController::class, 'saveBulk'])->name('whatsapp.template.bulk-save');
        Route::post('/whatsapp/template/bulk-test', [Admin\WhatsAppTemplateController::class, 'sendBulkSample'])->name('whatsapp.template.bulk-test');
        Route::post('/whatsapp/template/bulk-send', [Admin\WhatsAppTemplateController::class, 'sendBulk'])->name('whatsapp.template.bulk-send');
        Route::get('/whatsapp/timetable', [Admin\WhatsAppTimetableController::class, 'index'])->name('whatsapp.timetable');
        Route::get('/whatsapp/reminders/{member}/engagement', [Admin\WhatsAppRemindersController::class, 'engagement'])->name('whatsapp.reminders.engagement');
        Route::put('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'update'])->name('whatsapp.reminders.update');
        Route::post('/whatsapp/reminders/{member}/send', [Admin\WhatsAppRemindersController::class, 'sendReminder'])->name('whatsapp.reminders.send');
        Route::post('/whatsapp/reminders/{member}/disable', [Admin\WhatsAppRemindersController::class, 'disable'])->name('whatsapp.reminders.disable');
        Route::post('/whatsapp/reminders/{member}/confirm', [Admin\WhatsAppRemindersController::class, 'confirm'])->name('whatsapp.reminders.confirm');
        Route::delete('/whatsapp/reminders/{member}', [Admin\WhatsAppRemindersController::class, 'destroy'])->name('whatsapp.reminders.destroy');
        Route::post('/whatsapp/reminders/test-email', [Admin\WhatsAppRemindersController::class, 'testEmailReminder'])->name('whatsapp.reminders.test-email');
        Route::get('/whatsapp/cron', [Admin\WhatsAppCronController::class, 'index'])->name('whatsapp.cron');
        Route::get('/whatsapp/members-data', [Admin\WhatsAppMembersDataController::class, 'index'])->name('whatsapp.members-data');
        Route::delete('/whatsapp/members-data/{member}', [Admin\WhatsAppMembersDataController::class, 'destroy'])->name('whatsapp.members-data.destroy');
        Route::get('/telegram', fn () => redirect()->route('admin.telegram.settings'))->name('telegram.index');
        Route::get('/telegram/settings', [Admin\TelegramSettingsController::class, 'settings'])->name('telegram.settings');
        Route::get('/telegram/users', [Admin\TelegramLinkedUsersController::class, 'index'])->name('telegram.users');
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

        // Audit log
        Route::get('/audit', [Admin\AuditLogController::class, 'index'])->name('audit.index');
    });
});

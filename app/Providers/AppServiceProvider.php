<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('admin', fn ($value) => User::findOrFail($value));

        RateLimiter::for('himamat-whatsapp-reminders', function (): Limit {
            return Limit::perMinute(
                (int) config('himamat.reminders.rate_limits.reminders_per_minute', 240)
            )->by('himamat-whatsapp-reminders');
        });

        RateLimiter::for('himamat-whatsapp-invitations', function (): Limit {
            return Limit::perMinute(
                (int) config('himamat.reminders.rate_limits.invitations_per_minute', 120)
            )->by('himamat-whatsapp-invitations');
        });

        View::composer('layouts.member', function ($view): void {
            $data = $view->getData();
            if (empty($data['currentMember'])) {
                $member = request()->attributes->get('member');
                if ($member) {
                    $view->with('currentMember', $member);
                }
            }
        });
    }
}

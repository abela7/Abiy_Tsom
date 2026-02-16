<?php

namespace App\Providers;

use App\Models\User;
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

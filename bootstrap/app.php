<?php

use App\Http\Middleware\CheckMemberPasscode;
use App\Http\Middleware\IdentifyMember;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirect unauthenticated users to admin login (route 'login' does not exist)
        $middleware->redirectGuestsTo('/admin/login');

        // Applied to every web request
        $middleware->web(append: [
            SetLocale::class,
        ]);

        // Named middleware aliases for route groups
        $middleware->alias([
            'member' => IdentifyMember::class,
            'member.passcode' => CheckMemberPasscode::class,
            'api.member' => \App\Http\Middleware\EnsureMemberFromToken::class,
            'super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'admin_role' => \App\Http\Middleware\EnsureAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

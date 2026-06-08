<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureMemberCanRead;
use App\Http\Middleware\EnsureMemberProfileComplete;
use App\Http\Middleware\EnsureStaffIsActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::get('/health/ready', HealthController::class)
                ->name('health.ready');
        },
    )
    ->withCommands()
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->routeIs('member.reader.*', 'member.logout')
                ? route('member.login')
                : route('login')
        );
        $middleware->alias([
            'admin.only' => EnsureUserIsAdmin::class,
            'member.reader' => EnsureMemberCanRead::class,
            'member.profile.complete' => EnsureMemberProfileComplete::class,
            'staff.active' => EnsureStaffIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

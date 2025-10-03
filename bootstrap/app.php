<?php

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
        // Global middleware
        $middleware->append(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Trusted proxies configured via TRUSTED_PROXIES env variable
        // trustProxies() is called automatically by Laravel
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
